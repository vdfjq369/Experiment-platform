<?php

function fetch_speed_at_departure($sensorID, $section_departTime)
{
	
	global $now_date_and_time;
	//global $cur_date_and_time;
	//global $experiment_result;  
	if( $section_departTime >= $now_date_and_time ) 
	{
		//fwrite($experiment_result, "The past sec_depart_time exceeds now_date_and_time, you need to set cur_date_and_time more time from now.\n");
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
	
	mysqli_query($link, $query) or die('query1 connect data fail');	
	$result1 = mysqli_query($link,$query) or die("無法送出" . mysqli_error($link)); 
	
	$table_is_exist = mysqli_num_rows($result1);
	
	if($table_is_exist == 0) return 0;
	
	//echo $sec_depart_date;
	$query = "select avgSpeed from $sensorID where date='$sec_depart_date' and time='$time_of_nowTime'";
	
	mysqli_query($link, $query) or die('query2 connect data fail');	
	$result = mysqli_query($link,$query) or die("無法送出" . mysqli_error($link)); 
	$row = mysqli_fetch_array( $result );
	
	$real_speed = $row[0];
	//echo "speed at departure time: ".$real_speed."<br \>";
	mysqli_free_result($result);
	mysqli_close($link);
	
	return $real_speed;
	
}


?>