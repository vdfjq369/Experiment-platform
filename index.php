<?php

/*   
$predict_date_and_time = mktime(7, 00, 25, 6, 29, 2016);//想要預測的出發時間
$originX = 121.511421;
$originY = 25.047538;
$destinationX = 121.615282;
$destinationY = 25.053026;
$num_route = 1;*/

echo "
	<form method='get' action='main.php'>
		OriginX: <input name='originX' type='text' value='請輸入起始地X'>
		OriginY: <input name='originY' type='text' value='請輸入起始地Y'><br \>
		DestinationX: <input name='destinationX' type='text' value='請輸入目的地X'>
		DestinationY: <input name='destinationY' type='text' value='請輸入目的地Y'><br \>
		Departure date: <input name='DepartureDate' type='date'>
		Departure Time: <input name='DepartureHour' type='number' min='0' max='24'>點
		<input name='DepartureMinute' type='number' min='0' max='59'>分<br \>
		<input name='run' type='submit' value='run'><br \>
		</form>
";

?>
