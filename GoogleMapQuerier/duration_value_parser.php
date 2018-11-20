<?php
$duration_in_traffic = false;
$text = false;

function parse_duration_from_google($url)
{
	//xml parser 設定=================================================================
	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0); //關閉CASE_FOLDING功能
	xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
	xml_set_element_handler($xml_parser, "google_startElement", "google_endElement");
	xml_set_character_data_handler($xml_parser,"google_char");
	//read xml file============================================================================
	$fp = fopen($url, "r"); 

	while ($data = fread($fp, 4096)) {
		if (!xml_parse($xml_parser, $data, feof($fp))) {
			die( 
				fwrite($google_data,"XML error: ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser)."\n")
			);
		}
	}
	
	fclose($fp);
	xml_parser_free($xml_parser);
}


function google_startElement($xml_parser, $name, $attrs)        //起始標籤事件的函數 
{ 

	global $duration_in_traffic, $text; 
	if($name=="duration_in_traffic") 
	{ 
		$duration_in_traffic = true;
	}
	if($duration_in_traffic)
	{
		if($name=="text")
		{
			$text = true;
		}
	}

}	

function google_char($xml_parser, $data)
{

	global $duration_in_traffic, $text; 
	
	if($duration_in_traffic && $text)
	{ 
		
		$google_travel_time = explode(" ", $data);
		echo "google map= ".$google_travel_time[0]." mins<br \>";
	}
}

function google_endElement($xml_parser, $name)                 //結束標籤事件的函數 
{ 

	global $duration_in_traffic, $text;
	if($text)
	{
		$text = false;
		$duration_in_traffic = false;
	}
	
} 
?>