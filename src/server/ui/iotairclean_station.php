<?php
echo '<h2>PHP + MongoDB Test</h2>';

// some configs for easier customizing
$stationname = "wohnzimmer@home";
if(isset($_GET["stationname"]))
	$stationname = $_GET["stationname"];

try
{
        $mongo = new Mongo(/*"localahost:27101"*/);

        $iotairclean = $mongo->iotairclean;

        $measurements = $iotairclean->measurements;

        $ms = $measurements->find(array('station' => $stationname));

        if ($ms->count() <1)
        {
            //$posts->insert(array('title' => 'Hello, MongoDB!')); // inserts new values
			//$posts->insert(array('temperature' => 26.4, 'humidity' => 45.3, 'co2' => 877, 'station' => "michaelulm@home"));// inserts new values

			// but currently nothing to do
        }
        else
        {
                echo "<p>" . $ms->count() . ' measurements found.' ;
				$lastMeasurement = $measurements->find(array('station' => $stationname))->sort(array( 'measured' => -1))->limit(1);
				$values = $lastMeasurement->getNext();
//print_r($values);
				echo "<br/>Temperature: " . $values["temperature"] . "C";
				echo "<br/>Humidity: " . $values["humidity"] . "%";
				echo "<br/>CO2: " . $values["co2"] . "ppm";
				echo "<br/>measured: " . $values["measured"];

                                echo "<br/>Location: " . $values["location"];
                                echo "<br/>Room: " . $values["room"];

				echo "</p>";
                // foreach($it as $obj)
                // {
                        // echo "title: [" . $obj["title"] . "]<br />";
                // }
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

