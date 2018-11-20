<?php

function prediction_model($sensorID, $section_departTime, $last_time_speed, $last_section_speed)
{
	
	//echo "$sensorID<br \>";
	$data = load_historical_data($sensorID, $section_departTime);
	if(count($data) <= 1) // Historical data is too little to predict speed at time. 
	{
		
		$predict_speed = $last_time_speed;
	
	}
	else
	{
		
		$deviation_array_of_lastTime = get_deviation_array($data, 1);
		$deviation_array_of_predictTime = get_deviation_array($data, 0);
	
		$x = get_lastTime_deviation($deviation_array_of_lastTime, $last_time_speed); //取當天 last_min 的deviaiton(公式 y=a+bx 的 "x")
		$mean = get_predictTime_his_mean($deviation_array_of_predictTime);
	
		$parameter = training_parameter($deviation_array_of_lastTime, $deviation_array_of_predictTime);
	
		$deviation_of_predict_time = $parameter[0] + $parameter[1] * $x;
	
		$predict_speed = $mean + $deviation_of_predict_time;
		
	}
	
	return $predict_speed;
	 
}

function training_parameter($deviation_array_of_lastTime, $deviation_array_of_predictTime) 
{
	
	$deviation_u = array();
	$deviation_v = array();
	$deviation_u = $deviation_array_of_lastTime;
	$deviation_v = $deviation_array_of_predictTime;
	
	$parameter = array();
	
	$data_num = count($deviation_u)- 1; //最後一筆資料是要用在公式中，不能參加training
	echo "總座標數:".$data_num."<br \>";
	
	// (u^t*u)===================================================================
	$deviation_tmp = array(); 
	$inverse = array();
	$tmp0=0;
	$tmp1=0;
	//$tmp2=0;
	$tmp3=0;
	$tmp4=0;
	//$tmp5=0;
	//$tmp6=0;
	//$tmp7=0;
	//$tmp8=0;
	for($j=0; $j<$data_num; $j++)
	{
		$tmp0 = $tmp0 + $deviation_u[$j][0]*$deviation_u[$j][0];
		$tmp1 = $tmp1 + $deviation_u[$j][0]*$deviation_u[$j][1];
		//$tmp2 = $tmp2 + $deviation_u[$j][0]*$deviation_u[$j][2];
		$tmp3 = $tmp3 + $deviation_u[$j][1]*$deviation_u[$j][0];
		$tmp4 = $tmp4 + $deviation_u[$j][1]*$deviation_u[$j][1];
		//$tmp5 = $tmp5 + $deviation_u[$j][1]*$deviation_u[$j][2];
		//$tmp6 = $tmp6 + $deviation_u[$j][2]*$deviation_u[$j][0];
		//$tmp7 = $tmp7 + $deviation_u[$j][2]*$deviation_u[$j][1];
		//$tmp8 = $tmp8 + $deviation_u[$j][2]*$deviation_u[$j][2];
				
	}
	array_push($deviation_tmp, array("$tmp0", "$tmp1"), array("$tmp3", "$tmp4"));

    // (u^t*u) inverse=======================================================
	$determine = $deviation_tmp[0][0]*$deviation_tmp[1][1] - $deviation_tmp[0][1]*$deviation_tmp[1][0];
	if($determine!=0){
		/*$tmp0 = $deviation_tmp[1][1]*$deviation_tmp[2][2] - $deviation_tmp[1][2]*$deviation_tmp[2][1];
		$tmp1 = -($deviation_tmp[1][0]*$deviation_tmp[2][2] - $deviation_tmp[1][2]*$deviation_tmp[2][0]);
		$tmp2 = $deviation_tmp[1][0]*$deviation_tmp[2][1] - $deviation_tmp[1][1]*$deviation_tmp[2][0];
		$tmp3 = -($deviation_tmp[0][1]*$deviation_tmp[2][2] - $deviation_tmp[0][2]*$deviation_tmp[2][1]);
		$tmp4 = $deviation_tmp[0][0]*$deviation_tmp[2][2] - $deviation_tmp[0][2]*$deviation_tmp[2][0];
		$tmp5 = -($deviation_tmp[0][0]*$deviation_tmp[2][1] - $deviation_tmp[0][1]*$deviation_tmp[2][0]);
		$tmp6 = $deviation_tmp[0][1]*$deviation_tmp[1][2] - $deviation_tmp[0][2]*$deviation_tmp[1][1];
		$tmp7 = -($deviation_tmp[0][0]*$deviation_tmp[1][2] - $deviation_tmp[0][2]*$deviation_tmp[1][0]);
		$tmp8 = $deviation_tmp[0][0]*$deviation_tmp[1][1] - $deviation_tmp[0][1]*$deviation_tmp[1][0];*/
		$tmp0 = $deviation_tmp[0][0];
		$tmp1 = -$deviation_tmp[0][1];
		$tmp3 = -$deviation_tmp[1][0];
		$tmp4 = $deviation_tmp[1][1];
		array_push($inverse, array("$tmp4", "$tmp1"), array("$tmp3", "$tmp0"));
		for($i=0; $i<2; $i++)
		{
			for($j=0; $j<2; $j++)
			{
				$inverse[$j][$i] = (1/$determine)*$inverse[$j][$i];
			}
		}
		
		//(u^T*u)^-1*u^T===================
		for($i=0; $i<2; $i++)
		{
			for($j=0; $j<$data_num; $j++)
			{
				$deviation_tmp[$i][$j]=0; //初始deviation_tmp為0
				for($k=0; $k<2; $k++)
				{
					$deviation_tmp[$i][$j] = $deviation_tmp[$i][$j] + $inverse[$i][$k] * $deviation_u[$j][$k];
				}	 
				
			}
		}
		
		//(u^T*u)^-1*u^T*v========================
		
		for($i=0; $i<2; $i++)
		{
			$parameter[$i]=0; //初始parameter為0
			for($j=0; $j<$data_num; $j++)
			{		
				$parameter[$i] = $parameter[$i] + $deviation_tmp[$i][$j] * $deviation_v[$j];  
			}
		}

		echo "paramater a= ".$parameter[0]."<br \>";
		echo "paramater b= ".$parameter[1]."<br \>";
		//echo "paramater c= ".$parameter[0][2]."<br \>";
	}
	else
	{
		echo "determine is zero!<br \>";
	}
	
	return($parameter);	
}

//計算每天last time 及 predict time 的 deviation
function get_deviation_array($data, $get_lastTime_dev) //$data[$i] = [date] [last_time_speed] [now_time_speed]
{
	
	$deviation_array = array();
	
	$data_num = count($data);
	
	if($get_lastTime_dev == 1) $speed_option = 1; //[last_time_speed]
	else $speed_option = 2; //[now_time_speed]
	
	for($i=0; $i<$data_num; $i++) 
	{
		
		$sum = 0;
		
		for($j=0; $j<$i+1; $j++){ //第一筆資料沒有過去平均
	
			$sum = $sum + $data[$j][$speed_option]; 
		}
		
		$avg = $sum/($i+1);	//過去平均速度
	
		if($i != $data_num-1)
		{
			
			$dev = $data[$i+1][$speed_option] - $avg; 
			if($get_lastTime_dev == 1)// last time deviation
				array_push($deviation_array, array("1", "$dev")); 
			else // predict time deviation
				array_push($deviation_array, "$dev");
		}
		else
		{
			array_push($deviation_array, "$avg");
		}
	} 

	return $deviation_array;
	
}


function get_lastTime_deviation($deviation_array_of_lastTime, $last_time_speed)
{
	
	$n = count($deviation_array_of_lastTime) - 1;
	
	return $last_time_speed - $deviation_array_of_lastTime[$n];
	
}

function get_predictTime_his_mean($deviation_array_of_predictTime)
{
	
	$n = count($deviation_array_of_predictTime) - 1;
	
	return $deviation_array_of_predictTime[$n];
	
}
//從資料庫載入 predict time 及 last time 的歷史資料(不適用在午夜12:00附近，可能會有些誤差)
function load_historical_data($sensorID, $section_departTime)
{
	
	$data = array();
	$sec_depart_date =  date('Y-m-d', $section_departTime);
	$sec_depart_time = date('H:i:s', $section_departTime);
	$split_date = explode("-", $sec_depart_date);
	$split_time = explode(":", $sec_depart_time);
	
	//預測 10:01，則 last time 取 9:55，以防 10:00 的資料尚未進入 database
	$time_of_lastTime =  date('H:i:s', mktime($split_time[0], $split_time[1]-($split_time[1]%5)-5, 25, $split_date[1], $split_date[2], $split_date[0]));
	$time_of_nowTime = date('H:i:s', mktime($split_time[0], $split_time[1]-($split_time[1]%5), 25, $split_date[1], $split_date[2], $split_date[0]));
	
	//mysql連線
	$link = mysqli_connect("localhost", "root", "eve1019");
	if(!$link){echo"connect failed";}

	$link_db=mysqli_select_db($link, "transportation"); 
	if(!$link_db){echo"failed";}
	
	mysqli_query($link, "SET NAMES utf8");
	
	// exam whether there exist table "$sensorID"
	$query = "show tables like '$sensorID'";
	
	mysqli_query($link, $query) or die('query connect data fail');	
	$result1 = mysqli_query($link,$query) or die("無法送出" . mysqli_error($link)); 
	
	$table_is_exist = mysqli_num_rows($result1);
	
	if($table_is_exist == 0) return $data;
	
	
	
	//判斷欲預測的日期是否為假日 1:假日 0:平日
	$query0 = "select holiday from date_to_day where date='$sec_depart_date'";
	
	//mysqli_query($link, $query0) or die('query0 connect data fail');	
	$result0 = mysqli_query($link, $query0) or die("無法送出" . mysqli_error($link));
	$row0 = mysqli_fetch_array( $result0 );
	
	if($row0[0] == 1) $is_holiday = 1;
	else $is_holiday = 0;
	
	//抓出來的資料格式[date] [last_time_speed,now_time_speed] or [date] [last_time_speed] or [date] [now_time_speed]
	$query = "select date, group_concat(avgSpeed order by time asc) as speed from $sensorID natural join date_to_day where date<'$sec_depart_date' and (time='$time_of_lastTime' or time='$time_of_nowTime') and holiday=$is_holiday group by date";
	
	//mysqli_query($link, $query) or die('query connect data fail');	
	$result = mysqli_query($link, $query) or die("無法送出" . mysqli_error($link));
	
	$total_data_num = mysqli_num_rows($result);
	
	if($total_data_num < 2)
	{
			echo "Historical data is too little to predict speed at time. <br \>";
			return $data;
	}
	else
	{
		$i=0;
		while( $row = mysqli_fetch_array( $result ) )
		{ 
			
			$split_speed = explode(",", $row[1]); //切割 [last_time_speed,now_time_speed] 
			
			if( count($split_speed) >= 2 && $split_speed[0]!=0 && $split_speed[1]!=0 )
			{
				
				$data[$i] = array("$row[0]", "$split_speed[0]", "$split_speed[1]"); //[date] [last_time_speed] [now_time_speed]
				$i++;
				
			}
			
		}
		
	}
	
	mysqli_free_result($result0);
	mysqli_free_result($result);
	mysqli_close($link);
	
	return $data;
}

?>