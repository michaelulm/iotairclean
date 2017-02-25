<?php
echo '<h2>PHP + MongoDB Test</h2>';
try
{
        $mongo = new Mongo(/*"localahost:27101"*/);

        $iotairclean = $mongo->iotairclean;

        $measurements = $iotairclean->measurements;

        $ms = $measurements->find();

        if ($ms->count() <1)
        {
            //$posts->insert(array('title' => 'Hello, MongoDB!')); // inserts new values
			//$posts->insert(array('temperature' => 26.4, 'humidity' => 45.3, 'co2' => 877, 'station' => "michaelulm@home"));// inserts new values
            
			// but currently nothing to do
        }
        else
        {
                echo "<p>" . $ms->count() . ' measurements found.</p>' ;

                // foreach($it as $obj)
                // {
                        // echo "title: [" . $obj["title"] . "]<br />";
                // }
        }
        $mongo->close();
}
catch(MongoConnectionException $e)
{
        die('Error in connection to MongoDB');
}
catch(MongoException $e)
{
        die('Error:' . $e->getMessage());
}

