<?php

// to get all data of current day
$timestamp = strtotime('today midnight');

// check daylight saving time
if(date('I') == 1)
	$timestamp = $timestamp - (3600 * 1);
else
	$timestamp = $timestamp - (3600 * 2);

$measuredSince = date("Y-m-d H:i:s", $timestamp);

// some configs for easier customizing
$stationname = "wohnzimmer@home";
if(isset($_GET["stationname"]))
	$stationname = $_GET["stationname"];

?><!DOCTYPE html>
<html>
  <head>
    <title>IoT AirClean Visualisierung</title>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js" type="text/javascript"></script>
	<style>
	.container {
	  width: 80%;
	  margin: 15px auto;
	}
	.subcontainer-small{
		width:25%;
		float:left;
	}
	.subcontainer-big{
		width:75%;
		float:left;
	}
	</style>
  </head>
  <body>
	<img src="iot_airclean_logo_transparent_lang_small.png" />
	<h2>Visualisierung</h2>
	<div class="container">
		<div class="subcontainer-big">
			<canvas id="canvas"></canvas>
		</div>

		<div class="subcontainer-small">
			<canvas id="canvasPie"></canvas>
		</div>
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
                            x: newDateString(),
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
                            x: newDateString(),
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
                            x: newDateString(),
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
                            x: newDateString(),
                            y: 1600
                        }],
                    }, {
                        label: "aktuelle Messwerte CO2 (ppm)",
                        backgroundColor: color("#799E1A").alpha(0.5).rgbString(),
                        borderColor: "#799E1A",
						pointRadius: 0,
						pointHoverRadius: 5,
                        fill: false,
                        data: [
<?php

try
{
		// get mongo db connection 
        $mongo = new Mongo(/*"localahost:27101"*/);
        $iotairclean = $mongo->iotairclean;
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
				$ppm 			= $value["co2"];
				
				$ppmCalc = floor($ppm / 400) * 400;
				$range[$ppmCalc] = $range[$ppmCalc] + 1;
				
				// prepare for chart diagram
				echo "{
				 x: parseDB('$measureTime'),
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
                scales: {
                    xAxes: [{
                        type: "time",
                        time: {
                            format: timeFormat,
                            // round: 'day'
                            tooltipFormat: 'll HH:mm'
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Messzeitpunkt'
                        }
                    }, ],
                    yAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: 'co2 ppm',
                        },
						ticks: {
							suggestedMin: 0,    // minimum will be 0, unless there is a lower value.
							suggestedMax: 2000,	// maximum will be 2000 => normally should not reach this level
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
        });
		
		</script>
		
	</div>
	
  </body>
</html>