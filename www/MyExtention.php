<?php
//Vars
$server = "localhost";
$user = "phpmyadmin";
$password = "LifCenter";
$dbname = "phpmyadmin";

//Absolute Humidity
function AbsoluteHumidity($rH, $T){
	$absHumidity = (6.112 * exp(17.67 * $T / ($T + 243.5)) * $rH * 2.1674) /
			(273.15 + $T);
	return $absHumidity;
}

?>