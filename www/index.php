<?php
include 'MyExtention.php';

$conn = mysqli_connect($server, $user, $password, $dbname);
if (!$conn){die("Connection failed: " . mysqli_connect_error());}

//What Data to Fetch
if(isset($_GET["StartDate"]) && !empty($_GET["StartDate"]) && isset($_GET["EndDate"]) && !empty($_GET["EndDate"])){
	$DtStart = new Datetime($_GET["StartDate"]);
	$DtEnd = new Datetime($_GET["EndDate"]);
}
elseif(isset($_GET["GoToDate"]) && !empty($_GET["GoToDate"])){
	$DtStart = new Datetime($_GET["GoToDate"]);
	$DtEnd = new Datetime($_GET["GoToDate"]);
	$DtEnd->modify('+1 day');
}
else{
	$DtStart = new Datetime(date("Y-m-d G:i:s",mktime(0,0,0, date("n"), date("j"), date("y"))));
	$DtEnd = new Datetime(date("Y-m-d G:i:s",mktime(0,0,0, date("n"), date("j") + 1, date("y"))));
}
// Fetch Data
$sql = "SELECT * FROM Climate WHERE Timestamp > \"" . $DtStart->format('Y-m-d H:i:s') ."\" AND Timestamp <= \"" . $DtEnd->format('Y-m-d H:i:s') . "\"";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()){
		$chart_data .= "{ datetime:'" . $row["Timestamp"].
                        "',tempSens:".$row["Temp_Sensor"].
                        ",humidSens:".$row["Humid_Sensor"].
                      	",tempOut:".$row["Temp_Outside"].
                        ",humidOut:".$row["Humid_Outside"]. "}, ";
		$CurrentTemperature_Room = $row["Temp_Sensor"];
		$CurrentTemperature_Out = $row["Temp_Outside"];
		$CurrentHumidity_Room = $row["Humid_Sensor"];
		$CurrentHumidity_Out = $row["Humid_Outside"];
	}
}

$chart_data = substr ($chart_data, 0, -2); 

//Querry current values
$sql = "SELECT * FROM Climate WHERE Timestamp = (SELECT MAX(Timestamp) FROM Climate)";
if ($result = $conn->query($sql)){
	while($row = $result->fetch_assoc()){
		$CurrentTemperature_Room = $row["Temp_Sensor"];
		$CurrentTemperature_Out = $row["Temp_Outside"];
		$CurrentHumidity_Room = $row["Humid_Sensor"];
		$CurrentHumidity_Out = $row["Humid_Outside"];
	}
}

//Query Average Room Temperature of displayed Data
$sql = "SELECT AVG(Temp_Sensor) FROM Climate WHERE Timestamp > \"" . $DtStart->format('Y-m-d H:i:s') ."\" AND Timestamp <= \"" . $DtEnd->format('Y-m-d H:i:s') . "\"";
if ($result = $conn->query($sql)){
	while($row = $result->fetch_assoc()){
		$avrgRoomTemperature = $row["AVG(Temp_Sensor)"];
	}
}

//Query Average Room Humidity of displayed Data
$sql = "SELECT AVG(Humid_Sensor) FROM Climate WHERE Timestamp > \"" . $DtStart->format('Y-m-d H:i:s') ."\" AND Timestamp <= \"" . $DtEnd->format('Y-m-d H:i:s') . "\"";
if ($result = $conn->query($sql)){
        while($row = $result->fetch_assoc()){
                $avrgRoomHumidity = $row["AVG(Humid_Sensor)"];
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

<table align="center" width="350" border="1">
  <caption>
  <blockquote>
    <h1><a href="http://raspberrypi/index.php">Current Values</a></h1>
  </blockquote>
  </caption>
  <tr>
  	<th scope="col"></th>
    <th scope="col">Room</th>
    <th scope="col">Outside</th>
  </tr>
  <tr>
  	<td align="right"><strong>Temperature</strong></td>
    <td align="center"><?php echo $CurrentTemperature_Room . " °C" ?></td>
    <td align="center"><?php echo $CurrentTemperature_Out . " °C" ?></td>
  </tr>
  <tr>
  	<td align="right"><strong>Relative Humidity</strong></td>
    <td align="center"><?php echo $CurrentHumidity_Room . " %" ?></td>
    <td align="center"><?php echo $CurrentHumidity_Out . " %" ?></td>
  </tr>
  <tr>
  	<td align="right"><strong>Absolute Humidity</strong></td>
    <td align="center"><?php echo sprintf("%.2f", AbsoluteHumidity($CurrentHumidity_Room, $CurrentTemperature_Room)); ?> g/m³</td>
    <td align="center"><?php echo sprintf("%.2f", AbsoluteHumidity($CurrentHumidity_Out, $CurrentTemperature_Out)); ?> g/m³</td>
  </tr>
</table>
<br>
<div>
	
</div>
<form name="Update" action="index.php" align="center"> 
    <input name="GoToDate" type="submit" value="<?php $DtStart->modify('-1 day'); echo substr($DtStart->format('Y-m-d H:i:s'),-20, 10);
	$DtStart->modify('+1 day'); ?>">
    << Go To >>
    <input name="GoToDate" type="submit" value="<?php echo substr($DtEnd->format('Y-m-d H:i:s'),-20, 10) ?>">
</form>
<form name="Update" action="index.php" align="center"> 
	<label for "StartDate"> Show data between </label>
    <input name="StartDate" type="date" value="<?php echo substr($DtStart->format('Y-m-d H:i:s'),-20, 10); ?>"> 
    <label for "StartDate"> and </label>
    <input name="EndDate" type="date" value="<?php echo substr($DtEnd->format('Y-m-d H:i:s'),-20, 10); ?>">
	<input name="BttUpdate" type="submit" >
</form>
<h1>Temerpature </h1>
<div id="temperatureChart" style="height: 250px;"></div>
<p>Average: <?php echo sprintf("%.2f", $avrgRoomTemperature);  ?> °C </p>
<h1> Relative Humidity </h1>
<div id="humidityChart" style="height: 250px;"></div>
<p>Average: <?php echo sprintf("%.2f", $avrgRoomHumidity);  ?> % </p>

</body>
</html>

<script> new Morris.Line({
  element: 'temperatureChart',
  data: [<?php echo $chart_data; ?>],
  xkey: 'datetime',
  ykeys: ['tempSens','tempOut'],
  labels: ['Room Temperature [°C]', 'Outdoor Temperature [°C]']
});
new Morris.Line({
  element: 'humidityChart',
  data: [<?php echo $chart_data; ?>],
  xkey: 'datetime',
  ykeys: ['humidSens','humidOut'],
  labels: ['Room Humidity [%]', 'Outdoor Humidity [%]']
});
</script>
