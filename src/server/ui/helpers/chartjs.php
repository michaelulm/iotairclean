<?php
	// created helper methods for easier project overview
	
	/**
	 * creates dataset entries for chartjs and will dynamically generates needed data points for visualization 
	 */
	function createDataset($from, $to, $label = "Frischluft", $colorHex = "#008000", $data = 400, $yAxisID = "y-axis-ppm", $output = "ppm", $compareMethod = "parseDB", $fill = false){
		
		if($fill !== false)
			$fill = "true";
		else
			$fill = "false";
		
		$dataset = "	{
					label: '$label',
					yAxisID: '$yAxisID',
					backgroundColor: color('$colorHex').alpha(0.5).rgbString(),
					borderColor: '$colorHex',
					pointRadius: 0,
					fill: $fill,
               ";
		if(is_array($data) == false){
			$dataset .= "
					data: [
					{ x: $compareMethod('$from'), 	y: $data },
					{ x: $compareMethod('$to'),	y: $data }
					]";
		} else {
			$dataset .= "
					pointHoverRadius: 5,
					data: [
			";
			foreach ( $data as $d ){

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
	