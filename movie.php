<?php
	require_once('classes/PHPExcel.php');
	// cmd + option + F:  replace
	## try to get all the movie names from IMDB
	header("Content-type: text/html; charset=utf-8");
	//class movie{
		function parseExcel(){
			$filepath = 'final.xlsx';
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
            		if ($objWorksheet->getCellByColumnAndRow($col,1)->getValue() == 'name'){
            			$name_index = $col;
            		}
            		if ($objWorksheet->getCellByColumnAndRow($col,1)->getValue() == 'Movies'){
            			break;
            		}
            	}
            	for ($row = 2; $row <$highestRow;$row++){
                	$value = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                	$name  = $objWorksheet->getCellByColumnAndRow($name_index, $row)->getValue();
                	if ($value != NULL){
                		//$movie_name = split(',', $value);
                		$parse[$name] = $value;
                	}

            	}	 
				return $parse;
			}
			else{
				echo "file is wrong";
				return $parse;
			}
		}
		## make mapping on the movie genres which I have made
		function getGenre($excel_data){
			//$excel_data = parseExcel();
			ignore_user_abort(true);
			$file = 'genre';
			//$fh = fopen($file,'a+');
			$interval = 5; //5s
			if ($excel_data != NULL){
				foreach ($excel_data as $name => $movies){
					//echo $name.": ";
					//fwrite($fh,$name.": ");
					foreach ($movies as $i => $movie){
						if ($movie != NULL){
							$array = getJson($movie);
							//print_r($array);
							//fwrite($fh, $movie."->".$array." ");
							echo $name.": ".$movie."->".$array."<br/>";
							sleep($interval);
						}
					}
					//fwrite($fh, "\n");
				}
				//echo "finished~";
				//fclose($fh);
			}
		}
		## parse json with HTTP Get
		function getJson($title){
			$request_url = 'http://www.omdbapi.com/?t='.urlencode($title);
			//echo urldecode($request_url)."<br/>";
			$ch = curl_init(($request_url));
			//curl_setopt($ch,CURLOPT_URL,$request_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$cexecute = curl_exec($ch);
			$httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			curl_close($ch);
			$result = json_decode($cexecute,true);
			//if ($result['Genre'] != NULL){
				//$jsonInfo = array('Title' => $result['Title'],
				//		   		'Genre' => $result['Genre']);
			//}
			//echo split(",",$result['Genre'])[0];
			//echo "<br/>";
			return split(",",$result['Genre'])[0];
		}
		// chech and change the movie name with mistype
		function Check_Mistype($movies,$input){
			$shortest = -1;
			foreach($movies as $movie){
				$lev = levenshtein($input, $movie);
				if ($lev == 0){
					$closest = $movie;
					$shortest = 0;
					break;
				}
				if ($lev <= $shortest || $shortest<0){
					$closest = $movie;
					$shortest = $lev;
				}
			}
			/*echo "Input word:".$input."<br/>";
			if ($shortest == 0){
				echo "Exact match found: ".$closest."<br/>";
			}
			else{
				echo "Did you mean: ".$closest." ?<br/>"; 
			}*/
			return $closest;
		}
		// map  the genres with the list we defined
		function GenreMap($input,$output){
			$types = array('Action','Animation','Comedy','Documentary','Horror','Romance','Adventure','Crime','Fantasy');
			$filter1 = 'dict';
			$filter2 = 'map';
			$f1 = fopen($filter1, 'r+') or die("no such file");
			$f2 = fopen($filter2, 'r+') or die("no such file");
			$in = fopen($input, 'r+');
			$out = fopen($output,'w');
			$map = array();
			$dict = array();
			while (!feof($f2)){
				$line = split("\t", fgets($f2));
				$map[$line[0]] = $line[1];
			}
			fclose($f2);
			$map_key = array_keys($map);

			while (!feof($f1)){
				$line = split("\t",fgets($f1));
				if (sizeof($line) == 2){
					$dict[$line[0]] = $line[1];
				}
				else{
					$dict[$line[0]] = 1;
				}
			}
			fclose($f1);
			$dict_key = array_keys($dict);
			// count the genre for each user
			fwrite($out,"name"."\t");
			foreach($types as $ty){
				fwrite($out, $ty."\t");
			}
			fwrite($out,"\n");
			while (!feof($in)){
				$line = split("\t",fgets($in));
				$movies = split(";",$line[1]);
				$user = array();
				$user = init($user,$types);
				foreach($movies as $movie){
					$movie = Check_Mistype($dict_key,$movie);
					$genres = getJson($movie);
					if ($genres == NULL){
						if ($dict[$movie] != 0){
							$realname = $map[$movie];
							$genres = getJson($realname);
						}
					}
					/*$genre = split(",",$genres);
					foreach ($genre as $item){
						if ($user[$item] == NULL){
							$user[$item] = 1;
						}
						else{
							$user[$item]++;
						}
					}*/
					if (in_array($genres, $types)){
						$user[$genres]++;
					}
				}
				fwrite($out,$line[0]."\t");
				foreach($user as $genre=>$count){
					fwrite($out,$count."\t");
				}
				echo $line[0].": ";
				print_r($user);
				echo "<br/>";
				fwrite($out,"\n");
			}
			fclose($out);
		}
		function writefile($parse){
			$file = 'movies';
			$fh = fopen($file,'a+');
			foreach ($parse as $name => $movies){
				echo fwrite($fh, $name."\t".$movies."\n");
			}
			fclose($fh);
		}
		function read_file($file){
			$fh = fopen($file, 'r') or exit("unable to read");
			$genre = array();
			while (!feof($fh)){
				$line = split("\t",fgets($fh));
				$name = $line[0];
				$movies = split(";",$line[1]);
				//echo $name.": ";
				//print_r($movies);
				//echo "<br/>";
				$genre[$name] = $movies;
			}
			fclose($fh);
			return $genre;
		}
		function init($user,$types){
			foreach ($types as $type){
				$user[$type] = 0;
			}
			return $user;
		}
	//}
	/****** main method  ********/
	//$test = new movie();
	$file = 'movies'; 
	GenreMap($file,'output');
	//$movie = read_file($file);
	//getGenre($movie);
	//getJson('The Dark Knight');
	//getJson('rio');
	//$words  = array('apple','pineapple','banana','orange',
      //          'radish','carrot','pea','bean','potato','batman');
	/*$input = 'carrrot';
	$input2 = 'pphim.net';
	$test->Check_Mistype($words,$input2);*/
	//if (in_array('apple',$words)){
	//	echo '1';
	//}
?>