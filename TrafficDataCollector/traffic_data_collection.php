<?php
//include "../google_query.php";
//ignore_user_abort(true);//關掉瀏覽器，PHP腳本也可以繼續執行. 
//set_time_limit(0);// 通過set_time_limit(0)可以讓程序無限制的執行下去 
//$interval=300;// 每隔5min運行  
date_default_timezone_set("Asia/taipei");
//$past = time();

$file = "http://data.taipei/opendata/datalist/apiAccess?scope=resourceAquire&rid=5aacba65-afda-4ad5-88f5-6026934140e6&format=xml";

$DB_HOST	= "localhost";
$DB_LOGIN	= "root";
$DB_PASSWORD= "";
$DB_NAME	= "transportation";	
 
while(file_get_contents("gate.cfg")=="Y"){
	$now = time();
	$yearCollect = date('Y', $now);
	$monCollect = date('m', $now);
	$dayCollect = date('d', $now);
	$hourCollect = date('H', $now); 
	$minCollect = date('i', $now); 
	$secCollect = date('s', $now); 
	
	if( date('H:i:s', $now)=="06:55:25" || date('H:i:s', $now)=="10:55:25" || date('H:i:s', $now)=="13:55:25" || date('H:i:s', $now)=="16:55:25")
	{
		
		//connect to mySQL===========================================================
		$conn = mysqli_connect($DB_HOST, $DB_LOGIN, $DB_PASSWORD) or die( "Can't connect to MySQL!". mysqli_error($conn)."\n");
		mysqli_select_db($conn, $DB_NAME); 
		mysqli_query($conn, "SET NAMES utf8");
	
		$now_date = date('Y-m-d', $now);
		$now_time = date('H:i:s', $now);

		$log_data = fopen("log_data.txt","a")or die("Unable to open file!");
		collect_vehicle_speed($now, $now_date, $now_time);
		fclose($log_data);	 
		
		unset($now_date);
		unset($now_time);
		mysqli_close($conn);
	}
	if($hourCollect==7 || $hourCollect==11 || $hourCollect==14 || $hourCollect==17)
	{
		
		if($minCollect % 5 == 0 && $secCollect==25)
		{
			
			
			//connect to mySQL===========================================================
			$conn = mysqli_connect($DB_HOST, $DB_LOGIN, $DB_PASSWORD) or die( "Can't connect to MySQL!". mysqli_error($conn)."\n");
			mysqli_select_db($conn, $DB_NAME); 
			mysqli_query($conn, "SET NAMES utf8");
			
			$past= $now - 300;
			$timeToCollect = mktime($hourCollect,00,25,$monCollect,$dayCollect,$yearCollect);

			while($now - $timeToCollect <= 5400)
			{
				
				if($now-$past >= 300)
				{
					
					$past = $now;
					
					//get date & time
					$now_date = date('Y-m-d', $now);
					$now_time = date('H:i:s', $now);
					
					$log_data = fopen("log_data.txt","a")or die("Unable to open file!");
					collect_vehicle_speed($now, $now_date, $now_time);
					fclose($log_data);	
					
					
					/*if( $now_time=="07:30:25" || $now_time=="08:00:25" || $now_time=="11:00:25" || $now_time=="14:30:25" || $now_time=="17:30:25" || $now_time=="18:00:25")
					{
						
						//$now = time();
						$google_data = fopen("google_data.txt","a")or die("Unable to open file!");	
						for($path_num=1; $path_num<=3; $path_num++)
						{
							$now = time();
							$shortest_path = array();
							fwrite($google_data, $path_num."------------\n");
							get_google_duration_travel_time($path_num,$now);
							 
						}
						fwrite($google_data, "\n");
						fclose($google_data);
					 
					}  */
					//unset($now_date);
					//unset($now_time);
				} 
				//unset($now);		
				
				$now = time();
			}
			mysqli_close($conn);
		}
	}
}

	
function collect_vehicle_speed($now, $now_date, $now_time)
{
	global $conn;
	global $file;
	global $log_data;
	//fwrite($log_data, $now."\n");

	//xml parser 設定=================================================================
	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0); //關閉CASE_FOLDING功能
	xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
	xml_set_element_handler($xml_parser, "startElement", "endElement");
	xml_set_character_data_handler($xml_parser,"char");
	//read xml file============================================================================ 
	//$fp = fopen($file, "r") or die ( fwrite($log_data,"$file can't open.") );
	$fp = curl_file_get_contents($file) or die ( fwrite($log_data,"$file can't open.\n") );
	$flag=1;
	while ($data = $fp) {
		
		/*$data = str_replace("<", "&lt;;", $data);
		$data = str_replace(">", "&gt;", $data);
		$data = str_replace("'", "&apos;", $data);
		$data = str_replace('"', "&quot;", $data);*/
		$data = str_replace("&", "&amp;", $data);

		if($flag){
			if (!xml_parse($xml_parser, $data)) {
				die( 
					fwrite($log_data,"XML error: ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser)."\n")
				);
			}
			$flag=0;
		}else break;
	}
	//sleep($interval);
	//fclose($fp);
	unset($fp);
	
	//insert data to 'date_to_day' table
	if( date('H:i:s', $now) == '16:55:25' )
	{
		$tomorrow  = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		$tomorrow_date = date('Y-m-d', $tomorrow);
		$tomorrow_day = date('w', $tomorrow);
		$special_holiday = false;
		$special_workday = false;
		if($tomorrow_date=="2015-10-09" || $tomorrow_date=="2016-01-01" || $tomorrow_date=="2016-02-08" || $tomorrow_date=="2016-02-09" || $tomorrow_date=="2016-01-10" || $tomorrow_date=="2016-01-11" || $tomorrow_date=="2016-01-12" || $tomorrow_date=="2016-02-29" || $tomorrow_date=="2016-04-04" || $tomorrow_date=="2016-04-05" || $tomorrow_date=="2016-06-09" || $tomorrow_date=="2016-06-10" || $tomorrow_date=="2016-09-15" || $tomorrow_date=="2016-09-16" || $tomorrow_date=="2016-10-10")
			$special_holiday = true;
		if($tomorrow_date=="2016-01-30" || $tomorrow_date=="2016-06-04" || $tomorrow_date=="2016-09-10")
			$special_workday = true;
		if((($tomorrow_day==6 || $tomorrow_day==0) && !$special_workday) || $special_holiday)
			$query2 = "INSERT INTO date_to_day (date, day, holiday) VALUES ('$tomorrow_date', '$tomorrow_day', 1)"; 
		if(($tomorrow_day!=6 && $tomorrow_day!=0 && !$special_holiday) || $special_workday)
			$query2 = "INSERT INTO date_to_day (date, day, holiday) VALUES ('$tomorrow_date', '$tomorrow_day', 0)";  
		mysqli_query($conn, $query2)or die( fwrite($log_data,"Can't insert data from database!". mysql_error()."\n") );
	}
	
	
	xml_parser_free($xml_parser);  
	
	

	unset($data);
	unset($tomorrow);
	unset($tomorrow_date);
	unset($tomorrow_day);
	unset($special_holiday);
	unset($special_workday);
	unset($query2);
	
}

//request URL
function curl_file_get_contents($durl){ 
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $durl);   
  curl_setopt($ch, CURLOPT_TIMEOUT, 300); 
  //curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_); 
  //curl_setopt($ch, CURLOPT_REFERER,_REFERER_); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $r = curl_exec($ch); 
  curl_close($ch); 
  return $r; 
}

$_id = false; 
$SectionId = false;
$SectionName = false;
$AvgSpd = false;
$StartWgsX = false;
$StartWgsY = false;
$EndWgsX = false;
$EndWgsY = false;
$MOELevel = false;
//xml function===========================================================================  
function startElement($xml_parser, $name, $attrs)        //起始標籤事件的函數 
{ 
	//global $log_data; 
	global $_id, $SectionId, $SectionName, $AvgSpd, $StartWgsX, $StartWgsY, $EndWgsX, $EndWgsY, $MOELevel;   
	if($name=="_id") 
	{ 
		$_id=true; 
		$SectionId=false;
		$SectionName=false;
		$AvgSpd=false;
		$StartWgsX = false;
		$StartWgsY = false;
		$EndWgsX = false;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="SectionId") 
	{
		$_id=false; 
		$SectionId=true;
		$SectionName=false;
		$AvgSpd=false;
		$StartWgsX = false;
		$StartWgsY = false;
		$EndWgsX = false;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="SectionName") 
	{
		$_id=false; 
		$SectionId=false;
		$SectionName=true;
		$AvgSpd=false;
		$StartWgsX = false;
		$StartWgsY = false;
		$EndWgsX = false;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="AvgSpd") 
	{
		$_id=false; 
		$SectionId=false;
		$SectionName=false;
		$AvgSpd=true;
		$StartWgsX = false;
		$StartWgsY = false;
		$EndWgsX = false;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="StartWgsX") 
	{
		$_id=false; 
		$SectionId=false;
		$SectionName=false;
		$AvgSpd=false;
		$StartWgsX = true;
		$StartWgsY = false;
		$EndWgsX = false;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="StartWgsY") 
	{
		$_id=false; 
		$SectionId=false;
		$SectionName=false;
		$AvgSpd=false;
		$StartWgsX = false;
		$StartWgsY = true;
		$EndWgsX = false;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="EndWgsX") 
	{
		$_id=false; 
		$SectionId=false;
		$SectionName=false;
		$AvgSpd=false;
		$StartWgsX = false;
		$StartWgsY = false;
		$EndWgsX = true;
		$EndWgsY = false;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="EndWgsY") 
	{
		$_id=false; 
		$SectionId=false;
		$SectionName=false;
		$AvgSpd=false;
		$StartWgsX = false;
		$StartWgsY = false;
		$EndWgsX = false;
		$EndWgsY = true;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
	if($name=="MOELevel") 
	{
		$MOELevel = true;
		//echo $name." : ";
		//fwrite($log_data, "$name: ");
	} 
} 

function char($xml_parser, $data)
{
	global $conn;
	//global $log_data; 
	global $_id, $SectionId, $SectionName, $AvgSpd, $StartWgsX, $StartWgsY, $EndWgsX, $EndWgsY, $MOELevel;
    global $secID, $secName, $avgSpeed, $sX, $sY, $eX, $eY, $now_date, $now_time;
	
	if($_id){ 
		//$ID = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}
	if($SectionId){
		$secID = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}		
	if($SectionName){
		$secName = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}	
	if($AvgSpd){
		$avgSpeed = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}	
	if($MOELevel)
	{
		//echo $data."<br \>";
	}
	if($StartWgsX){
		$sX = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}
	if($StartWgsY){
		$sY = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}		
	if($EndWgsX){
		$eX = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
	}		
	if($EndWgsY){
		$eY = $data;
		//echo $data."<br \>";
		//fwrite($log_data, $data." ");
		
		if(is_sensor_I_want($secID))
		{
			//fwrite($log_data, "$secID is what I want.--------------------------------------------");
			$query3 = "INSERT INTO $secID(ID, avgSpeed, date, time) VALUES ('', '$avgSpeed', '$now_date', '$now_time')"; 	 
			mysqli_query($conn, $query3)or die( "Can't insert data from database!". mysqli_error($conn)."\n" );  
			 
		}
		
	}	
	unset($secID);
	unset($secName);
	unset($avgSpeed);
	unset($sX);
	unset($sY);
	unset($eX);
	unset($eY);
	
}

function endElement($xml_parser, $name)                 //結束標籤事件的函數 
{ 
	global $log_data;
	global $_id, $SectionId, $SectionName, $AvgSpd, $StartWgsX, $StartWgsY, $EndWgsX, $EndWgsY, $MOELevel; 
	$_id = false; 
	$SectionId = false;
	$SectionName = false;
	$AvgSpd = false;
	$MOELevel = false;
	$StartWgsX = false;
	$StartWgsY = false;
	$EndWgsX = false;
	$EndWgsY = false;
	//fwrite($log_data, "/".$name."\n");
} 

function is_sensor_I_want($secID)
{
	
 if($secID=="ZJCTT60" || $secID=="ZJERC60" || $secID=="ZJFQQ60" || $secID=="ZJGNK60" || $secID=="ZJHML60"
 || $secID=="ZIBHV20" || $secID=="ZHKQP20" || $secID=="ZHJS620" || $secID=="ZINM760" || $secID=="ZINLD60"
 || $secID=="ZIPIZ60" || $secID=="ZJGPQ00" || $secID=="ZKWN800" || $secID=="ZLPN800" || $secID=="ZNUNJ00"
 || $secID=="ZNZM960" || $secID=="ZP5KR60" || $secID=="ZP6JY60" || $secID=="ZJGPQ40" || $secID=="ZJGN740"
 || $secID=="ZFZK620" || $secID=="ZFYKD20" || $secID=="ZFTLH60" || $secID=="ZFPMQ20" || $secID=="ZG8N700"
 || $secID=="ZHLN700" || $secID=="ZKXET20" || $secID=="ZKLGD20" || $secID=="ZKAHN20" || $secID=="ZK8HY20" 
 || $secID=="ZK5IE20" || $secID=="ZJXIQ20" || $secID=="ZJTJ820" || $secID=="ZJQJI20" || $secID=="ZJHKR20" 
 || $secID=="ZJHM720" || $secID=="ZJHML20" || $secID=="ZJGMT00" || $secID=="ZJGPC20" || $secID=="ZJERC20" 
 || $secID=="ZJES820" || $secID=="ZJDST20" || $secID=="ZJCTT20" || $secID=="ZKCUX20" || $secID=="ZKVVH20" 
 || $secID=="ZLEWS20" || $secID=="ZM64J20" || $secID=="ZEUPN60" || $secID=="ZI6NV00" || $secID=="ZJGN700" 
 || $secID=="ZP6IV60" || $secID=="ZP6IG60" || $secID=="ZHZC820" || $secID=="ZHZDJ20" || $secID=="ZHHEF20" 
 || $secID=="ZH7EV20" || $secID=="ZGMFQ20" || $secID=="ZG6J520" || $secID=="ZHLM800" || $secID=="ZHMM600" 
 || $secID=="ZIAM700" || $secID=="ZJHM700" || $secID=="ZK8M700" || $secID=="ZLZM900" || $secID=="ZMQMV00" 
 || $secID=="ZMZM900" || $secID=="ZNUNJ20" || $secID=="ZNXPC20" || $secID=="ZP5QG20" || $secID=="ZQKWL20" 
 || $secID=="ZQUWI20") 
 {
	 return 1;
 }
 else{return 0;}
	
}
?>
