<?php

// to get all data of current day
$timestamp = strtotime('today midnight');

// compare yesterday for Standard
$compareDate = "yesterday";
if(isset($_GET["compareDate"]))
	$compareDate = $_GET["compareDate"];

// TODO SELECT DATE
$compareTo = strtotime("$compareDate midnight");
$compareToOrig = $compareTo;

// for later compare
$diffInSeconds = $timestamp - $compareTo;

// check daylight saving time
if(date('I') == 1)
	$timestamp = $timestamp - (3600 * 1);
else
	$timestamp = $timestamp - (3600 * 2);

$measuredSince = date("Y-m-d H:i:s", $timestamp);
$measuredTo = date("Y-m-d H:i:s", $timestamp + (3600 * 24));

// compare now with yesterday 
$compareFrom = $compareTo - (3600 * 24);
$compareFrom 	= date("Y-m-d H:i:s", $compareFrom);
$compareTo	 	= date("Y-m-d H:i:s", $compareTo);

// some configs for easier customizing
$stationname = "wohnzimmer@home";
if(isset($_GET["stationname"]))
	$stationname = $_GET["stationname"];


// get mongo db connection 
$mongo = new Mongo(/*"localahost:27101"*/);
$iotairclean = $mongo->iotairclean;

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	
    <title>IoT AirClean Visualisierung</title>
	 <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
	
    <script src="js/jquery-3.2.1.min.js"></script>
	<script src="js/moment.min.js"></script>
	<script src="js/Chart.min.js"></script>
	<script src="js/mqttws31.min.js" type="text/javascript"></script>
	<style>
	.classWithPad { margin:10px; padding:10px; }
	</style>
  </head>
  <body>
	
	
	<form id="compareForm">
		<div class="row">
			<div class="col-md-6 col-sm-4">
				<img src="iotairclean_logo_small.png" />	
			</div>
			<div class="col-md-6 col-sm-8">
				
					<select name="stationname" class="form-control">
					<?php

						// get all available stations
						$measurements = $iotairclean->measurements;


						$keys = array("station" => 1);
						$initial = array("count" => 0);
						$reduce = "function (obj, prev) { prev.count++; }";
						$stations = $measurements->group($keys, $initial, $reduce);

						foreach ( $stations as $id => $values )
						{
							foreach($values as $value){
								$selected = "";
								$station = $value["station"];
								$counter = $value["count"];
								if($stationname == $station)
									$selected = "selected";
								echo "<option value='$station' $selected>$station ($counter Messungen)</option>";
							}
						}
					?>
					</select>
					  <input type="date" name="compareDate" id="compareDate" value="<?php echo date('Y-m-d', $compareToOrig); ?>" >
					  <input class="btn btn-default" type="submit" value="vergleichen">
					  <a class="btn btn-default" href="<?php echo basename($_SERVER["SCRIPT_FILENAME"], '') ;?>">zurücksetzen</a>
			</div>
		</div>
	</form>	
	<div class="row">
		<div class="col-md-12">
			<canvas id="canvas"></canvas>
		</div>
	</div>
	<div class="row">
		<div class="col-md-2"></div>
		<div class="col-md-2"></div>
		<div class="col-md-2">
			<h3>Aktueller Zeitraum</h3>
			<canvas id="canvasPie"></canvas>
		</div>		
		<div class="col-md-2">
			<h3>Vergleichszeitraum</h3>
			<canvas id="canvasPieCompare"></canvas>
		</div>		
		<div class="col-md-2"></div>
		<div class="col-md-2"></div>
	</div>
	
	
	
	
	
	
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
	
	<script>

        var timeFormat = 'DD.MM.YYYY HH:mm:ss';
        var color = Chart.helpers.color;
        var config = {
            type: 'line',
            data: {
                labels: [ // Date Objects
					parseDB(<?php echo "'$measuredSince'"; ?>),
                    newDate()
                ],
                datasets: [
                    {
                        label: "Frischluft",
                        backgroundColor: color("#008000").alpha(0.5).rgbString(),
                        borderColor: "#008000",
						pointRadius: 0,
                        fill: false,
                        data: [{
                            x: parseDB(<?php echo "'$measuredSince'"; ?>),
                            y: 400
                        },{
                            x: parseDB(<?php echo "'$measuredTo'"; ?>),
                            y: 400
                        }],
                    }, {
                        label: "in Ordnung",
                        backgroundColor: color("#FFFF00").alpha(0.5).rgbString(),
                        borderColor: "#FFFF00",
						pointRadius: 0,
                        fill: false,
                        data: [{
                            x: parseDB(<?php echo "'$measuredSince'"; ?>),
                            y: 800
                        },{
                            x: parseDB(<?php echo "'$measuredTo'"; ?>),
                            y: 800
                        }],
                    }, {
                        label: "schlecht",
                        backgroundColor: color("#FFA500").alpha(0.5).rgbString(),
                        borderColor: "#FFA500",
						pointRadius: 0,
                        fill: false,
                        data: [{
                            x: parseDB(<?php echo "'$measuredSince'"; ?>),
                            y: 1200
                        },{
                            x: parseDB(<?php echo "'$measuredTo'"; ?>),
                            y: 1200
                        }],
                    }, {
                        label: "Handlungsbedarf",
                        backgroundColor: color("#FF0000").alpha(0.5).rgbString(),
                        borderColor: "#FF0000",
						pointRadius: 0,
                        fill: false,
                        data: [{
                            x: parseDB(<?php echo "'$measuredSince'"; ?>),
                            y: 1600
                        },{
                            x: parseDB(<?php echo "'$measuredTo'"; ?>),
                            y: 1600
                        }],
                    }, {
                        label: "CO2 (ppm) aktuell",
						yAxisID: "y-axis-ppm",
                        backgroundColor: color("#799E1A").alpha(0.5).rgbString(),
                        borderColor: "#799E1A",
						pointRadius: 0,
						pointHoverRadius: 5,
                        fill: false,
                        data: [
<?php

						try
						{
							// we will need this both arrays later, to reduce server loading time
							$othersArray = array();
							
							# CURRENT MEASUREMENTS
							$measurements = $iotairclean->measurements;
							
							// Read all measurements of the current day 
							$ms = $measurements
								->find(array('station' => $stationname, 'measured' => array('$gt' => $measuredSince) ))
								->sort(array('measured'=>1))
							;
							
							// prepare range calculation for pie chart
							$range = array(400 => 0, 800 => 0, 1200 => 0, 1600 => 0);

							if ($ms->count() <1)
							{
								// currently nothing to do
							}
							else
							{
								foreach ( $ms as $id => $value )
								{
									$measureTime 	= $value["measured"];
									$ppm 			= $value["co2"];		// part per millions	(CO2 Gehalt der Luft)
									$temperature	= $value["temperature"];// temperature 			(Temperatur)
									$humidity		= $value["humidity"];   // humidity				(Luftfeuchtigkeit)
									
									$ppmCalc = floor($ppm / 400) * 400;
									$range[$ppmCalc] = $range[$ppmCalc] + 1;
									
									// prepare for chart diagram
									echo "{
									 x: parseDB('$measureTime'),
									 y: $ppm
									 },";
									 
									 // temp store data for other graphics
									 $othersArray[] = array( 'measured' => $measureTime, 'temperature' => $temperature, 'humidity' => $humidity, "ppm" => $ppm);
								}
							}
							$mongo->close();
						}
						catch(MongoConnectionException $e)
						{
							die('Error in connection to MongoDB' . $e->getMessage());
						}
						catch(MongoException $e)
						{
							die('Error:' . $e->getMessage());
						}
?>
							],
                    }, {
                        label: "Temperatur (°C) aktuell",
						yAxisID: "y-axis-dht",
                        backgroundColor: color("#FF2853").alpha(0.5).rgbString(),
                        borderColor: "#FF2853",
						pointRadius: 0,
						pointHoverRadius: 5,
                        fill: false,
                        data: [
<?php
						foreach ( $othersArray as $other ){
							
							// prepare for chart diagram
							echo "{
							 x: parseDB('".$other['measured']."'),
							 y: ".$other['temperature']."
							 },";
						}
?>
						
							],
                    }, {
                        label: "Luftfeuchtigkeit (%) aktuell",
						yAxisID: "y-axis-dht",
                        backgroundColor: color("#2D69FF").alpha(0.5).rgbString(),
                        borderColor: "#2D69FF",
						pointRadius: 0,
						pointHoverRadius: 5,
                        fill: false,
                        data: [
<?php
						foreach ( $othersArray as $other ){
							
							// prepare for chart diagram
							echo "{
							 x: parseDB('".$other['measured']."'),
							 y: ".$other['humidity']."
							 },";
						}
?>
						
						
							],
                    }, {
                        label: "CO2 (ppm) Vergleich",
						yAxisID: "y-axis-ppm",
                        backgroundColor: color("#E5FFCC").alpha(0.5).rgbString(),
                        borderColor: "#E5FFCC",
						pointRadius: 0,
						pointHoverRadius: 5,
                        fill: false,
                        data: [
<?php

						try
						{
							# COMPARING MEASUREMENTS
							$measurements = $iotairclean->measurements;
							// Read all measurements of the current day 
							$ms = $measurements
								->find(array('station' => $stationname, 'measured' 
										=> array('$gt' => $compareFrom, '$lt' => $compareTo)
									))
								->sort(array('measured'=>1))
							;
							
							// prepare range calculation for pie chart
							$rangeCompare = array(400 => 0, 800 => 0, 1200 => 0, 1600 => 0);

							if ($ms->count() <1)
							{
								// currently nothing to do
							}
							else
							{
								foreach ( $ms as $id => $value )
								{
									$measureTime 	= $value["measured"];
									$ppm 			= $value["co2"];
									
									$ppmCalc = floor($ppm / 400) * 400;
									$rangeCompare[$ppmCalc] = $rangeCompare[$ppmCalc] + 1;
									
									// prepare for chart diagram
									echo "{
									 x: compareDB('$measureTime'),
									 y: $ppm
									 },";
								}
							}
							$mongo->close();
						}
						catch(MongoConnectionException $e)
						{
							die('Error in connection to MongoDB' . $e->getMessage());
						}
						catch(MongoException $e)
						{
								die('Error:' . $e->getMessage());
						}
?>
							],
                    }]
            }
            ,
            options: {
                title:{
                    text: "IoT AirClean Station"
                },
				legend: {
					position: 'top'
				},
                scales: {
                    xAxes: [{
                        type: "time",
                        time: {
                            format: timeFormat,
                            round: true,
							unit: 'hour',
                            tooltipFormat: 'k:mm DD.MM.YYYY', 
							displayFormats: {
								hour: 'k'
							}
                        }, 
						
                        scaleLabel: {
                            display: true,
                            labelString: 'Messzeitpunkt'
                        }
                    }, ],
                    yAxes: [{
						id: 'y-axis-ppm',
						position: "left",
                        scaleLabel: {
                            display: true,
                            labelString: 'co2 ppm',
                        },
						ticks: {
							suggestedMin: 0,    // minimum will be 0, unless there is a lower value.
							suggestedMax: 2000,	// maximum will be 2000 => normally should not reach this level
							beginAtZero: true   // minimum value will be 0.
						}
                    },{
						id: 'y-axis-dht',
						position: "right",
                        scaleLabel: {
                            display: true,
                            labelString: 'Temp. / Feucht.',
                        },
						ticks: {
							suggestedMin: 0,    // minimum will be 0, unless there is a lower value.
							suggestedMax: 100,	// maximum will be 2000 => normally should not reach this level
							beginAtZero: true   // minimum value will be 0.
						}
                    }]
                },
            }
        };

		function parseDB(value){
			var offset = 2;
			if(moment().isDST() == false)
				offset = 1;
			
			var duration = moment.duration({'hours' : offset});
			return moment(value).add(duration);
		}
		function compareDB(value){
			var compareArea = moment.duration({'seconds' : <?php echo $diffInSeconds; ?>});
			var compareDay  = moment.duration ({'hours' : 24});
			return moment(value).add(compareArea).add(compareDay);
		}

        function newDate(days) {
            return moment().toDate();
        }

        function newDateString(days) {
            return moment().format(timeFormat);
        }

        function newTimestamp(days) {
            return moment().unix();
        }
		

        function addNewMeasurementPPM(ppm){
			 // goood
			 config.data.datasets[0].data.push({
			 x: newDate(),
			 y: 400,
			 });
			 // ok
			 config.data.datasets[1].data.push({
			 x: newDate(),
			 y: 800,
			 });
			 // bad
			 config.data.datasets[2].data.push({
			 x: newDate(),
			 y: 1200,
			 });
			 // nooo
			 config.data.datasets[3].data.push({
			 x: newDate(),
			 y: 1600,
			 });
			 // current measurement ppm
			 config.data.datasets[4].data.push({
			 x: newDate(),
			 y: ppm,
			 });

            window.myLine.update();
        }

		
		// some parts need to be loaded after complete document is rady
        $( document ).ready(function() {

			// setup canvas with visualization
			var ctx = document.getElementById("canvas").getContext("2d");
			window.myLine = new Chart(ctx, config);
			
            window.myLine.update();

            // Create a client instance: Broker, Port, Websocket Path, Client ID
            var d = new Date();
            var n = d.getTime();
            client = new Paho.MQTT.Client("192.168.100.191", Number(1884), "clientId" + n);

            // set callback handlers
            client.onConnectionLost = function (responseObject) {
                console.log("Connection Lost: " + responseObject.errorMessage);
                console.log("try to reconnect");
                onConnect();
            }

			// will handle current measurement from arduino (real-time)
            client.onMessageArrived = function (message) {
                //              console.log("Message Arrived: "+message.payloadString);
                var obj = message.payloadString;
                try {
                    obj = jQuery.parseJSON(message.payloadString);

                    addNewMeasurementPPM(obj.co2);
                } catch (e) {
                    // not json
                }
                console.log(obj);
            }

            // Called when the connection is made
            function onConnect() {
                console.log("Connected!");
                client.subscribe("/iotairclean");
                message = new Paho.MQTT.Message("Hello");
                message.destinationName = "/iotairclean";
                client.send(message);
            }

            // Connect the client, providing an onConnect callback
            client.connect({
                onSuccess: onConnect
                //,mqttVersion: 3
            });

			// second Pie Chart for overview
			var ctxPie = document.getElementById("canvasPie").getContext("2d");
			// For a pie chart
			var myPieChart = new Chart(ctxPie,{
				type: 'pie',
				data: {
					labels: [
						"Frischluft",
						"in Ordnung",
						"schlecht",
						"Handlungsbedarf"
					],
					datasets: [
						{
							data: [ <?php echo $range[400];?>,
									<?php echo $range[800];?>,
									<?php echo $range[1200];?>,
									<?php echo $range[1600];?>],
							backgroundColor: [
								"#008000",
								"#FFFF00",
								"#FFA500",
								"#FF0000"
							],
							hoverBackgroundColor: [
								"#008000",
								"#FFFF00",
								"#FFA500",
								"#FF0000"
							]
						}]
				},
				options: {
					legend: {
						display: false
					},
					animation:{
						animateScale:true
					}
				}
			});
			
			// second Pie Chart for overview
			var ctxPieCompare = document.getElementById("canvasPieCompare").getContext("2d");
			// For a pie chart
			var myPieChartCompare = new Chart(ctxPieCompare,{
				type: 'pie',
				data: {
					labels: [
						"Frischluft",
						"in Ordnung",
						"schlecht",
						"Handlungsbedarf"
					],
					datasets: [
						{
							data: [ <?php echo $rangeCompare[400];?>,
									<?php echo $rangeCompare[800];?>,
									<?php echo $rangeCompare[1200];?>,
									<?php echo $rangeCompare[1600];?>],
							backgroundColor: [
								"#008000",
								"#FFFF00",
								"#FFA500",
								"#FF0000"
							],
							hoverBackgroundColor: [
								"#008000",
								"#FFFF00",
								"#FFA500",
								"#FF0000"
							]
						}]
				},
				options: {
					legend: {
						display: false
					},
					animation:{
						animateScale:true
					}
				}
			});
        });
		
		</script>
		
	
	<div class="row">
		<div class="col-md-12">
		<?php
		
			// TESTING AREA
		
		
		?>
		
		
		</div>
	
	</div>
	
	<div class="row">
		<div class="col-md-2"></div>
		<div class="col-md-2"></div>
		<div class="col-md-4 classWithPad">
			<table  class="table">
				<tr>
					<th></th>
					<th>Aktueller Zeitraum</td>
					<td>Vergleichszeitraum</td>
				</tr>
				<tr>
					<th>Frischluft</th>
					<?php 
						$tdClass = "";
						if($range[400] > $rangeCompare[400]){
							$tdClass = "class='success'";
						} 
												
						$compare = $range[400] / $rangeCompare[400];

						// show success if we have an almost equal measurements
						if($compare > 0.95){
							$tdClass = "class='success'";
						} // raise a warning if there are not so good measurements
						else if($compare > 0.8){
							$tdClass = "class='warning'";
						} else  {
							$tdClass = "class='danger'";
						}

						

						?>
					<td <?php echo $tdClass; ?>><?php echo $range[400];?></td>
					<td><?php echo $rangeCompare[400];?></td>
				</tr>
				<tr>
					<th>in Ordnung</th>
					<?php 						
						$tdClass = "";
						$compare = $range[800] / $rangeCompare[800];

						// show success if we have an almost equal measurements
						if($compare > 0.95){
							$tdClass = "class='success'";
						} // raise a warning if there are not so good measurements
						else if($compare > 0.8){
							$tdClass = "class='warning'";
						} else {
							$tdClass = "class='danger'";
							
							if($range[800] < $range[400] // there are better values available
								&& $range[800] > ($range[1200] + $range[1600])	// there are less bad values available
								){
								$tdClass = "class='success'";
							}else if ($range[800] > ($range[1200] + $range[1600])){
								$tdClass = "class='warning'";
							}
						}
						
						
						if($range[800] == 0 ){
							$tdClass = "";
						}
						
						?>
					<td <?php echo $tdClass; ?>><?php echo $range[800];?></td>
					<td><?php echo $rangeCompare[800];?></td>
				</tr>
				<tr>
					<th>schlecht</th>
					<?php 
						$tdClass = "";
						$compare = $range[1200] / $rangeCompare[1200];
						if($compare > 1.1){
							$tdClass = "class='danger'";
						}
						else if($compare > 0.95){
							$tdClass = "class='warning'";
						}
						
						?>
					<td <?php echo $tdClass; ?>><?php echo $range[1200];?></td>
					<td><?php echo $rangeCompare[1200];?></td>
				</tr>
				<tr>
					<th>Handlungsbedarf</th>
					<?php 
						// no measurements for this area => success
						$tdClass = "success";
						// more than 0 values should raise a warning
						if($range[1600] > 0){
							$tdClass = "class='warning'";
						}
						// if there are more values than in the compare measurements, it must be raise a danger warning
						if($range[1600] > $rangeCompare[1600]){
							$tdClass = "class='danger'";
						}
						?>
					<td <?php echo $tdClass; ?>><?php echo $range[1600];?></td>
					<td><?php echo $rangeCompare[1600];?></td>
				</tr>
				<tr>
					<td colspan="3">(Anzahl der Messungen)<td>
				</tr>
			</table>
		</div>
		<div class="col-md-2"></div>
		<div class="col-md-2"></div>
	
	</div>
	<div class="row">
		<div class="col-md-4">
		</div>
		<div class="col-md-4 text-center classWithPad">
			mehr Details zu IoT AirClean auf <a href="http://www.iot-airclean.at">www.iot-airclean.at</a>
		</div>
		<div class="col-md-4">
		</div>
	</div>
  </body>
</html>