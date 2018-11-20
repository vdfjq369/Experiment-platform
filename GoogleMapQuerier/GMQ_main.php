<?php 
include "google_map_direction_API.php";
include "duration_value_parser.php";
chdir("..");
/*$shortest_path = load_table_shortest_path_min_cost(0);
print_r($shortest_path);*/


function get_travel_time_from_google($path_num)
{
	$shortest_path = load_table_shortest_path_min_cost($path_num);
	$url = generate_url($shortest_path);
	parse_duration_from_google($url);
}

function load_table_shortest_path_min_cost($path_num)
{
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

	if(count($shortest_path) > 21 )//超過google可以設定經過的點
	{
		$num_of_via_point = count($shortest_path) - 2; //扣掉起點及終點
		
		while($num_of_via_point > 37)
		{
			$space = $num_of_via_point / 19; 
			
			for($i=1; $i<=$num_of_via_point; $i=$i+$space)
			{
				array_splice($shortest_path, $i, 1);
			}
			$num_of_via_point = count($shortest_path)-2;
		}
		if($num_of_via_point > 19) array_splice($shortest_path, 20, $num_of_via_point-19 );
	}
	
	fclose($path_file);
	return $shortest_path;
}

?>