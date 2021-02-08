<?php
require_once ('././config/dbHandler.php');

class analysisManager
{

    public $u_file;
    public $layer_name;
    public $snap_tolerance;
    public $max_pipe_length;
    public $projection;
    public $existing_layer;
    public $tank_layer;

    function uploadData($u_file)
    {

        $filename = substr($u_file['name'], 0, -4);

        $path = ROOTDIR . "/analysis_tools/uploads/";

        $uni_name = $filename . '_' . uniqid();
        $uni_name = strtolower($uni_name);
        $uni_name = str_replace(' ', '_', $uni_name);

        mkdir($path . $uni_name, 0777);

        $move_location = $path . $uni_name . "/" . $uni_name . '.zip';

        move_uploaded_file($u_file['tmp_name'], $move_location);

        $this->unzip_file($move_location, $path . $uni_name . "/");

        $folderPath = $path . $uni_name . "/";

        $shp_location = glob($folderPath . "*.shp");

        $shx_location = glob($folderPath . "*.shx");
        $dbf_location = glob($folderPath . "*.dbf");
        $prj_location = glob($folderPath . "*.prj");

        if ($shp_location && $shx_location && $dbf_location && $prj_location)
        {

            $proj = $this->projection;

            $cmd = '"C:\Program Files\PostgreSQL\10\bin\shp2pgsql" -s ' . $proj . ' -c "' . $shp_location[0] . '" ' . $uni_name;

            $queries = shell_exec($cmd);

            $insert_to_postgis = pg_query(DBCONNECT, $queries);

            $addGeom = pg_query(DBCONNECT, "alter table $uni_name add column the_geom geometry");
            $addGeom = pg_query(DBCONNECT, "update $uni_name set the_geom = st_transform(ST_Force2D(geom), 3857)");
            $addGeom = pg_query(DBCONNECT, "alter table $uni_name drop column geom");
            $addGeom = pg_query(DBCONNECT, "alter table $uni_name rename the_geom to geom");
            $addGeom = pg_query(DBCONNECT, "select UpdateGeometrySRID('$uni_name', 'geom', 3857)");

            // $insert_to_postgis = pg_query(DBCONNECT, "update $uni_name set geom = st_transform()");
            $this->addLayerIndex($uni_name, $this->layer_name);
            $this->addToGeoserver($uni_name);

            return array(
                "status" => true,
                "upload_message" => "$filename is valid GIS Data and upload sucessfull",
                "u_name" => $uni_name
            );
        }

        else
        {

            return array(
                "status" => false,
                "upload_message" => "Invalid Data- add all necessary files "

            );

        }

    }

    function buildModel()
    {

        if (isset($this->existing_layer))
        {
            $tableName = $this->existing_layer;
            $tableName = $tableName;
            $analysisTable = "build_model_$tableName" . '_' . uniqid();
            $output_pipes = "pipes_$tableName" . '_' . uniqid();
            $output_junctions = "junctions_$tableName" . '_' . uniqid();
            $topo_name = "topo_$tableName" . '_' . uniqid();
        }
        else
        {
            $tableName = $this->uploadData($this->u_file);
            $tableName = $tableName['u_name'];
            $analysisTable = "build_model_$tableName";
            $output_pipes = "pipes_$tableName";
            $output_junctions = "junctions_$tableName";
            $topo_name = "topo_$tableName";

            $tank_tableName = $this->uploadData($this->tank_layer);
            $tank_tableName = $tank_tableName['u_name'];

        }

        $snap_tolerance = $this->snap_tolerance;
        $max_pipe_length = $this->max_pipe_length;

        $create_analytics_table = pg_query(DBCONNECT, "create table $analysisTable as SELECT ST_LineSubstring(the_geom, $max_pipe_length*n/length, CASE WHEN $max_pipe_length*(n+1) < length THEN $max_pipe_length*(n+1)/length ELSE 1 END) As geom FROM (SELECT ST_LineMerge((ST_Dump(ST_LineMerge(ST_Node(st_union($tableName.geom))))).geom) AS the_geom, ST_Length((ST_Dump(ST_LineMerge(ST_Node(st_union($tableName.geom))))).geom) As length FROM $tableName ) AS t CROSS JOIN generate_series(0,10000) AS n WHERE n*$max_pipe_length/length < 1;");

        $create_topo = pg_query(DBCONNECT, "SELECT topology.CreateTopology('$topo_name', 3857)");
        $create_topo_geom = pg_query(DBCONNECT, "SELECT topology.AddTopoGeometryColumn('$topo_name', 'public', '$analysisTable', 'topo_geom', 'LINESTRING')");

        $setting_tolerance = pg_query(DBCONNECT, "UPDATE $analysisTable SET topo_geom = topology.toTopoGeom(geom, '$topo_name', 1, $snap_tolerance)");

        $creating_output_pipes = pg_query(DBCONNECT, "create table public.$output_pipes as select * from $topo_name.edge_data");
        $creating_output_junctions = pg_query(DBCONNECT, "create table public.$output_junctions as select * from $topo_name.node");
        $adding_length_column = pg_query(DBCONNECT, "ALTER TABLE $output_pipes ADD COLUMN length VARCHAR,ADD COLUMN graph_type VARCHAR");
        $adding_length_column = pg_query(DBCONNECT, "ALTER TABLE $output_junctions ADD COLUMN degree VARCHAR");
        $update_length_column = pg_query(DBCONNECT, "update $output_pipes set length = st_length(geom)");
        $update_mesh = pg_query(DBCONNECT, "update $output_pipes set graph_type = 'MESHED'");
        $update_mesh = pg_query(DBCONNECT, "update $output_pipes set graph_type = 'BRANCHED' from ( select a.edge_id from $output_pipes as a, $output_pipes as b where St_touches(ST_EndPoint(a.geom),b.geom) group by a.edge_id HAVING COUNT(*) < 2 ) as subquery where $output_pipes.edge_id = subquery.edge_id");
        $update_degree = pg_query(DBCONNECT, "update $output_junctions set degree = subquery.count from ( select distinct(a.node_id), count(*) from $output_junctions as a ,$output_pipes as b where st_intersects(a.geom, b.geom) group by a.node_id) as subquery where $output_junctions.node_id = subquery.node_id");

        $addDCID2J = pg_query(DBCONNECT, "alter table $output_junctions add COLUMN dc_id text, add COLUMN ref_type text,  add COLUMN new_dc text");
        $update_ref = pg_query(DBCONNECT, "update $output_junctions set ref_type = 'junction'");
        $addDCID2P = pg_query(DBCONNECT, "alter table $output_pipes add COLUMN dc_id text, add COLUMN new_dc text");
        $updDC2p = pg_query(DBCONNECT, "update $output_pipes set dc_id = edge_id");
        $updDC2j = pg_query(DBCONNECT, "update $output_junctions set dc_id = node_id");
        $traceFunctionName = "trace_pipelines_" . uniqid();
        $createTraceFunction = pg_query(DBCONNECT, 'CREATE OR REPLACE FUNCTION public.' . $traceFunctionName . '( p_start_id text, p_start_sequence integer) RETURNS TABLE(line_id text, line_sequence integer) LANGUAGE plpgsql COST 100 VOLATILE ROWS 1000 AS $BODY$ DECLARE r record; branch_rec record; next_geoms geometry[]; last_geoms geometry[]; next_geom geometry; next_sequence int =p_start_sequence; BEGIN select geom from ' . $output_pipes . ' where dc_id = p_start_id into next_geom; update ' . $output_pipes . ' set new_dc=next_sequence where dc_id= p_start_id; next_geoms = array_append(next_geoms,next_geom); LOOP if array_length(next_geoms,1)>0 then else EXIT; end if; last_geoms=array[]::geometry[]; last_geoms=last_geoms||next_geoms; next_geoms=array[]::geometry[]; for i IN 1..array_upper(last_geoms, 1) loop for r in select * from ' . $output_pipes . ' where st_intersects( ST_PointN(ST_LineMerge(last_geoms[i]),ST_NPoints(ST_LineMerge(last_geoms[i]))), ST_PointN(ST_LineMerge(geom),1) ) loop if ST_Equals(r.geom,last_geoms[i] ) then continue; else next_sequence = next_sequence+1; line_id = r.dc_id; line_sequence = next_sequence; update ' . $output_pipes . ' set new_dc=line_sequence where dc_id= line_id; return next; for branch_rec in select * from ' . $traceFunctionName . '(r.dc_id,line_sequence ) loop line_id = branch_rec.line_id; line_sequence = branch_rec.line_sequence; next_sequence = branch_rec.line_sequence; return next; end loop; end if; end loop; end loop; END LOOP; END ; $BODY$; ALTER FUNCTION public.' . $traceFunctionName . '(text, integer) OWNER TO postgres;');

        $find_intersect_dc = pg_query(DBCONNECT, "select a.* from $tank_tableName as b , $output_pipes as a where st_intersects(a.geom, b.geom)");

        $find_intersect_dc = pg_fetch_assoc($find_intersect_dc);

        $find_intersect_dc = $find_intersect_dc['dc_id'];

        $traceNetwork = pg_query(DBCONNECT, "select * from $traceFunctionName('$find_intersect_dc',1)");

        $reArrange = pg_query(DBCONNECT, "update $output_pipes  set new_dc = subquery.row_number from 
(select ROW_NUMBER () OVER (ORDER BY a.new_dc::int), a.dc_id, a.new_dc::int
 from $output_pipes  as a) as subquery where $output_pipes .dc_id = subquery.dc_id");

        $traceJunctions = pg_query(DBCONNECT, "update $output_junctions set new_dc = subquery.row_number from (select ROW_NUMBER () OVER (ORDER BY a.new_dc::int), a.dc_id, a.new_dc::int, b.dc_id as j_id from $output_pipes as a, $output_junctions as b where st_intersects(ST_EndPoint(a.geom), b.geom)) as subquery where $output_junctions.dc_id = subquery.j_id;");

        $dropFunction = pg_query(DBCONNECT, "DROP FUNCTION $traceFunctionName");

        //$delete_topo_query = pg_query(DBCONNECT, "SELECT topology.DropTopology('$topo_name')");
        //$delete_topo_query = pg_query(DBCONNECT, "Drop table $analysisTable");
        

        $this->addToGeoserver($output_pipes);
        $this->addToGeoserver($output_junctions);
        $this->addToGeoserver($tank_tableName);

        return array(
            "status" => true,
            "upload_message" => "pipes and junctions created sucessfull",
            "input_table" => $tableName,
            "pipes_data" => $output_pipes,
            "junctions_data" => $output_junctions,
            "tank_tableName" => $tank_tableName,
            "traceFunctionName" => $traceFunctionName
        );
    }

    function unzip_file($file, $destination)
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true)
        {
            return false;
        }
        $zip->extractTo($destination);
        $zip->close();
        return true;
    }

    function addLayerIndex($tableName, $layerName)
    {
        $uid = $this->GUID();
        $addLayer = pg_query(DBCONNECT, "insert into layers (id, layer_name, table_name) values ('$uid', '$layerName', '$tableName')");
    }

    function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid() , '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535) , mt_rand(0, 65535) , mt_rand(0, 65535) , mt_rand(16384, 20479) , mt_rand(32768, 49151) , mt_rand(0, 65535) , mt_rand(0, 65535) , mt_rand(0, 65535));
    }

    function addToGeoserver($layerName)
    {

        exec('curl -v -u ' . GSUSER . ':' . GSPASS . ' -XPOST -H "Content-type: text/xml" -d "<featureType><name>' . $layerName . '</name></featureType>" ' . GSURL . '/rest/workspaces/' . WORKSPACE . '/datastores/' . DATASTORE . '/featuretypes', $output, $return);

    }

}

?>
