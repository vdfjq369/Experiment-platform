<?php

/*$arrivalTime;//車輛抵達時間點
$timing_plan_StartTime;//時制開始的時間點
$period;//時制週期
$timeDifference;//時制週期時差
$first_phase_TotalTime;//可通行秒數(預設第一個時相為綠燈)
$redlight_reserve_time;//紅燈預留秒數(預留秒數過後換另一方向通行)
$carNum_in_one_sec;//每秒來的車輛總數(Lambda)
$leaveTime_of_one_car;//每台車輛開走的時間*/

function departTime_after_light_waiting($arrivalTime, $max_queueing_length, $timing_plan_StartTime, $period, $timeDifference, $first_phase_TotalTime, $redlight_reserve_time)
{
	global $experiment_result_detail;
	global $predict_date_and_time;//從main.php取得
	global $regenerate_poisson_file;
		
	//for simulation*******  
	$carNum_in_one_sec = 0.15;//每秒累積車輛數  
	
	$leaveTime_of_one_car = 1.5; //每輛駛離時間(單位:s)
	$lb = $leaveTime_of_one_car - 0.5;
	$ub = $leaveTime_of_one_car + 0.5;
	$averge_car_length = 4.5; //單位:公尺
	
	$split_timing_plan_StartTime = explode(":",$timing_plan_StartTime);
	$split_date = explode("-", date('Y-m-d' , $predict_date_and_time));
	$timing_plan_StartTime = mktime($split_timing_plan_StartTime[0], $split_timing_plan_StartTime[1], $split_timing_plan_StartTime[2], $split_date[1], $split_date[2], $split_date[0]);
	
	
	$first_phase_StartTime = $timing_plan_StartTime + $timeDifference; //$first_phase_StartTime:開始可以通行的時間點(綠燈開始時間點)
	
	if( $arrivalTime < $first_phase_StartTime) //如果到達時間落在時差範圍內，必定為紅燈
	{
	
		$accumulate_car = ($arrivalTime - $timing_plan_StartTime) * $carNum_in_one_sec;
		
		$accumulate_car = stats_rand_gen_ipoisson($accumulate_car);
		
		$regenerate_time = 0;
		
		while( ($accumulate_car*$averge_car_length) > $max_queueing_length )
		{
			$regenerate_time++;
			$accumulate_car = stats_rand_gen_ipoisson($accumulate_car);
			//$accumulate_car =  $max_queueing_length/$averge_car_length;
		}
		fwrite($regenerate_poisson_file, $regenerate_time.",");
		
		//for simulation******** 
		$total_leave_time = 0; 
		for($car_num = 0; $car_num < $accumulate_car; $car_num++)
		{
			
			//$leaveTime_of_one_car_random = stats_rand_gen_funiform($lb, $ub);
			$leaveTime_of_one_car_random = stats_rand_gen_exponential($leaveTime_of_one_car);
			$total_leave_time = $total_leave_time + $leaveTime_of_one_car_random;
		}
		$departTime = $arrivalTime + ($first_phase_StartTime - $arrivalTime) + $total_leave_time;
		
		$tPass = $arrivalTime - $timing_plan_StartTime;
		$tLeft = $first_phase_StartTime - $arrivalTime;
		$tTotal = ($first_phase_StartTime - $arrivalTime) + $total_leave_time;
		//fwrite($experiment_result_detail, ",".$accumulate_car.",".$tPass.",".$tLeft.",".$tTotal);
		
		return $departTime;
		
	}
	
	$time_in_this_period = ($arrivalTime-$first_phase_StartTime) % $period; 
	$able_pass_TotalTime = $first_phase_TotalTime - $redlight_reserve_time;
	
	if( $time_in_this_period > $able_pass_TotalTime ) //到達時間為紅燈
	{
		
		$accumulate_car =  ($time_in_this_period - $able_pass_TotalTime) * $carNum_in_one_sec;
		$accumulate_car = stats_rand_gen_ipoisson($accumulate_car);
		
		$regenerate_time = 0;
		while ( ($accumulate_car*$averge_car_length) > $max_queueing_length )
		{
			$regenerate_time++;
			$accumulate_car = stats_rand_gen_ipoisson($accumulate_car);
			//$accumulate_car =  $max_queueing_length/$averge_car_length;
			
		}
		fwrite($regenerate_poisson_file, $regenerate_time.",");
		
		//for simulation********
		$total_leave_time = 0;
		for($car_num = 0; $car_num < $accumulate_car; $car_num++)
		{
			//$leaveTime_of_one_car_random = stats_rand_gen_funiform($lb, $ub);
			$leaveTime_of_one_car_random = stats_rand_gen_exponential($leaveTime_of_one_car);
			$total_leave_time = $total_leave_time + $leaveTime_of_one_car_random;
		}
		$departTime = $arrivalTime + ($period - $time_in_this_period) + $total_leave_time;
		
		$tPass = $time_in_this_period - $able_pass_TotalTime;
		$tLeft = $period - $time_in_this_period;
		$tTotal = ($period - $time_in_this_period) + $total_leave_time;
		//fwrite($experiment_result_detail, ",".$accumulate_car.",".$tPass.",".$tLeft.",".$tTotal);
	}
	else //到達時間為綠燈
	{ 
		$departTime = $arrivalTime;
		//echo "green light!<br \>";
		//fwrite($experiment_result_detail, ",green,green,green,green"); 
	}
	
	return $departTime;
	
}


?>