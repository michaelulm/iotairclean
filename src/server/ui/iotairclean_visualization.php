<?php

	require_once("helpers/chartjs.php");
	require_once("helpers/database.php");
	require_once("helpers/iotairclean.php");
	
// prevent mongo timeout error after 30sec loading at hugh data amount
//MongoCursor::$timeout = -1;	

// primeDate today for Standard
setAirCleanDate($datePrimary, $datePrimaryForm, "datePrimary", "today");
setAirCleanDate($dateSecondary, $dateSecondaryForm, "dateSecondary", "yesterday");

// currently only compare 24h
function setAirCleanDate(&$varName, &$varFormName, $paramName, $paramDefault = "today"){

	// set default, and check for param of current form
	$date =  $paramDefault;
	if(isset($_GET["$paramName"]))
		$date = $_GET["$paramName"];
	
	$varName = strtotime("$date midnight"); // always start from midnight
	
	// TODO check for other countries
	// check daylight saving time, currently only supports austria and UTC+1
	if(date('I') == 1)
		$varName = $varName - (3600 * 1);
	else
		$varName = $varName - (3600 * 2);
	
	// defines variable for form output, must be 24h laters
	$varFormName = $varName + (3600 * 24);
}

// defines current measuring => Default: today
$measuredFrom = date("Y-m-d H:i:s", $datePrimary);
$measuredTo = date("Y-m-d H:i:s", $datePrimary + (3600 * 24));

$diffInSecondsPrimary = strtotime($measuredTo) - strtotime($measuredFrom);

// compare now with secondary Date => Default: Yesterday 
$compareFrom 	= date("Y-m-d H:i:s", $dateSecondary);
$compareTo	 	= date("Y-m-d H:i:s", $dateSecondary + (3600 * 24));
// for later graph options 
$diffInSeconds = $diffInSecondsPrimary;
// and also precalculated the diff in days
if($measuredFrom > $compareTo)
	$diffInDays = (strtotime($measuredFrom) - strtotime($compareTo)) / 3600 / 24;
else
	$diffInDays = -(strtotime($compareFrom) - strtotime($measuredFrom)) / 3600 / 24 - 1	;


// some configs for easier customizing
$stationname = "";
if(isset($_GET["stationname"]))
	$stationname = $_GET["stationname"];

// get mongo db manager in PHP 7
$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");

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
    <link href="css/jquery-1.12.1-ui-base.css" rel="stylesheet">			<!-- needed for datepicker in all browsers -->
	
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/jquery-1.12.1-ui.min.js"></script>						<!-- needed for datepicker in all browsers -->
	<script src="js/moment.min.js"></script>
	<script src="js/moment-timezone.min.js"></script>
	<script src="js/Chart.min.js"></script>
	<script src="js/mqttws31.min.js" type="text/javascript"></script>
	<style>
	.iotaircleanWithPad { margin:10px; padding:10px; }
	.iotaircleanAutoWidth { width: 100%;}
	.iotaircleanWithTopBottomMargin { width:100%; margin:10px 0px 10px 0px;}
	.iotaircleanFill { height:100vh;}
	#compareForm { width:100%; margin:20px 0px 20px 0px;}
	</style>
  </head>
  <body>
	
	
	<?php
		// get all available stations
		
		$command = new MongoDB\Driver\Command([
			'aggregate' => 'measurements',
			'pipeline' => [
				['$group' => ['_id' => '$station']],
			],
		]);
		$stations = $mongo->executeCommand("iotairclean", $command);
		
		$iotairclean_stations = array();
		foreach ($stations as $document) {
			foreach($document->result as $result){
				$id = $result->_id;
				$v  = $result->value;
				$iotairclean_stations[] = array( "id" => $id, "value" => $v);
			}
		}							
			
	?>
	
	
	<div class="row iot-graph iotaircleanAutoWidth iotaircleanFill" >
		<div class="col-md-1 col-sm-2 text-center">
			<!-- redesign for better overview -->
			<img src="img/iotairclean_logo_small.png" class="iotaircleanWithTopBottomMargin"	/>
			<?php
				echo "<table style='width:100%;'>";
				// show on big screen an online state of all sensor stations					
				foreach ($iotairclean_stations as $s) {
					$station = $s["id"];
					$room 	 = $s["value"];
					$class 	 = "btn-secondary";
					
					// set default station
					if($stationname == "")
						$stationname = $station;
					
					
					$stationShortnameArray = explode("@", $station);
					$stationShortname = $stationShortnameArray[0];
					
					if($stationname == $station){
						$class = "btn-primary";
						$stationHeader = $stationShortname;
					}
					
					echo "
						<div><a class='btn $class' style='width:100%;' href='?stationname=$station' >$stationShortname</a><a class='btn btn-warning' style='width:100%;' href='?stationname=$station' name='state-$station' ></a><div>
					";
				}
				echo "</table>";
			?>
				
			<form id="compareForm" >	
				<?php
					// build select
					foreach ($iotairclean_stations as $s) {
						
						$selected = "";
						$station = $s["id"];
						$counter = $s["value"];
						if($stationname == $station){
							echo "<input type='hidden' value='$station' />";
						}
						// echo "<option value='$station' $selected>$station ($counter Messungen)</option>";
					}
				?>
				
				<label for="datePrimary">Datum </label>
				<input type="text" name="datePrimary" id="datePrimary" value="<?php echo date('d.m.Y', $datePrimaryForm); ?>" size="10">
				<label for="dateSecondary">mit Datum </label>
				<input type="text" name="dateSecondary" id="dateSecondary" value="<?php echo date('d.m.Y', $dateSecondaryForm); ?>"  size="10">
				<input class="btn btn-default iotaircleanAutoWidth iotaircleanWithTopBottomMargin" type="submit" value="vergleichen"><br/>
				<a class="btn btn-default iotaircleanAutoWidth iotaircleanWithTopBottomMargin" href="<?php echo basename($_SERVER["SCRIPT_FILENAME"], '') ;?>">zurücksetzen</a>
			</form>	
				
		</div>
		<div class="col-md-10 col-sm-10">
			<p><strong>Aktuelle Sensorbox: <?php echo $stationHeader;?></strong></p>
			<canvas id="canvas"></canvas>
		</div>
		<div class="col-md-1 col-sm-0">
		</div>
	</div>
	
	
	
	
	<div class="row iotaircleanWithTopBottomMargin iotaircleanAutoWidth">
		<div class="col-md-1 col-sm-0"></div>
		<div class="col-md-2 col-sm-1"></div>
		<div class="col-md-3 col-sm-5">
			<h3>Aktueller Zeitraum</h3>
			<canvas id="canvasPie"></canvas>
		</div>		
		<div class="col-md-3 col-sm-5">
			<h3>Vergleichszeitraum</h3>
			<canvas id="canvasPieCompare"></canvas>
		</div>		
		<div class="col-md-2 col-sm-1"></div>
		<div class="col-md-1 col-sm-0"></div>
	</div>
	
<?php
	// now load necessary data from database for later acccess
	
	loadData( $othersArray, $range,	
		 array('station' => $stationname, 'measured' => array('$gt' => $measuredFrom,'$lt' => $measuredTo) ),
		 array('measured'=>1)
		 );
		 
	loadData( $compareArray, $rangeCompare,	
		 array('station' => $stationname, 'measured' => array('$gt' => $compareFrom,'$lt' => $compareTo) ),
		 array('measured'=>1)
		 );

?>
		
	<div class="row iotaircleanAutoWidth">
		<div class="col-md-12">
		<?php
			detectNobody($nobodyMeasurementsArray, $othersArray);
			detectAiring($airingMeasurementsArray, $othersArray);
		?>
		</div>
	</div>
	
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
	
	<script>
	
		var datepickerOptions = {
			// german
			prevText: '&#x3c;zurück', prevStatus: '',
			prevJumpText: '&#x3c;&#x3c;', prevJumpStatus: '',
			nextText: 'Vor&#x3e;', nextStatus: '',
			nextJumpText: '&#x3e;&#x3e;', nextJumpStatus: '',
			currentText: 'heute', currentStatus: '',
			todayText: 'heute', todayStatus: '',
			clearText: '-', clearStatus: '',
			closeText: 'schließen', closeStatus: '',
			monthNames: ['Januar','Februar','März','April','Mai','Juni',
			'Juli','August','September','Oktober','November','Dezember'],
			monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
			'Jul','Aug','Sep','Okt','Nov','Dez'],
			dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
			dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
			dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
			
			// other options
			dateFormat: 'dd.mm.yy',
			firstDay: 1 
			
		};
	
		jQuery( "#datePrimary" ).datepicker(datepickerOptions);
		jQuery( "#dateSecondary" ).datepicker(datepickerOptions);
		
        var timeFormat = 'DD.MM.YYYY HH:mm:ss';
        var lastTime;
        var color = Chart.helpers.color;
        var config = {
            type: 'line',
            data: {
                labels: [ 
                ],
                datasets: [
					<?php createDataset($measuredFrom, $measuredTo, "Frischluft", "#008000", 400);?>, 
					<?php createDataset($measuredFrom, $measuredTo, "in Ordnung", "#FFFF00", 800);?>, 
					<?php createDataset($measuredFrom, $measuredTo, "schlecht", "#FFA500", 1200);?>, 
					<?php createDataset($measuredFrom, $measuredTo, "Handlungsbedarf", "#FF0000", 1600);?>, 
					<?php createDataset($measuredFrom, $measuredTo, "CO2 (ppm)", "#799E1A", $othersArray, "y-axis-ppm", "ppm");?>, 
					<?php createDataset($measuredFrom, $measuredTo, "CO2 Trend", "#799E1A", 0, "y-axis-ppm", "ppm", "parseDB", "fill");?>, 
					<?php createDataset($measuredFrom, $measuredTo, "Temperatur (°C)", "#FF2853", $othersArray, "y-axis-dht", "temperature");?>, 
					<?php createDataset($measuredFrom, $measuredTo, "Luftfeuchtigkeit (%)", "#2D69FF", $othersArray, "y-axis-dht", "humidity");?>, 
					<?php createDataset($measuredFrom, $measuredTo, "abwesend", "#C0C0C0", $nobodyMeasurementsArray, "y-axis-ppm", "ppm", "parseDB", "fill", 0.2, 0.2);?>, 
					<?php createDataset($measuredFrom, $measuredTo, "gelüftet", "#799E1A", $airingMeasurementsArray, "y-axis-ppm", "ppm", "parseDB", "fill", 0.2, 0.2);?>, 
					<?php createDataset($measuredFrom, $measuredTo, "CO2 (ppm) Vergleich", "#c2c4c0", $compareArray, "y-axis-ppm", "ppm", "compareDB");?>
                    ]
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

		/* parse DB date value for correct timezone */
		function parseTimezoneOffset(value){
			var offset = 2;
			if(moment().tz('Europe/Vienna').isDST() == false)
				offset = 1;
			
			var duration = moment.duration({'hours' : offset});
			return moment(value).add(duration)
		}
		/* parse primary date value, there's no need for other modification currently */
		function parseDB(value){
			return parseTimezoneOffset(value);
		}
		/* parse secondary date value, also have to parse correct timezone */
		function compareDB(value){
			var compareArea = moment.duration({'seconds' : <?php echo $diffInSeconds; ?>});
			var compareDay  = moment.duration ({'hours' : <?php echo $diffInDays * 24; ?>});
			return parseTimezoneOffset(moment(value).tz('Europe/Vienna').add(compareArea).add(compareDay));
		}
		/* parse lastTime of last Measurement and add counter of prediction for visualization */
		function parsePrediction(value, counter){
			var seconds = 30 * counter;
			
			var d = new Date()
			var offset = 0;
			// workaround for local runtime with UTC and Raspberry Touch Display
			if( (d.getTimezoneOffset()/60) == 0){
				var offset = 2;
				if(moment().tz('Europe/Vienna').isDST() == false)
					offset = 1;
			}
			var addPredictionTime = moment.duration({'seconds' : seconds, "hours" : offset});
			return moment(value).add(addPredictionTime);
		}

        function newDate() {
            return moment().tz('Europe/Vienna').toDate();
        }

        function newDateString(days) {
            return moment().tz('Europe/Vienna').format(timeFormat);
        }

        function newTimestamp(days) {
            return moment().tz('Europe/Vienna').unix();
        }
		

        function addNewMeasurementPPM(ppm){
			 // current measurement ppm
			 config.data.datasets[4].data.push({ x: newDate(), y: ppm  });

            window.myLine.update();			
        }
		
        function addNewPredictionPPM(ppm, counter){
			
			if(counter == 1){
				config.data.datasets[5].data = [];
			}
			
			// it's not needed to show values below of 400, this is quite good enough for an forecast
			if(ppm < 400){
				ppm = 400;
			}
				
			 // current measurement ppm
			 config.data.datasets[5].data.push({ x: parsePrediction(newDate(), counter), y: ppm  });

            window.myLine.update();			
        }

		// variable for timeout for offline / online visualization
		var stations = [];
		
		// some parts need to be loaded after complete document is rady
        $( document ).ready(function() {

			// setup canvas with visualization
			var ctx = document.getElementById("canvas").getContext("2d");
			window.myLine = new Chart(ctx, config);
			
            window.myLine.update();

<?php
			// we will create a check parameter, so this will proof if primary == check, and so we now that only current date is activate or not
			setAirCleanDate($checkPrimary, $checkPrimaryForm, "checkPrimary", "today");
			
			// only activate live support for today 
			if($checkPrimary == $datePrimary){
?>
				// Create a client instance: Broker, Port, Websocket Path, Client ID
				var d = new Date();
				var n = d.getTime();
				client = new Paho.MQTT.Client("<?php echo $_SERVER['SERVER_ADDR']; ?>", Number(1884), "clientId" + n);

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

						if(obj.station == "<?php echo $stationname;?>"){
							if(typeof obj.counter !== 'undefined'){
								addNewPredictionPPM(obj.co2, obj.counter);
								console.log(obj.station + ' added to UI Graph Prediction');
							}else{
								addNewMeasurementPPM(obj.co2);
								console.log(obj.station + ' added to UI Graph Current');
							}
						}
					} catch (e) {
						// not json
					}
					
					$( "a[name='state-" + obj.station + "']" ).removeClass('btn-warning');
					$( "a[name='state-" + obj.station + "']" ).removeClass('btn-danger');
					$( "a[name='state-" + obj.station + "']" ).addClass('btn-success');
					
					clearTimeout(stations[obj.station]);
					
					// offline after 1min = 60 seconds
					stations[obj.station] = setTimeout(
					  function() 
					  {
						$( "a[name='state-" + obj.station + "']" ).removeClass('btn-success');
						$( "a[name='state-" + obj.station + "']" ).addClass('btn-danger');
					  }, 60000);
					  
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
<?php
			}
?>

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
		
	
	
	
	<div class="row iotaircleanWithTopBottomMargin iotaircleanAutoWidth">
		<div class="col-md-1 col-sm-0"></div>
		<div class="col-md-2 col-sm-1"></div>
		<div class="col-md-6 col-sm-10">
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
		<div class="col-md-2 col-sm-1"></div>
		<div class="col-md-1 col-sm-0"></div>
	
	</div>
	<div class="row iotaircleanWithTopBottomMargin iotaircleanAutoWidth">
		<div class="col-md-4">
		</div>
		<div class="col-md-4 text-center">
			mehr Details zu IoT AirClean auf <a href="http://www.iot-airclean.at">www.iot-airclean.at</a>
		</div>
		<div class="col-md-4">
		</div>
	</div>
  </body>
</html>
