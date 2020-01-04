<?php
include 'MyExtention.php';

$conn = mysqli_connect($server, $user, $password, $dbname);
if (!$conn){die("Connection failed: " . mysqli_connect_error());}

/* Room Temeperature */

//find maximum
$sql = "SELECT Timestamp FROM Climate ORDER BY Temp_Sensor DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()){
		$MaxDt = new Datetime($row["Timestamp"]);
		//echo $MaxDt->format('Y-m-d H:i:s');
	}
}

//Find Average
for ($hour = 0; $hour < 24 ; $hour++){
	for ($minute = 0; $minute < 60; $minute = $minute + 15){
		$sql = "SELECT AVG(Temp_Outside), AVG(Temp_Sensor), AVG(Humid_Outside), AVG(Humid_Sensor) FROM Climate WHERE HOUR(Timestamp) = " . $hour . " AND MINUTE(Timestamp) = " . $minute . 
		" AND Timestamp > \"" . $DtStart->format('Y-m-d H:i:s') ."\" AND Timestamp <= \"" . $DtEnd->format('Y-m-d H:i:s') . "\"";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()){
				//echo sprintf("%'.02d", $hour) . ":" . sprintf("%'.02d", $minute) . ", " .$row["AVG(Temp_Sensor)"] . "<br>";
				$chart_data .= "{ time:'" . sprintf("%'.02d", $hour) . ":" . sprintf("%'.02d", $minute).
								"',tempSens:".$row["AVG(Temp_Sensor)"].
								",humidSens:".$row["AVG(Humid_Sensor)"].
								",tempOut:".$row["AVG(Temp_Outside)"].
								",humidOut:".$row["AVG(Humid_Outside)"]. "}, ";
			}
		}
	}
}
$conn->close();
?>

<html> 
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Climate</title>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
</head>
<body>


<h1>Temerpature </h1>
<div id="temperatureChart" style="height: 250px;"></div>
<h1> Relative Humidity </h1>
<div id="humidityChart" style="height: 250px;"></div>

</body>
</html>

<script> new Morris.Line({
  element: 'temperatureChart',
  data: [<?php echo $chart_data; ?>],
  parseTime:false,
  xkey: 'time',
  ykeys: ['tempSens','tempOut'],
  labels: ['Room Temperature [°C]', 'Outdoor Temperature [°C]']
});
new Morris.Line({
  element: 'humidityChart',
  data: [<?php echo $chart_data; ?>],
  parseTime:false,
  xkey: 'time',
  ykeys: ['humidSens','humidOut'],
  labels: ['Room Humidity [%]', 'Outdoor Humidity [%]']
});
</script>
