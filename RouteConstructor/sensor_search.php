<?php
include "sensor_speed_at_departure.php";
//$shortest_path = array();
//$sensors_On_Map = array();
//$senor_on_intersection = array();

/*$now_date_and_time = time();//目前的時間      
$cur_date_and_time = mktime(7, 00, 30, 6, 29, 2016);//假想目前的時間，必須小於等於$now_date_and_time(只能設為過去或現在,不能是未來時間)
$predict_date_and_time = mktime(7, 00, 30, 6, 29, 2016);//想要預測的出發時間*/

//create_table_sensor(0,$predict_date_and_time);

function create_table_sensor_list_over_route($path_num, $shortest_path, $depart_time)
{

	$on_routing_file = fopen("path".$path_num."/table_sensor_on_routing_".$path_num.".txt", "w") or die("Unable to open file!");
	
	$sensor_on_routing = create_table_sensor_search($path_num,$shortest_path);
	
	$num_sensor_on_routing = count($sensor_on_routing);
	for($i=0; $i<$num_sensor_on_routing; $i++)
	{
		$seg_X = $sensor_on_routing[$i]['X'];
		$seg_Y = $sensor_on_routing[$i]['Y'];
		$senID = $sensor_on_routing[$i]['sensorID'];
		$speed_at_departure=0;
		$speed_at_departure = fetch_speed_at_departure($senID,$depart_time);
		if($speed_at_departure==0) $speed_at_departure=40;
		if($i == $num_sensor_on_routing-1)
			fwrite($on_routing_file,$seg_X.",".$seg_Y.",".$senID.",".$speed_at_departure);
		else
			fwrite($on_routing_file,$seg_X.",".$seg_Y.",".$senID.",".$speed_at_departure."\n");
		
	}
	
	fclose($on_routing_file);
}

function create_table_sensor_search($path_num,$shortest_path)
{
	//global $shortest_path;
	global $sensors_On_Map;
	$sensor_on_routing = array();
 	
	
	$senor_on_intersection = find_sensor($shortest_path);
	
	$search_file = fopen("path".$path_num."/table_sensor_search_".$path_num.".txt", "w") or die("Unable to open file!");
	
	$num_route_points = count($shortest_path);
	$index_has_sensor = 0;
    //print_r($shortest_path);
	for($i=0; $i<$num_route_points-1; $i++) //終點不用判斷是否有sensor
	{
		$seg_X = $shortest_path[$i]['X'];
		$seg_Y = $shortest_path[$i]['Y'];
		if($i!=$num_route_points-2)
		{
			
			fwrite($search_file,$seg_X.",".$seg_Y.",".$senor_on_intersection[$seg_X][$seg_Y].",\n");
			
			if($senor_on_intersection[$seg_X][$seg_Y] != "no sensor") //把有sensor的資料存下來
			{
				$sensor_on_routing[$index_has_sensor]['X']=$seg_X;
				$sensor_on_routing[$index_has_sensor]['Y']=$seg_Y;
				$sensor_on_routing[$index_has_sensor]['sensorID']=$senor_on_intersection[$seg_X][$seg_Y];
				$index_has_sensor++;
			}
		}
		else 
			fwrite($search_file,$seg_X.",".$seg_Y.",".$senor_on_intersection[$seg_X][$seg_Y].",");
			
	}
	
	fclose($search_file);
	
	return $sensor_on_routing;
	
}

function find_sensor($shortest_path)
{
	global $sensors_On_Map;
	$num_route_points = count($shortest_path);
	$num_total_senors = count($sensors_On_Map);
	
	$sensor_on_intersection = array();//save sensor which is on the intersection
	$i=0;
	$j=0;
	
	for($i=0; $i<$num_route_points-1; $i++)
	{
		$segX = $shortest_path[$i]['X'];
		$segY = $shortest_path[$i]['Y'];
		
		if($shortest_path[$i]['type'] == "intersection")
		{
			$find=0; //$記錄路口是否有找到過sensor
			
			//find which sensors are around $i
			for($j=0; $j<$num_total_senors; $j++)
			{
				$start_senX=$sensors_On_Map[$j]['sX'];
				$start_senY=$sensors_On_Map[$j]['sY'];
				
				$X_between = $start_senX - $segX;
				$Y_between = $start_senY - $segY;
				
				$distance_between = sqrt($X_between*$X_between + $Y_between*$Y_between)/0.00001;//單位:公尺
				
				if($distance_between < 50) //如果sensor在interseciton範圍內
				{
					//sensor infor
					$end_senX=$sensors_On_Map[$j]['eX'];
					$end_senY=$sensors_On_Map[$j]['eY'];
					//segment infor
					$next_segX = $shortest_path[$i+1]['X'];
					$next_segY = $shortest_path[$i+1]['Y'];
					
					if(is_match($start_senX,$start_senY,$end_senX,$end_senY,$segX,$segY,$next_segX,$next_segY))
					{
						
						$find=1;
						$sensor_on_intersection[$segX][$segY] = $sensors_On_Map[$j]['ID'];
						break;
					}
					//else $sensor_on_intersection[$segX][$segY] = "no sensor";
						
				}
				//else $sensor_on_intersection[$segX][$segY] = "no sensor";
				
			}
			if($find==0) $sensor_on_intersection[$segX][$segY] = "no sensor";
			
		}
		else //is TurningPoint
			$sensor_on_intersection[$segX][$segY] = "no sensor";
	}
	//destination point
	$sensor_on_intersection[$shortest_path[$i]['X']][$shortest_path[$i]['Y']] = "no sensor";
	return $sensor_on_intersection;
	
}

function is_match($start_senX,$start_senY,$end_senX,$end_senY,$segX,$segY,$next_segX,$next_segY)
{
  	$direct_sensor = intend_direction($start_senX,$start_senY,$end_senX,$end_senY);
	$direct_segment = intend_direction($segX,$segY,$next_segX,$next_segY);
	if($direct_sensor==$direct_segment)
	{
		if($direct_sensor==6 || $direct_sensor==8) //slope=infinite
		{
			return $match=1;
		}
		else
		{
			$slope_sensor = intend_slope($start_senX,$start_senY,$end_senX,$end_senY);
			$slope_segment = intend_slope($segX,$segY,$next_segX,$next_segY);
			if(abs($slope_sensor-$slope_segment) <= 0.5) return $match=1;
			else return $match=0;
		}
	}
	else
		return $match=0;
	
}

function intend_direction($X,$Y,$next_X,$next_Y)
{
	if($X-$next_X<0 && $Y-$next_Y<0) $direction=1; //往第一象限走
	if($X-$next_X>0 && $Y-$next_Y<0) $direction=2; //往第二象限走
	if($X-$next_X>0 && $Y-$next_Y>0) $direction=3; //往第三象限走
	if($X-$next_X<0 && $Y-$next_Y>0) $direction=4; //往第四象限走
	if($X-$next_X<0 && $Y-$next_Y=0) $direction=5; //往東走
	if($X-$next_X=0 && $Y-$next_Y<0) $direction=6; //往北走
	if($X-$next_X>0 && $Y-$next_Y=0) $direction=7; //往西走
	if($X-$next_X=0 && $Y-$next_Y>0) $direction=8; //往南走
	return $direction;
}

function intend_slope($X,$Y,$next_X,$next_Y)
{
		$slope=($next_Y-$Y)/($next_X-$X);
		return $slope;
}


//載入table_shortest_path.txt
function load_table_shortest_path($path_num)
{
	
	$shortest_path = array(); // 儲存path上所有node座標(包含起始點及結束點)
	$path_file = fopen("path".$path_num."/table_shortest_path_".$path_num.".txt", "r") or die("Unable to open file!");
	
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

?>