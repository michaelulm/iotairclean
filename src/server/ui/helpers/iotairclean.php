<?php
	// created helper methods for iot airclean specific operations
	
	/**
	 * detects after a period of time, that nobody is in the room after anybody was in there
	 * detections works will falling ppm value, just slowly and continously falling 
	 */
	function detectNobody(&$nobodyMeasurementsArray, $data, $debug = false){
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
		
		foreach($data as $d){
			// set first measurement
			if($nobodyStart == ""){
				$nobodyStart = $d["measured"];
			}
			
			$changePPM = $lastPPM - $d["ppm"];
			$latestPPM[] = $d["ppm"];		
							
			// check some more measurement at once to get an overview about up or down
			$nobodyHereCounter++;
			$latestDown = 0;
			foreach($latestPPM as $ppm){
				if($d["ppm"] <= $ppm){
					$latestDown++;
				}
			}
			
			// check some more measurement at once to get an overview about up or down
			if(count($latestPPM) > 20){
				array_shift($latestPPM);
				
				if($latestDown < 2){
					$nobodyFailureCounter = $possibleFailures;
					// echo "<br/>DETECT " . $d["ppm"];
				}
			}		
			
			// now start comparing
			if($changePPM <= $diffPPM && $changePPM >= -$diffPPM && $nobodyUpCounter < $nobodyUpCounterLimit){
				$nobodyFailureCounter = 0;
				$nobodyMeasurementsArrayTemp[] = array( "measured" => $d["measured"], "ppm" => $d["ppm"]);
				
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
					// only for debug purpose
					if($debug)
						echo "<br/>Nobody was here from " . $nobodyStart . " until " . $d["measured"] . " for $nobodyHereCounter with $nobodyFailureCounter (up $nobodyUp, down $nobodyDown)";
					
					// visualize nobody was here
					foreach($nobodyMeasurementsArrayTemp as $id => $value){
						$nobodyMeasurementsArray[] = $value;
						unset($nobodyMeasurementsArrayTemp[$id]);
					}
				}
				$nobodyMeasurementsArray[] = array( "measured" => $d["measured"], "ppm" => 0);
				$nobodyHereCounter = 0;
				$nobodyUpCounter = 0;
				$nobodyUp = 0;
				$nobodyDown = 0;
				$nobodyStart = $d["measured"];
				$nobodyMeasurementsArrayTemp = array();
			}		
			
			$lastPPM = $d["ppm"];
		}
		
		// end of nobody
		$nobodyMeasurementsArray[] = array( "measured" => $measuredTo, "ppm" => 0);
	}
	
	/**
	 * detects airing conditions, because of really fast falling ppm value
	 */
	function detectAiring(&$airingArray, $data, $debug = false){
		$airingArray = array();
		
		// TODO create Config File / Config Table in Database
		// basic settings for detect airing
		$ppmFalling  = 100; 	# ppm
		$interval    = 30;  	# interval when data is gathered on arduino
		$timeslot    = 10;   	# 10 minutes will be compared to detect airing
		$maxListLen  = intval(($timeslot * 60) / $interval);
		
		$ppmIncreaseLimit = 500;
		
		// needed variables for detecting airing
		$tmpItem["ppm"] = 0;
		$co2Falling      = False;
		$airingDetected  = False;
		$tmpList         = array();
		$airingList      = array(); 
		
		$airingValid     = 0; # is 0 or smaller when valid -> prevents that one airing is noted als multiple airings
		$increaseCounter = 0;
		
		// # iterate all items to find airing
		foreach($data as $d){
			// # detect if co2 falling
			if($d["ppm"] < $tmpItem["ppm"]){
				$co2Falling = True;
			} else {
				$co2Falling = False;
			}
			
			// # react on co2-state
			if($co2Falling){
				$airingList[] = $d;
			} else {
				// we are using an counter to identify increasing co2 again 
				if($tmpItem["ppm"] > $ppmIncreaseLimit){
					$increaseCounter++;
				} else {
					$increaseCounter = 0;
				}
				
				
				// TODO add limit variables
				// # airing was detected and co2 is increasing again
				if($airingDetected && $tmpItem["ppm"] > $ppmIncreaseLimit && $increaseCounter > 10){
					// echo "<br/> AIRING FINISHED";
					// echo "<br/> start:   " . $airingList[0]['measured'];
					// echo "<br/> end:     " . $airingList[count($airingList)-1]['measured'];
					$airingDetected  = False;
				}
				
				// clean array for new detections
				$airingList = array();
			}

			// # delete oldest item if max-length is reached
			if(count($tmpList) >= $maxListLen)
				array_shift($tmpList);
			$tmpList[] = $d["ppm"];

			// # start comparing measurements
			if(count($tmpList) == $maxListLen){
				$airingValid -= 1;
				if($tmpList[0] - $tmpList[$maxListLen-1] >= $ppmFalling && $tmpList[$maxListLen-1] <= 600){
					if($airingValid <= 0){
						$airingDetected = True;
						// echo '<br/> AIRING DETECTED: ' . $d["measured"];
						$airingValid = $maxListLen; # set list size to prevent next value to be airing too
					}
				}
			}
			$tmpItem = $d; # prepare for next iteration
			
			// now we prepare data for visualization, by airing we will show a graph
			if($airingDetected)
				$airingArray[] = array( "measured" => $d["measured"], "ppm" => $d["ppm"]); // doing airing
			else
				$airingArray[] = array( "measured" => $d["measured"], "ppm" => 0); // no airing
		}
	}
	