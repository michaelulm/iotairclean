<?php
	// created helper methods for database access methods

	function loadData(&$tmpDataArray, &$rangeArray, $find, $sort){
		
		// get mongo db connection 
		$mongo = new Mongo(/*"localahost:27101"*/);
		$iotairclean = $mongo->iotairclean;
		try
		{
			// we will need this both arrays later, to reduce server loading time
			$tmpDataArray = array();
			$measurements = $iotairclean->measurements;
			
			// Read all measurements by selection 
			$ms = $measurements->find($find)->sort($sort);
			
			// prepare range calculation for pie chart
			$rangeArray = array(400 => 0, 800 => 0, 1200 => 0, 1600 => 0);

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
					$rangeArray[$ppmCalc] = $rangeArray[$ppmCalc] + 1;
									 
					// temp store data for other graphics
					$tmpDataArray[] = array( 'measured' => $measureTime, 'temperature' => $temperature, 'humidity' => $humidity, "ppm" => $ppm);
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
	}