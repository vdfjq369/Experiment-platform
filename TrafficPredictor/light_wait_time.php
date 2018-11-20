<?php

/*$arrivalTime;//������F�ɶ��I
$timing_plan_StartTime;//�ɨ�}�l���ɶ��I
$period;//�ɨ�g��
$timeDifference;//�ɨ�g���ɮt
$first_phase_TotalTime;//�i�q����(�w�]�Ĥ@�Ӯɬ۬���O)
$redlight_reserve_time;//���O�w�d���(�w�d��ƹL�ᴫ�t�@��V�q��)
$carNum_in_one_sec;//�C��Ӫ������`��(Lambda)
$leaveTime_of_one_car;//�C�x�����}�����ɶ�*/

function departTime_after_light_waiting($arrivalTime, $max_queueing_length, $timing_plan_StartTime, $period, $timeDifference, $first_phase_TotalTime, $redlight_reserve_time)
{
	global $experiment_result_detail;
	global $predict_date_and_time;//�qmain.php���o
	global $regenerate_poisson_file;
		
	//for simulation*******  
	$carNum_in_one_sec = 0.15;//�C��ֿn������  
	
	$leaveTime_of_one_car = 1.5; //�C���p���ɶ�(���:s)
	$lb = $leaveTime_of_one_car - 0.5;
	$ub = $leaveTime_of_one_car + 0.5;
	$averge_car_length = 4.5; //���:����
	
	$split_timing_plan_StartTime = explode(":",$timing_plan_StartTime);
	$split_date = explode("-", date('Y-m-d' , $predict_date_and_time));
	$timing_plan_StartTime = mktime($split_timing_plan_StartTime[0], $split_timing_plan_StartTime[1], $split_timing_plan_StartTime[2], $split_date[1], $split_date[2], $split_date[0]);
	
	
	$first_phase_StartTime = $timing_plan_StartTime + $timeDifference; //$first_phase_StartTime:�}�l�i�H�q�檺�ɶ��I(��O�}�l�ɶ��I)
	
	if( $arrivalTime < $first_phase_StartTime) //�p�G��F�ɶ����b�ɮt�d�򤺡A���w�����O
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
	
	if( $time_in_this_period > $able_pass_TotalTime ) //��F�ɶ������O
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
	else //��F�ɶ�����O
	{ 
		$departTime = $arrivalTime;
		//echo "green light!<br \>";
		//fwrite($experiment_result_detail, ",green,green,green,green"); 
	}
	
	return $departTime;
	
}


?>