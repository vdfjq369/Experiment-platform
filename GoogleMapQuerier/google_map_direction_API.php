<?php 

function generate_url($shortest_path)
{
	global $predict_date_and_time;

	global $google_data;
	$intersection_num = count($shortest_path);
	$direction_API = "https://maps.googleapis.com/maps/api/directions/xml?key=AIzaSyB4-PhMRVv1s5r-FmqBBgMB5qsFLj5gppU";
	$orgination = $shortest_path[0]['Y'].",".$shortest_path[0]['X'];
	$destination = $shortest_path[$intersection_num-1]['Y'].",".$shortest_path[$intersection_num-1]['X'];
	$intersec = "";
	for($i=1; $i<$intersection_num-1; $i++)
	{
		$intersec = $intersec."|via:".$shortest_path[$i]['Y'].",".$shortest_path[$i]['X'];
	}
	
	$url= $direction_API."&origin=".$orgination."&destination=".$destination."&waypoints=".$intersec."&departure_time=".$predict_date_and_time."&mode=driving&language=en-EN&traffic_model=best_guess";
	
	return $url;
}

?>