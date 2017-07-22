<?php
	// created helper methods for iot airclean specific operations
		
	$nobodyMeasurementsArray = array();
	// start of nobody
	$nobodyMeasurementsArray[] = array( "measured" => $measuredFrom, "ppm" => 0);

	// scaleable comparing values 
	$nrOfMeasuresPerMinute = 2;
	$diffPPM = 5;		   // max difference value
	$possibleFailures = 5; // nr of failures
	$nobodyHereLimit = 90; // Minutes

	$nobodyHereCounter = 0;
	$nobodyFailureCounter = 0;
	$nobodyStart = "";
	$nobodyUp = 0;
	$nobodyDown = 0;
	$nobodyUpCounter = 0;  // counts how many following ups happens
	$nobodyUpCounterLimit = 10;  
	
	$latestPPM = array();
	
	foreach($othersArray as $other){
		// set first measurement
		if($nobodyStart == ""){
			$nobodyStart = $other["measured"];
		}
		
		$changePPM = $lastPPM - $other["ppm"];
		$latestPPM[] = $other["ppm"];		
						
		// check some more measurement at once to get an overview about up or down
		$nobodyHereCounter++;
		$latestDown = 0;
		foreach($latestPPM as $ppm){
			if($other["ppm"] <= $ppm){
				$latestDown++;
			}
		}
		
		// check some more measurement at once to get an overview about up or down
		if(count($latestPPM) > 20){
			array_shift($latestPPM);
			
			if($latestDown < 2){
				$nobodyFailureCounter = $possibleFailures;
				// echo "<br/>DETECT " . $other["ppm"];
			}
		}		
		
		// now start comparing
		if($changePPM <= $diffPPM && $changePPM >= -$diffPPM && $nobodyUpCounter < $nobodyUpCounterLimit){
			$nobodyFailureCounter = 0;
			$nobodyMeasurementsArrayTemp[] = array( "measured" => $other["measured"], "ppm" => $other["ppm"]);
			
			if($changePPM > 0){
				$nobodyDown++;
				$nobodyUpCounter = 0;
			}
			
			if($changePPM < 0){
				$nobodyUp++;
				$nobodyUpCounter++;
			}			
		} else if($nobodyFailureCounter < $possibleFailures && $nobodyUpCounter < $nobodyUpCounterLimit){
			// we accept some failures
			$nobodyFailureCounter++;						
		}else {
			if($nobodyHereCounter > $nobodyHereLimit * $nrOfMeasuresPerMinute
				&& $nobodyUp / $nobodyDown < 2 // otherwise it must be someone here
			){
				echo "<br/>Nobody was here from " . $nobodyStart . " until " . $other["measured"] . " for $nobodyHereCounter with $nobodyFailureCounter (up $nobodyUp, down $nobodyDown)";
				
				// visualize nobody was here
				foreach($nobodyMeasurementsArrayTemp as $id => $value){
					$nobodyMeasurementsArray[] = $value;
					unset($nobodyMeasurementsArrayTemp[$id]);
				}
			}
			$nobodyMeasurementsArray[] = array( "measured" => $other["measured"], "ppm" => 0);
			$nobodyHereCounter = 0;
			$nobodyUpCounter = 0;
			$nobodyUp = 0;
			$nobodyDown = 0;
			$nobodyStart = $other["measured"];
			$nobodyMeasurementsArrayTemp = array();
		}		
		
		$lastPPM = $other["ppm"];
	}
	
	// end of nobody
	$nobodyMeasurementsArray[] = array( "measured" => $measuredTo, "ppm" => 0);
