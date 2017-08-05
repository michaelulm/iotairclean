<?php
	// created helper methods for easier project overview
	
	/**
	 * creates dataset entries for chartjs and will dynamically generates needed data points for visualization 
	 */
	function createDataset($from, $to, $label = "Frischluft", $colorHex = "#008000", $data = 400, $yAxisID = "y-axis-ppm", $output = "ppm", $compareMethod = "parseDB", $fill = false, $alphaBorder = 1.0, $alphaBackground = 1.0){
		
		if($fill !== false)
			$fill = "true";
		else
			$fill = "false";
		
		$dataset = "	{
					label: '$label',
					yAxisID: '$yAxisID',
					backgroundColor: color('$colorHex').alpha($alphaBackground).rgbString(),
					borderColor: color('$colorHex').alpha($alphaBorder).rgbString(),
					pointRadius: 0,
					fill: $fill,
               ";
		if(is_array($data) == false && $data > 0){
			$dataset .= "
					data: [
					{ x: $compareMethod('$from'), 	y: $data },
					{ x: $compareMethod('$to'),	y: $data }
					]";
		} else if(is_array($data) == false){
			$dataset .= "
					data: [ ]";
		} else {
			$dataset .= "
					pointHoverRadius: 5,
					data: [
			";
			$lastD = null;
			$diff = new DateInterval('P0Y0M0DT0H5M0S');
			foreach ( $data as $d ){
				
				// check missing data
				if(is_null($lastD) == false){
					
					$datetime1 = new DateTime($lastD['measured']);
					$datetime2 = new DateTime($d['measured']);
					
					$datetime1->add($diff);
					
						
					// if data leak too big, we show this to the user
					if($datetime1 < $datetime2){
						
						$stepper = 100;
						if($lastD["$output"] < 100)
							$stepper = 5;
						// go down
						for($i = $lastD["$output"]; $i > 0; $i = $i - $stepper){
							
							if($i < 0)
								$i = 0;
							
							$dataset .=  	"{
											 x: $compareMethod('".$lastD['measured']."'),
											 y: $i
											 },";
						}
						$dataset .=  	"{
										 x: $compareMethod('".$lastD['measured']."'),
										 y: 0
										 },";
						
										 
								
						$stepper = 100;
						if($d["$output"] < 100)
							$stepper = 5;
						// go up
						for($i = 0; $i < $d["$output"]; $i = $i + $stepper){
							
							if($i > $d["$output"])
								$i = $d["$output"];
							
							$dataset .=  	"{
											 x: $compareMethod('".$d['measured']."'),
											 y: $i
											 },";
						}
					// echo $dataset;			 
					// echo $datetime1->format('Y-m-d H:i:s');
					// echo "<br/>".$datetime2->format('Y-m-d H:i:s');
					// die();
					}
				}
					$lastD = $d;
				

				// prepare for chart diagram
				
				$dataset .=  	"{
								 x: $compareMethod('".$d['measured']."'),
								 y: ".$d["$output"]."
								 },";
			}
			$dataset .= "
					],";
		}
		
		// close dataset at the end
		$dataset .= " }";
		echo $dataset;
	}
	