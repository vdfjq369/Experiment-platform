<?php
include "sensor_search.php";
$sensors_On_Map = array();

//ConstructRoute(121.511421, 25.047538, 121.615282, 25.053026, 3);
function ConstructRoute($origX, $origY, $destX, $destY, $num_route)
{
	global $predict_date_and_time;
	
	chdir("RouteConstructor");
	
	load_sensors_in_capital();
	
	/*exec('g++ A_star_v4.cpp -o AStar 2>&1',$error_msg,$return_val);
	if($return_val !== 0) { 
		echo 'Error<br>';
		print_r($error_msg);   
	}*/
	
	$input = "AStar ".$origX." ".$origY." ".$destX." ".$destY." ".$num_route;//generate route
	$output = shell_exec($input)or die("fail");;
	echo $output."<br \>";
	
	for($i=0; $i<$num_route; $i++)
	{
		$shortest_path = load_table_shortest_path($i);
		create_table_sensor_list_over_route($i,$shortest_path,$predict_date_and_time);
	}
	chdir("..");
}

//載入地圖上所有sensor資料
function load_sensors_in_capital()
{
	global $sensors_On_Map;
	$sensor_file = fopen("sensors_in_capital.txt", "r") or die("Unable to open file!");
	
	$sensor = 0;
	
	while(!feof($sensor_file))  //$path_file= X,Y,road_name 
	{	
	
		$sensor_data = explode(",", fgets($sensor_file)); 
		
		$sensors_On_Map[$sensor]['ID'] =  $sensor_data[0];
		$sensors_On_Map[$sensor]['sX'] =  $sensor_data[2];
		$sensors_On_Map[$sensor]['sY'] =  $sensor_data[3];
		$sensors_On_Map[$sensor]['eX'] =  $sensor_data[4];
		$sensors_On_Map[$sensor]['eY'] =  $sensor_data[5];
		$sensor++;
	}   
	
	fclose($sensor_file);
	
}


?> 