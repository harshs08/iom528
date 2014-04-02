<?php
	require_once('classes/PHPExcel.php');
	// cmd + option + F:  replace
	## try to get all the movie names from IMDB
	header("Content-type: text/html; charset=utf-8");
	//class movie{
		function parseExcel(){
			$filepath = 'final.xlsx';
			$file = 'location1';
			$fh = fopen($file,'a+');
			$parse = array();
			if (file_exists($filepath)){
				echo "File present";
				$inputFile = PHPExcel_IOFactory::identify($filepath);
				$objReader = PHPExcel_IOFactory::createReader($inputFile);
				$objReader->setReadDataOnly(true);
				// load data
				$objPHPExcel = $objReader->load($filepath);
				$total_sheets = $objPHPExcel->getSheetCount();
				$allSheetName=$objPHPExcel->getSheetNames(); 
            	$objWorksheet = $objPHPExcel->setActiveSheetIndex(0); 
            	$highestRow = $objWorksheet->getHighestRow();
            	$highestColumn = $objWorksheet->getHighestColumn(); 
            	$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);  
            	echo $highestRow." "." ".$highestColumnIndex;
   				// col: 列  row: 行
            	for ($col = 0; $col <= $highestColumnIndex;$col++) {
            		if ($objWorksheet->getCellByColumnAndRow($col,1)->getValue() == 'Name'){
            			$name_index = $col;
            		}
            		if ($objWorksheet->getCellByColumnAndRow($col,1)->getValue() == 'Home town'){
            			break;
            		}
            	}
            	$search = array(
            		"\n"
            		);
            	$replace = array(
            		""
            		);
            	for ($row = 2; $row <$highestRow;$row++){
                	$hometown = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                	$current_city = $objWorksheet->getCellByColumnAndRow($col+1,$row)->getValue();
                	$name  = $objWorksheet->getCellByColumnAndRow($name_index, $row)->getValue();
                	if ($hometown != NULL|| $current_city != NULL){
                		//$hometown = preg_replace("\n", "", $hometown);
                		//$current_city = preg_replace("\n", "", $current_city);
                		str_replace(" ", "", $hometown);
                		str_replace(" ", "", $current_city);
                		fwrite($fh, $name."\t".$hometown."\t".$current_city."\n");
                		echo $name."\t".$hometown."\t".$current_city."<br/>";
                	}
            	}	 
			}
			else{
				echo "file is wrong";
			}
			fclose($fh);
		}
		function continent_lookup($search) {
   			if (strlen($search) == 0)
        		return false;
   			$search = urlencode($search);
    		$app_id = 'H8RPj1jV34GPRIWg.othe2rP0e4X0_vBfCej_6d73qOzKh0v_b2o7V.7_JN.06fw';
    		$res = file_get_contents("http://where.yahooapis.com/v1/places.q($search)?appid=$app_id");
    		preg_match("/<woeid>([0-9]+?)<\/woeid>/", $res, $match);
     		if (!$match){
        		return false;
        	}
    		$woeid = $match[1];
    		$res = file_get_contents("http://where.yahooapis.com/v1/place/$woeid/belongtos.type(29)?appid=$app_id");
    		preg_match( "/<name>(.+?)<\/name>/", $res, $match );
    		return $match[1];
		}
		function init_user_info($info){
			$location = array();
			$continent = array('Asia','Africa','North America','South America','Antarctica','Europe','Australia');
			foreach ($continent as $item){
				echo strcmp($info, $item)."\t";
				if (strcmp($info, $item) == 1){
					//echo $info."<br/>";
					$location[$item] = 1;
				}
				else{
					//echo $item."\t";
					$location[$item] = 0;
				}
			}
			echo "<br/>";
			return $location;
		}
		function getGeoInfo($location){
			$continent = array('Asia','Africa','North America','South America','Antarctica','Europe','Australia');
			$file1 = 'dataset/hometown';
			$file2 = 'dataset/current';
			$out1 = fopen($file1,'w+');
			$out2 = fopen($file2,'w+');
			//$hometown = array();
			//$current_city = array();
			$fh = fopen($location, 'r') or die("no file available");
			/*fwrite($out1, "Name"."\t");
			fwrite($out2, "Name"."\t");
			foreach ($continent as $item){
				fwrite($out1, $item."\t");
				fwrite($out2, $item."\t");
			}
			fwrite($out1, "\n");
			fwrite($out2, "\n");*/
			while (!feof($fh)){
				ignore_user_abort(true);
				$interval = 2;
				//init_user_info($hometown);
				//init_user_info($current_city);
				$person_info = split("\t",fgets($fh));
				if ($person_info[1] != ""){
					$home = continent_lookup($person_info[1]);
					if ($home != NULL){
						fwrite($out1,$person_info[0]."\t".$home."\n");
					}
					// else{
					// 	fwrite($out1,$person_info[0]."\t".$home."\n");
					// }
					//fwrite($out1,$person_info[0]."\t".$home."\n");
					/*$hometown[$home] = 1;
					fwrite($out1, $person_info[0]."\t");
					foreach ($hometown as $name=>$number){
						fwrite($out1,$number."\t");
					}
					fwrite($out1, "\n");*/
				}
				if ($person_info[2] != ""){
					$curr = continent_lookup($person_info[2]);
					if ($curr != NULL){
						fwrite($out2, $person_info[0]."\t".$curr."\n");
					}
					//fwrite($out2, $person_info[0]."\t".$curr."\n");
					/*$current_city[$curr] = 1;
					fwrite($out2, $person_info[0]."\t");
					foreach ($current_city as $name=>$number){
						fwrite($out2,$number."\t");
					}
					fwrite($out2, "\n");*/
				}
				echo $person_info[0]." ".$home." ".$curr."<br/>";
				sleep($interval);
			}
			fclose($fh);
			fclose($out1);
			fclose($out2);
		}
		function getMatrix($f1,$f2){
			$continent = array('Asia','Africa','North America','South America','Antarctica','Europe','Australia');
			$temp = array();
			$curr_matrix = fopen('dataset/curr','w+');
			$home_matrix = fopen('dataset/home', 'w+');
			$curr = fopen($f1, 'r') or die("no file available");
			$home = fopen($f2, 'r') or die("no file available");
			fwrite($curr_matrix, "name"."\t");
			fwrite($home_matrix, "name"."\t");
			foreach ($continent as $loc) {
				fwrite($curr_matrix, $loc."\t");
				fwrite($home_matrix, $loc."\t");
			}
			fwrite($curr_matrix, "\n");
			fwrite($home_matrix, "\n");
			while (!feof($curr)){
				//$temp = init_user_info();
				$person_info = split("\t",fgets($curr));
				//if ($person_info[1] != NULL){
				//$temp[$person_info[1]] = 1;
				$temp = init_user_info($person_info[1]);
				fwrite($curr_matrix, $person_info[0]."\t");
				foreach ($temp as $place=>$count){
					fwrite($curr_matrix, $count."\t");
				}
				fwrite($curr_matrix, "\n");
				//print_r($temp);
				//echo "<br/>";
				//}
			}
			fclose($curr);
			fclose($curr_matrix);
			while (!feof($home)){
				//$temp = init_user_info($temp);
				$person_info = split("\t",fgets($home));
				$temp = init_user_info($person_info[1]);
				//if (sizeof($person_info) >= 2){
					//$temp[$person_info[1]] = 1;
					fwrite($home_matrix, $person_info[0]."\t");
					foreach ($temp as $place=>$count){
						fwrite($home_matrix, $count."\t");
					}
					fwrite($home_matrix, "\n");
				//}
			}
			fclose($home);
			fclose($home_matrix);
			echo "ok~";
		}
		//getGeoInfo('location');
		getMatrix('dataset/current','dataset/hometown');
		// $a = 'Asia';
		// $b = "Asia";
		// echo strcmp($a, $b);
		//parseExcel();
		//echo continent_lookup('Ho Chi Minh City, Vietnam');
?>