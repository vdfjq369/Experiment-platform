<?php

include "speed_prediction.php";
include "light_wait_time.php";



//計算整個路徑所需時間
function total_travel_time($shortest_path, $sensor_search_on, $sensor_inter, $light, $predict_date_and_time, $is_real, $driverSpeedDiff)
{

	global $now_date_and_time;
	global $now_date_and_time;
	
	global $experiment_result_detail;
	global $regenerate_poisson_file;
	
	$section_departTime = $section_arrivalTime = $predict_date_and_time; //first section 預設不用等紅綠燈
	$section_predict_speed = 40;
	$last_sensor_num = 0;//初始化目前剛經過的sensor編號為0 
	$total_distance = 0;
	
	
	for($i=0; $i<count($shortest_path)-1; $i++)
	{
		
		$secX = $shortest_path[$i]['X'];
		$secY = $shortest_path[$i]['Y'];
		$next_sec_num = $i+1;
		$next_secX = $shortest_path[$next_sec_num]['X'];
		$next_secY = $shortest_path[$next_sec_num]['Y'];
		
		$section_distance = distVincenty($secY, $secX, $next_secY , $next_secX);// 一公尺 = 座標0.00001度
		//echo "distance = $section_distance<br \>";
		
		
		if( $shortest_path[$i]['type'] != "TurningPoint" ) // i 不可能為 0 ，因為i=0是路徑起始點，不可能是TurningPoint
		{
		
			//找該section 是否有sensor
		
			$sensorID = $sensor_search_on[$secX][$secY];
			
			if($sensorID != "no sensor") 
			{
				//echo $sensorID." ";
				$last_sensor_num++;
				
				if($i == 0)//如果起點有 sensor
				{
					//$last_time_speed, $last_section_speed 從資料庫取
					echo "orginal depart coordinate has sensor, please wait for historical data getting...<br \>";
					
					$last_time_speed = $sensor_inter[$last_sensor_num]['last_speed'];
					$last_section_speed = 40;//預設出發點前一個路段的速度為40
					
					if($last_time_speed == 0 || $last_time_speed == "")  
						
						echo "Original depart point has sensor, but there's no last time speed in database.<br \>";
						
					else{
					
						if($predict_date_and_time < $now_date_and_time)//預測現在就要出發當下的路況
						{
							echo "test <br \>";
							//抓$now_date_and_time的速度資料 如果now=10:13:00 抓10:05:00資料
							if($is_real == 0)
								$section_predict_speed = $last_time_speed;
							
							else//抓真實資料
								$section_predict_speed = fetch_real_speed($sensorID, $section_departTime);
							echo "$section_predict_speed <br \>";
						}
						
						else if($predict_date_and_time >= $now_date_and_time)//預測未來某個時間點才要出發當下的路況
						{
							
							if($is_real == 0) 
							{
								$date_and_time_tmp = $now_date_and_time;
								
								$total_rounds = floor( ($predict_date_and_time - $now_date_and_time) / 300 ); //資料庫以五分鐘(300s)為一個單位
								if($total_rounds == 0 ) $total_rounds++;
								
								echo "total rounds = $total_rounds<br \>";
								
								for($round = 0; $round < $total_rounds; $round++)
								{
									$section_predict_speed = prediction_model($sensorID, $date_and_time_tmp, $last_time_speed, $last_section_speed);							
									$last_time_speed = $section_predict_speed;
									$last_section_speed = $section_predict_speed; 
									$date_and_time_tmp = $date_and_time_tmp + 300; //資料庫以五分鐘(300s)為一個單位
									
								}
							}
							else //抓真實資料
								$section_predict_speed = fetch_real_speed($sensorID, $section_departTime);
						
						}
					
					}
				
				}
				else//在非起點的路口有sensor
				{
					
					if($is_real == 0)
						$section_predict_speed = prediction_model($sensorID, $section_departTime, $last_time_speed, $last_section_speed);
					else
						$section_predict_speed = fetch_real_speed($sensorID, $section_departTime);	
					
				}
					
			} 
			else
			{ 
				
				$section_predict_speed = interpolation($secX, $secY, $last_sensor_num, $sensor_inter); 
				echo "interpolation = $section_predict_speed <br \>";
			}
			
			
			if($section_predict_speed == 0) 
			{
				/*fwrite($experiment_result_detail, ","."Speed is zero and system can't not compute travel time\n");
				break;*/
				$section_predict_speed = $last_time_speed;
			}
		
			//for simulation*********
			$section_predict_speed = $section_predict_speed * $driverSpeedDiff;
		
			//save section speed of the this time and this road section for next section speed prediction
			$last_time_speed = $section_predict_speed;
			$last_section_speed = $section_predict_speed; 
		
			
			
			
			//單位:(km/hr) 轉 (m/s)
			$section_predict_speed = $section_predict_speed*1000/3600;//(單位: m/s)
			echo "section_predict_speed=".$section_predict_speed."<br \>";
		
			//road section i 出發時間 
			if($i > 0)
			{
				
				$section_departTime = departTime_after_light_waiting
				(
					
					$section_arrivalTime,
					$distance_behind_intersection,
					$light[$i]['timing_plan_StartTime'], 
					$light[$i]['period'], 
					$light[$i]['timeDifference'], 
					$light[$i]['first_phase_TotalTime'], 
					$light[$i]['redlight_reserve_time']
				);
				
				
			} 
			
			//$section_departTime = $section_arrivalTime;//如果要等紅綠燈，此行要標註起來
			
			//echo "section depart at".date("Y-m-d H:i:s", $section_departTime)."<br \>";
			//road section i 抵達時間 
			$distance_behind_intersection = $section_distance;	
		}
		else 
		{
			if($i==0) $distance_behind_intersection = 0;
			
			$section_departTime = $section_arrivalTime;//如果是TurningPoint不做任何速度及紅燈時間計算
			$distance_behind_intersection = $distance_behind_intersection + $section_distance;
		} 
		
		//fwrite($experiment_result_detail, ($section_predict_speed/1000*3600).",".($section_departTime - $section_arrivalTime).",");
	
		//$total_distance = $total_distance + $section_distance;
		echo "section_predict_speed=".$section_predict_speed."<br \>";
		$section_arrivalTime = $section_departTime + $section_distance / $section_predict_speed;
	
		//echo "section arrive at".date("Y-m-d H:i:s", $section_arrivalTime)."<br \>";
		//echo "=============================================<br \>";
		
	}
	//echo "total_distance: $total_distance<br \>";
	$final_arrivalTime = $section_arrivalTime;
	$total_travel_time = $final_arrivalTime - $predict_date_and_time; 
	//fwrite($experiment_result_detail,"\n");
	fwrite($regenerate_poisson_file,"\n");
	return $total_travel_time; 
}

//interpolation method 內插法
function interpolation($secX, $secY, $last_sensor_num, $sensor_inter)
{
	
	global $shortest_path;
	//global $sensor;
	$next_sensor_num = $last_sensor_num + 1;
	
	if($last_sensor_num == 0)// first section has no sensor, set default speed of first section is 40
	{
		
		$last_sensor_speed = 40;
		$next_sensor_speed = $sensor_inter[$next_sensor_num]['last_speed'];
		$last_sensorX = $shortest_path[0]['X'];
		$last_sensorY = $shortest_path[0]['Y'];
		$next_sensorX = $sensor_inter[$next_sensor_num]['X'];
		$next_sensorY = $sensor_inter[$next_sensor_num]['Y'];
		
	}	
	else if($last_sensor_num == count($sensor_inter)) //the leaved section are no sensor, set destination speed is 0
	{
		
		$last_sensor_speed = $sensor_inter[$last_sensor_num]['last_speed'];
		$next_sensor_speed = 40;
		$last_sensorX = $sensor_inter[$last_sensor_num]['X'];
		$last_sensorY = $sensor_inter[$last_sensor_num]['Y'];
		$next_sensorX = $shortest_path[count($shortest_path)-1]['X'];
		$next_sensorY  = $shortest_path[count($shortest_path)-1]['Y'];
		
	}
	else
	{
		
		$last_sensor_speed = $sensor_inter[$last_sensor_num]['last_speed'];
		$next_sensor_speed = $sensor_inter[$next_sensor_num]['last_speed'];
		$last_sensorX = $sensor_inter[$last_sensor_num]['X'];
		$last_sensorY = $sensor_inter[$last_sensor_num]['Y'];
		$next_sensorX = $sensor_inter[$next_sensor_num]['X'];
		$next_sensorY  = $sensor_inter[$next_sensor_num]['Y'];
		
	}
	
	$sensor_difference = $next_sensor_speed - $last_sensor_speed;
	
	//計算兩個sensor之間的距離
	/*
	$dis_hor_between_two_sensors = ($next_sensorX -  $last_sensorX) / 0.00000900900901;
	$dis_ver_between_two_sensors = ($next_sensorY -  $last_sensorY) / 0.00000900900901;
	$distance_between_two_sensors = sqrt( $dis_hor_between_two_sensors*$dis_hor_between_two_sensors + $dis_ver_between_two_sensors*$dis_ver_between_two_sensors );
	*/
	$distance_between_two_sensors = distVincenty($last_sensorY, $last_sensorX, $next_sensorY, $next_sensorX);
	
	//計算現在路口離前一個sensor的距離
	/*
	$dis_hor_from_last_sensor = ($secX - $last_sensorX) / 0.00000900900901;
	$dis_ver_from_last_sensor = ($secY - $last_sensorY) / 0.00000900900901;
	$distance_from_last_sensor = sqrt( $dis_hor_from_last_sensor*$dis_hor_from_last_sensor + $dis_ver_from_last_sensor*$dis_ver_from_last_sensor );
	*/
	$distance_from_last_sensor = distVincenty($last_sensorY, $last_sensorX, $secY, $secX);
	
	$predict_speed = $last_sensor_speed + $sensor_difference * ($distance_from_last_sensor / $distance_between_two_sensors);
	 
	return $predict_speed;
	
}

function fetch_real_speed($sensorID, $section_departTime)
{
	
	global $now_date_and_time;
	
	
	if( $section_departTime >= $now_date_and_time) 
	{
		echo "The past sec_depart_time exceeds now_date_and_time, you need to set cur_date_and_time more time from now.<br \>";
		return 0;
	}
	$sec_depart_date =  date('Y-m-d', $section_departTime);
	$sec_depart_time = date('H:i:s', $section_departTime);
	$split_date = explode("-", $sec_depart_date);
	$split_time = explode(":", $sec_depart_time);
	
	$time_of_nowTime = date('H:i:s', mktime($split_time[0], $split_time[1]-($split_time[1]%5), 25, $split_date[1], $split_date[2], $split_date[0]));
	//echo "fetch: ".$time_of_nowTime."<br \>";
	//mysql連線
	$link = mysqli_connect("localhost", "root", "eve1019");
	/*if($link){echo "connect successful!!<br>";}
			else{echo"connect failed";}*/

	$link_db=mysqli_select_db($link, "transportation"); 
	/*if($link_db){echo "table successful!!<br>";}
		else{echo"failed";}*/
	mysqli_query($link, "SET NAMES utf8");
	
	// exam whether there exist table "$sensorID"
	$query = "show tables like '$sensorID'";
	
	mysqli_query($link, $query) or die('query connect data fail');	
	$result1 = mysqli_query($link,$query) or die("無法送出" . mysqli_error($link)); 
	
	$table_is_exist = mysqli_num_rows($result1);
	
	if($table_is_exist == 0) return 0;
	
	$query = "select avgSpeed from $sensorID where date='$sec_depart_date' and time='$time_of_nowTime'";
	
	mysqli_query($link, $query) or die('query connect data fail');	
	$result = mysqli_query($link,$query) or die("無法送出" . mysqli_error($link)); 
	$row = mysqli_fetch_array( $result );
	
	$real_speed = $row[0];
	//echo "actual: ".$real_speed."<br \>";
	mysqli_free_result($result);
	mysqli_close($link);
	
	return $real_speed;
	
}

//Vincenty距離計算
function distVincenty($lat1, $long1, $lat2, $long2) {
    $a = 6378137;
    $b = 6356752.314245;
    $f = 1/298.257223563;
    $L = deg2rad($long2 - $long1);
    $U1 = atan((1 - $f) * tan(deg2rad($lat1)));
    $U2 = atan((1 - $f) * tan(deg2rad($lat2)));
    $sinU1 = sin($U1);
    $cosU1 = cos($U1);
    $sinU2 = sin($U2);
    $cosU2 = cos($U2);
    $lambda = $L;
    $iterLimit = 100;
    do {
        $sinLambda = sin($lambda);
        $cosLambda = cos($lambda);
        $sinSigma = sqrt(($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) + ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) * ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda));
        if( $sinSigma == 0 ) {
            return 0;
        }
        $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
        $sigma = atan2($sinSigma, $cosSigma);
        $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
        $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
        if( $cosSqAlpha == 0 ) {
            $cos2SigmaM = 0;
        } else {
            $cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
        }
        $C = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
        $lambdaP = $lambda;
        $lambda = $L + (1 - $C) * $f * $sinAlpha * ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
    } while(abs($lambda - $lambdaP) > 0.000000000001 && --$iterLimit > 0);
    if( $iterLimit == 0 ) {
        return 0;
    }
    $uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
    $A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
    $B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
    $deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) - $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));
    return $b * $A * ($sigma - $deltaSigma);
}


?>