<?php
ignore_user_abort(true);//關掉瀏覽器，PHP腳本也可以繼續執行. 
//set_time_limit(0);// 通過set_time_limit(0)可以讓程序無限制的執行下去
date_default_timezone_set('Asia/Taipei'); //設定時區
include "RouteConstructor/RC_main.php";
include "TrafficPredictor/TP_main.php";
include "GoogleMapQuerier/GMQ_main.php";
  
if(array_key_exists('run',$_GET)) 
{	
	chdir("vanet");

	$now_date_and_time = time();//目前的時間      
	//$cur_date_and_time = mktime(7, 00, 25, 6, 29, 2016);//假想目前的時間，必須小於等於$now_date_and_time(只能設為過去或現在,不能是未來時間)
	$predict_date = explode("-", $_GET['DepartureDate']);
	
	$predict_date_and_time = mktime($_GET['DepartureHour'], $_GET['DepartureMinute'], 25, $predict_date[1], $predict_date[2], $predict_date[0]);//想要預測的出發時間
	/*$originX = $_GET['originX'];
	$originY = $_GET['originY'];
	$destinationX = $_GET['destinationX'];
	$destinationY = $_GET['destinationY'];*/
	$num_route = 3;
	$driver_num=100;
	$experiment_result_detail=0;
	$regenerate_poisson_file=0;
	main(); //execute main function*/
}



function main()
{
	global $now_date_and_time;
	//global $cur_date_and_time;
	global $predict_date_and_time; 
	/*global $originX; 
	global $originY;
	global $destinationX;
	global $destinationY;*/
	global $num_route;
	global $driver_num;
	global $experiment_result_detail;
	global $regenerate_poisson_file; 
	
	$predicted_value = array();
	$actual_value = array();
	$min_cost = 0;
	$min_cost_route = 0;
	

	//RC
	//ConstructRoute($originX, $originY, $destinationX, $destinationY, $num_route);//the route results are in directory "RouteConstructor"
	//TP
	for($i=0; $i<$num_route; $i++)
	{
		$experiment_result = fopen("experiment_result/experiment_result_path_".$i.".txt", "w") or die("Unable to open file!");
		$experiment_result_detail = fopen("experiment_result/experiment_result_detail_path_".$i.".txt", "w") or die("Unable to open file!");
		$regenerate_poisson_file = fopen("experiment_result/regenerate_poisson_path_".$i.".txt", "w") or die("Unable to open file!");
	
		for($j=0; $j<$driver_num; $j++)
		{
			
			$driverSpeedDiff = stats_rand_gen_funiform(0.8, 1.2); 
			 
			$predicted_travel_time = PredictTraffic($i, 0, $driverSpeedDiff); //PredictTraffic($path_num, $is_real, $driverSpeedDiff)
			$actual_travel_time = PredictTraffic($i, 1, $driverSpeedDiff); //PredictTraffic($path_num, $is_real)
			
			$predicted_value[$j] = $predicted_travel_time ;
			$actual_value[$j] = $actual_travel_time;
			
			//fwrite($experiment_result,$predicted_value[$j].",".$actual_value[$j]."\n");			
			//fwrite($regenerate_poisson_file,"\n");				
		}
		
		$avg_predicted_value = array_sum($predicted_value)/$driver_num;
		$avg_actual_value = array_sum($actual_value)/$driver_num;
		$dev_predicted_value = stats_standard_deviation($predicted_value,true);
		$dev_actual_value = stats_standard_deviation($actual_value,true);
		
		fwrite($experiment_result,"-----------Average------------\n");
		fwrite($experiment_result,"predicted= ".$avg_predicted_value.",actual= ".$avg_actual_value."\n");
		fwrite($experiment_result,"-----------Deviation------------\n");
		fwrite($experiment_result,"predicted= ".$dev_predicted_value.",actual= ".$dev_actual_value."\n");
		 
		fclose($experiment_result);
		fclose($experiment_result_detail);
		fclose($regenerate_poisson_file);
		unset($predicted_value);
		unset($actual_value);
		

	}
		
	//echo "predicted total_travel_time:".$total_travel_time."<br \>";
			/*if($i==0)
			{ 
				$min_cost = $total_travel_time;
				$min_cost_route = $i;
			}
			else
			{
				if($total_travel_time < $min_cost)
				{
					$min_cost = $total_travel_time;
					$min_cost_route = $i;
				}
			}*/

	
		
	//echo "min: path".$min_cost_route."= ".$min_cost."<br \>";
		
		
	/*if($predict_date_and_time < $now_date_and_time) //驗證過去(預測值和歷史資料比較)
	{
		$actual_travel_time = PredictTraffic($min_cost_route, 1); //PredictTraffic($path_num, $is_real)
		echo "actual= ".$actual_travel_time."<br \>";
	}
	if($predict_date_and_time >= $now_date_and_time) // 預測現在及未來(預測值和google比較)
	{
		
		get_travel_time_from_google($min_cost_route);
		
	}*/
	//else echo "cur_date_and_tim can't greater than now_date_and_time!<br \>";
	
}




?>