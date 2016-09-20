<?php
	// Enable Error Reporting and Display:
		echo "<pre>";
		error_reporting(~0);
		ini_set('display_errors', 1);

	function dataRange($last){
	/* 
		This function returns an array of all the days in 'D M jS' form between the 
		end day and today.
	*/
		// gets every date from $last - 1 to current day
			$dates = array();
			foreach (range($last-1, 1, -1) as $key => $value) {
				if ($value == 1) {
					array_push($dates, date('D M jS', strtotime('-'.$value.' day')));
				} else {
					array_push($dates, date('D M jS', strtotime('-'.$value.' days')));
				}
			}
			array_push($dates, date('D M jS'));

		// return array
			return $dates;
	}

	function extractGZ() {
	/*
		This function scans the `stats` directory and extracts all the
		information from the .gz file to a .txt file if the .txt file
		doesn't already exist. It will always recreate the current day's
		.txt file. 
	*/
		// save all .gz files as .txt files in the stats directory
			$files = scandir('../../stats');
			foreach ($files as $key => $file) {
				if (preg_match("/access_log_([0-9]*).gz/", $file)) {
					if (!in_array(str_replace('.gz', '.txt', $file), $files)) {
						// open .gz to read and .txt to write
							$inputFile = '../../stats/'.$file;
							$outputFile = '../../stats/'.str_replace('.gz', '.txt', $file);
							$gz = gzopen($inputFile, 'rb');
							$output = fopen($outputFile, 'wb');

						// copy all data from .gz to .txt
							stream_copy_to_stream($gz, $output);
						
						// close both files
							gzclose($gz);
							fclose($output);
					}
				}
			}

		// redo today's
			$today = 'access_log_' . date('Ymd') . '.gz';
			$inputFile = '../../stats/'.$today;
			$outputFile = '../../stats/'.str_replace('.gz', '.txt', $today);
			$gz = gzopen($inputFile, 'rb');
			$output = fopen($outputFile, 'wb');
			stream_copy_to_stream($gz, $output);
			gzclose($gz);
			fclose($output);
	}

	function ipGrabber(){
	/*
		This function scans the `stats` directory and extracts IPs from a .txt file which 
		was previously extracted from a .gz file. It will always run on the current day, but
		will only run on other days if the file doesn't already exist. Only unique IPs from
		day are exported. All IPs are added to a master IP file if they aren't already in
		it. 
	*/
		// read and write all unique IPs to a .txt file
			$files = scandir('../../stats');
			foreach ($files as $key => $file) {
				if (preg_match("/access_log_([0-9]*).txt/", $file)) {
					if (!in_array(str_replace('.txt', '_ip.txt', $file), $files)) {
							$ipArray = array();
						// open .txt to read and .txt to write
							$inputFile = '../../stats/'.$file;
							$outputFile = '../../stats/'.str_replace('.txt', '_ip.txt', $file);
							$input = fopen($inputFile, 'r');
							$output = fopen($outputFile, 'w');

						// read and write the unique IPs
							while(!feof($input)) {
								$line = fgets($input);
								if(!in_array(str_replace(' ', '', current(array_slice(explode('-', $line), 0,1))), $ipArray)) {
									array_push($ipArray, str_replace(' ', '', current(array_slice(explode('-', $line), 0,1))));
								}
							}
							foreach ($ipArray as $key => $value) {
								fwrite($output, $value."\n");
							}
						
						// close both files
							fclose($input);
							fclose($output);
					}
				}
			}

		// redo today's
			$today = 'access_log_' . date('Ymd') . '.txt';
			$ipArray = array();
			$inputFile = '../../stats/'.$today;
			$outputFile = '../../stats/'.str_replace('.txt', '_ip.txt', $today);
			$input = fopen($inputFile, 'r');
			$output = fopen($outputFile, 'w');
			while(!feof($input)) {
				$line = fgets($input);
				if(!in_array(str_replace(' ', '', current(array_slice(explode('-', $line), 0,1))), $ipArray)) {
					array_push($ipArray, str_replace(' ', '', current(array_slice(explode('-', $line), 0,1))));
				}
			}
			foreach ($ipArray as $key => $value) {
				fwrite($output, $value."\n");
			}
			fclose($input);
			fclose($output);

		// make one large file of all IPs
			$ipArray = array();
			$files = scandir('../../stats/');
			foreach ($files as $key => $file) {
				if (preg_match("/access_log_([0-9]*)_ip.txt/", $file)) {
					$input = fopen('../../stats/'.$file, "r");
					if ($input) {
						while (($line = fgets($input)) !== false) {
							$ip = trim(fgets($input));
							if (!in_array($ip, $ipArray)) {
								array_push($ipArray, $ip);
							}
						}
						fclose($input);
					}
				}
			}
			$output = fopen('../../stats/analytics/ipMaster.txt', 'w');
			foreach ($ipArray as $key => $value) {
				fwrite($output, $value."\n");
			}
			fclose($output);
	}

	function homepageConversion(){
	/*
		This function scans the homepage SQL table to determine the conversion rate of
		people hitting the tool.php website and actually saving an ad, or clicking to 
		see about.php. It also saves the 5 most popular referers. Webadmin and bot clicks 
		are ignored. Two .txt files are created.
	*/
		// declarations
			$visitors = array("tool"=>0,"about"=>0,"image"=>0);
			$referers = array();
			$servername = "craigslistadsavercom.ipowermysql.com";
			$username = "cronjob";
			$password = "r4aqq7tg#Craigslist";
			$dbname1 = "link_storage";

		// create and check connections
			$conn1 = new mysqli($servername, $username, $password, $dbname1);
			if ($conn1->connect_error) {
				die("Connection failed: " . $conn1->connect_error);
			}

		// get all entires in homepage that aren't by webadmin or a bot
			$sql = "SELECT * FROM `homepage` WHERE ( unique_key <> '56380139eb3ed' and unique_key <> '5637ddee37583' and unique_key <> '5720ca2867718' and unique_key <> '564f94f04561f' and `unique_key` <> '5789059dbfcbc' ) ORDER BY `id` DESC";
			$result = $conn1->query($sql);
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					if (!strpos($row['useragent'], 'bing') && !strpos($row['useragent'], 'Baidu') && !strpos($row['useragent'], 'Googlebot') && !strpos($row['useragent'], 'semanticbot')) {
						// count occurence of referers
						if ($row['referer'] != '' and $row['referer'] != 'http://www.craigslistadsaver.com/tool.php' and $row['referer'] != 'http://www.craigslistadsaver.com/' ) {
							// add a / to all referer links
							if ( substr($row['referer'], -1) != '/'){ $referer = $row['referer'].'/'; } else { $referer = $row['referer']; }
							$referer = str_replace('https', 'http', $referer);
							if (!array_key_exists($referer, $referers)) {
								$referers[$referer] = 1;
							} else {
								$referers[$referer]++;
							}
						}

						if (preg_match('/http:\/\/www.craigslistadsaver.com\/about.php[#a-z]*/', $row['page'])) {
							$visitors['about']++;
						}
						if (preg_match('/http:\/\/www.craigslistadsaver.com\/tool.php[#a-z]*/', $row['page']) or preg_match('/http:\/\/www.craigslistadsaver.com(\/)?/', $row['page'])) {
							$visitors['tool']++;
						}
						if (strlen($row['pastedUrl']) > 1) {
							$visitors['image']++;
						}
					}
				}
			}
			$conn1->close();

		// get top 5 referers and write to a .txt file
			arsort($referers);
			$referers = array_slice($referers, 0, 5, true);
			$output = fopen('../../stats/analytics/toolReferers.txt', 'w');
			foreach ($referers as $key => $value) {
				fwrite($output, $value.':'.$key."\n");
			}
			fclose($output);

		// write visitor counts to a .txt file
			$output = fopen('../../stats/analytics/toolConversion.txt', 'w');
			foreach ($visitors as $key => $value) {
				fwrite($output, $key.':'.$value."\n");
			}
			fclose($output);

	}

	function scrollPercentages(){
	/*
		This functions gets the 15 most recently saved ads and gets records the scroll
		percentages into a .txt file.
	*/
		// declarations
			$percentages = array("0%"=>0,"10%"=>0,"20%"=>0,"30%"=>0,"40%"=>0,"50%"=>0,"60%"=>0,"70%"=>0,"80%"=>0,"90%"=>0,"100%"=>0);
			$names = array();
			$titles = array();
			$servername = "craigslistadsavercom.ipowermysql.com";
			$username = "cronjob";
			$password = "r4aqq7tg#Craigslist";
			$dbname1 = "link_storage";
			$dbname2 = "view_storage";

		// create and check connections
			$conn1 = new mysqli($servername, $username, $password, $dbname1);
			if ($conn1->connect_error) {
				die("Connection failed: " . $conn1->connect_error);
			}
			$conn2 = new mysqli($servername, $username, $password, $dbname2);
			if ($conn2->connect_error) {
				die("Connection failed: " . $conn2->connect_error);
			}


		// get 15 most recent ads
			$sql = "SELECT `id`, `fullname`, `title` FROM view ORDER BY `id` DESC LIMIT 15";
			$result = $conn1->query($sql);
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					array_push($names, $row['fullname']);
					array_push($titles, $row['title']);
				}
			}
			$conn1->close();
		

		// get scroll percentages
			foreach ($names as $key => $value) {
				$sql = "SELECT * FROM `" . $value . "` WHERE (`fullname` = '" . $titles[$key] . "') ";
				$result = $conn2->query($sql);
				if ($result) {
					while ($row = $result->fetch_assoc()) {
						$percentages[$row['percentage']] = $percentages[$row['percentage']] + 1;
					}
				} else { var_dump($result); }
			}
			$conn2->close();

		// write percentages to a file
			$output = fopen('../../stats/analytics/scrollPercentages.txt', 'w');
			foreach ($percentages as $key => $value) {
				fwrite($output, $key.':'.$value."\n");
			}
			fclose($output);
	}

	function dailyVisitors(){
	/*
		This function goes through all the saved ads and and records the number of users
		for the last 30 days, 10 days, 5 days, and 1 day. It puts all these results into a
		.txt file.
	*/
		// declarations
			$visitors = array("30"=>0,"10"=>0,"5"=>0,"1"=>0);
			$names = array();
			$servername = "craigslistadsavercom.ipowermysql.com";
			$username = "cronjob";
			$password = "r4aqq7tg#Craigslist";
			$dbname1 = "link_storage";
			$dbname2 = "view_storage";

		// create and check connections
			$conn1 = new mysqli($servername, $username, $password, $dbname1);
			if ($conn1->connect_error) {
				die("Connection failed: " . $conn1->connect_error);
			}
			$conn2 = new mysqli($servername, $username, $password, $dbname2);
			if ($conn2->connect_error) {
				die("Connection failed: " . $conn2->connect_error);
			}

		// get all the saved ad names
			$sql = "SELECT `id`, `fullname` FROM view ORDER BY `id` ASC";
			$result = $conn1->query($sql);
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					array_push($names, $row['fullname']);
				}
			}
			$conn1->close();

		// calculate day ranges
			$days30 = dataRange(30);
			$days10 = dataRange(10);
			$days5 = dataRange(5);
			$days1 = date('D M jS');

		// go through every view_storage table to get views/day range
			foreach ($names as $key => $value) {
				$sql = "SELECT `id`, `date` FROM `" . $value ."` ORDER BY `id` ASC";
				$result = $conn2->query($sql);

				if ($result) {
					while ($row = $result->fetch_assoc()) {
						$dateVal = str_replace('U_', '', $row['date']);
						$temp = explode(' ', $dateVal);
						$temp = @$temp[0] . ' ' . @$temp[1] . ' ' . @$temp[2];

						if (in_array($temp, $days30)) {
							$visitors['30']++;
						}
						if (in_array($temp, $days10)) {
							$visitors['10']++;
						}
						if (in_array($temp, $days5)) {
							$visitors['5']++;
						}
						if ($temp == $days1) {
							$visitors['1']++;
						}
					}
				} //else { echo $sql.'<br>'; } // outputs the sql code for the ad that hasn't been viewed in view.php
			}
			$conn2->close();

		// write visitors to a file
			$output = fopen('../../stats/analytics/dailyVisitors.txt', 'w');
			foreach ($visitors as $key => $value) {
				fwrite($output, $key.':'.$value."\n");
			}
			fclose($output);
	}

	function device(){
	/*
		This function goes through all the saved ads that have recored the device that's 
		accessing the ad. It creates a .txt file of the counts of mobile vs. desktop
		users.
	*/
		// declarations
			$device = array("mobile"=>0,"desktop"=>0);
			$names = array();
			$servername = "craigslistadsavercom.ipowermysql.com";
			$username = "cronjob";
			$password = "r4aqq7tg#Craigslist";
			$dbname1 = "link_storage";
			$dbname2 = "view_storage";

		// create and check connections
			$conn1 = new mysqli($servername, $username, $password, $dbname1);
			if ($conn1->connect_error) {
				die("Connection failed: " . $conn1->connect_error);
			}
			$conn2 = new mysqli($servername, $username, $password, $dbname2);
			if ($conn2->connect_error) {
				die("Connection failed: " . $conn2->connect_error);
			}

		// get all the saved ad names
			$sql = "SELECT `id`, `fullname` FROM view ORDER BY `id` ASC";
			$result = $conn1->query($sql);
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					array_push($names, $row['fullname']);
				}
			}
			$conn1->close();

		// go through every view_storage table to get desktop/mobile user
			foreach ($names as $key => $value) {
				$sql = "SELECT `id`, `mobile` FROM `" . $value ."` ORDER BY `id` ASC";
				$result = $conn2->query($sql);

				if ($result) {
					while ($row = $result->fetch_assoc()) {
						if ($row['mobile'] == 'desktop device') {
							$device['desktop']++;
						} elseif ($row['mobile'] == 'mobile device') {
							$device['mobile']++;
						}
					}
				} // else: `mobile` hasn't been set for that ad
			}
			$conn2->close();

		// write device to a file
			$output = fopen('../../stats/analytics/userDevices.txt', 'w');
			foreach ($device as $key => $value) {
				fwrite($output, $key.':'.$value."\n");
			}
			fclose($output);
	}

	function algolia(){
	/*
		This function takes all the ad data from SQL and creates CSV like files that can be 
		uploaded to Algolia and searched.
	*/	
		// declarations
			$upload = array();
			$servername = "craigslistadsavercom.ipowermysql.com";
			$username = "cronjob";
			$password = "r4aqq7tg#Craigslist";
			$dbname1 = "link_storage";

		// create and check connections
			$conn1 = new mysqli($servername, $username, $password, $dbname1);
			if ($conn1->connect_error) {
				die("Connection failed: " . $conn1->connect_error);
			}

		// get information from `storage`
			$sql = "SELECT url, title, imgname, fullname, price, adtype FROM storage";
			$result = $conn1->query($sql);
			if ($result) {
				array_push($upload, '"url", "title", "nickname", "price", "adtype", "views"');
				while ($row = $result->fetch_assoc()) {
					// picking either `imgname` or `fullname`
					if (strlen($row['fullname']) > 1) { $nickname = $row['fullname']; } else { $nickname = $row['imgname'];}

					// get current view count
					$sql2 = 'SELECT views FROM view WHERE title = "'. $row['title'] . '"';
					$result2 = $conn1->query($sql2);
					if ($result2) {
						$row2 = $result->fetch_array(MYSQLI_ASSOC);
						$views = $row['views'];
					}

					$addTo = '"'.$row['url'].'", "'.$row['title'].'", "'.$row['title'].'", "'.$row['price'].'", "'.$row['adtype'].'", "'.$views.'"';
					array_push($upload, $addTo);
				}
			}

		// put information into a txt file
			$output = fopen('../../stats/analytics/algoliaUpload.txt', 'w');
			foreach ($upload as $key => $value) {
				fwrite($output, $value."\n");
			}
			fclose($output);

	}

	// Files Created
		// ipMaster.txt
		// toolReferers.txt
		// toolConversion.txt
		// scrollPercentages.txt
		// dailyVisitors.txt
		// userDevices.txt
		// algoliaUpload.txt

	extractGZ(); 			// extract all .gz data to .txt file
	ipGrabber(); 			// extract all the ips to a .txt file
	homepageConversion();	// gets the numbers of people who save an ad after getting to tool.php
	scrollPercentages(); 	// gets the 15 most recent ads scroll percentages (ignores 0%)
	dailyVisitors();		// gets the 30, 15, 10 and 1 day(s) num of visitors
	device();				// gets the mobile vs. desktop device spread
	algolia();				// gets all the saved ads to be searched in Algolia


?>
