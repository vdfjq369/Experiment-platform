<?php
//include "load_table.php";
include "total_travel_time.php";
//echo "test <br \>";
function PredictTraffic($path_num, $is_real, $driverSpeedDiff)
{
	global $predict_date_and_time;
	
	$total_travel_time=0;
	//$driver_travel_time = array();
	
	$shortest_path = load_table_shortest_path_from_RC($path_num);
	$sensor_search_on = load_table_sensor_search($path_num);
	$sensor_inter = load_table_sensor_on_routing($path_num);
	$light = load_table_traffic_light_time_plan($path_num);

	$total_travel_time = total_travel_time
				(
					$shortest_path,
					$sensor_search_on,
					$sensor_inter,
					$light,
					$predict_date_and_time, 
					$is_real, 
					$driverSpeedDiff 
				);
				
	$total_travel_time = $total_travel_time/60; //(單位 : min)
		
	return $total_travel_time;
	
}


//載入table_shortest_path.txt
function load_table_shortest_path_from_RC($path_num)
{
	//chdir("RouteConstructor");
	
	$path_file = fopen("RouteConstructor/path".$path_num."/table_shortest_path_".$path_num.".txt", "r") or die("Unable to open file!");
	
	$sec_num = 0;
	
	while(!feof($path_file))  //$path_file= X,Y,road_name 
	{	
	
		$path_data = explode(",", fgets($path_file)); 
		
		$shortest_path[$sec_num]['X'] =  $path_data[0];
		$shortest_path[$sec_num]['Y'] =  $path_data[1];
		$shortest_path[$sec_num]['road_name'] =  $path_data[2];
		$shortest_path[$sec_num]['type'] =  $path_data[3];
		$sec_num++;
	}   
	
	fclose($path_file);
	return $shortest_path;
	
}

//載入table_traffic_light_time_plan.txt
function load_table_traffic_light_time_plan($path_num)
{
	
	//global $light; //儲存路徑上的light資料
	$light_file = fopen("RouteConstructor/path".$path_num."/table_traffic_light_time_plan_".$path_num.".txt", "r") or die("Unable to open file!");
	
	
	$sec_num = 0;
	
	while(!feof($light_file))  //$light_file= X,Y,timing_plan_StartTime,period,timeDifference,first_phase_TotalTime,redlight_reserve_time
	{	
	
		$light_data = explode(",", fgets($light_file)); 
		
		$sec_num++;
		
		$light[$sec_num]['X'] =  $light_data[0];
		$light[$sec_num]['Y'] =  $light_data[1];
		$light[$sec_num]['timing_plan_StartTime'] =  $light_data[2];
		$light[$sec_num]['period'] =  $light_data[3];
		$light[$sec_num]['timeDifference'] =  $light_data[4];
		$light[$sec_num]['first_phase_TotalTime'] =  $light_data[5];
		$light[$sec_num]['redlight_reserve_time'] =  $light_data[6];
		
	}   
	
	fclose($light_file);
	//print_r($light);
	return $light;
}

//載入table_sensor_search.txt
function load_table_sensor_search($path_num)
{
	
	//global $sensor_search_on;
	$sensor_search_file = fopen("RouteConstructor/path".$path_num."/table_sensor_search_".$path_num.".txt", "r") or die("Unable to open file!");
	
	while(!feof($sensor_search_file))  //$light_file= X,Y,timing_plan_StartTime,period,timeDifference,first_phase_TotalTime,redlight_reserve_time
	{	
	
		$sensor_search_data = explode(",", fgets($sensor_search_file));
		
		$sensor_search_on[$sensor_search_data[0]][$sensor_search_data[1]] = "$sensor_search_data[2]";
		
	}
	
	fclose($sensor_search_file);
	//print_r($sensor_search_on);
	return $sensor_search_on;
}

//載入table_sensor_on_routing.txt, last_speed以資料庫有的最新資料為主 
//為了內插法使用
function load_table_sensor_on_routing($path_num)
{
	
	//global $sensor_inter;//儲存路徑上的sensor資料
	$sensor_file = fopen("RouteConstructor/path".$path_num."/table_sensor_on_routing_".$path_num.".txt", "r") or die("Unable to open file!");
	 
	$sensor_num = 0;
	
	while(!feof($sensor_file))  //$sensor_file= sensorX,sensorY,sensorID,last_speed,next_sensorX,next_sensorY
	{	
		
		// $sensor_data[0]=sensorX; $sensor_data[1]=sensorY; $sensor_data[2]=sensorID; $sensor_data[3]=last_speed;
		$sensor_data = explode(",", fgets($sensor_file)); 
		
		$sensor_num++;
		
		$sensor_inter[$sensor_num]['X'] =  $sensor_data[0];
		$sensor_inter[$sensor_num]['Y'] =  $sensor_data[1];
		$sensor_inter[$sensor_num]['sensorID'] =  $sensor_data[2];
		$sensor_inter[$sensor_num]['last_speed'] =  $sensor_data[3];
		
	}   
	
	fclose($sensor_file);
	//print_r($sensor);
	return $sensor_inter;
	
}


?>
