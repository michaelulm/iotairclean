<?php
	// created helper methods for database access methods

	function loadData(&$tmpDataArray, &$rangeArray, $find, $sort){
		
		// get mongo db connection 
		$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
		// $iotairclean = $mongo->iotairclean; // old php method for PHP 5
		
		try
		{
			// we will need this both arrays later, to reduce server loading time
			$tmpDataArray = array();
			$options = [
					'sort' => $sort,
				];

			// query data in PHP 7
			// Read all measurements by selection 
			$query = new MongoDB\Driver\Query($find, $options); 
			$ms = $mongo->executeQuery("iotairclean.measurements", $query);
			
			// prepare range calculation for pie chart
			$rangeArray = array(400 => 0, 800 => 0, 1200 => 0, 1600 => 0);
				
			foreach ( $ms as $m )
			{
				$measureTime 	= $m->measured;
				$ppm 			= $m->co2;			// part per millions	(CO2 Gehalt der Luft)
				$temperature	= $m->temperature;	// temperature 			(Temperatur)
				$humidity		= $m->humidity;	    // humidity				(Luftfeuchtigkeit)
				
				$ppmCalc = floor($ppm / 400) * 400;
				$rangeArray[$ppmCalc] = $rangeArray[$ppmCalc] + 1;
								 
				// temp store data for other graphics
				$tmpDataArray[] = array( 'measured' => $measureTime, 'temperature' => $temperature, 'humidity' => $humidity, "ppm" => $ppm);
			}
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