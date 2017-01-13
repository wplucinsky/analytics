<?php

	class AnalyticsIP {

		public $displaySuccess = FALSE;
		public $date;
		private $servername = "**";
		private $username = "**";
		private $password = "**";
		private $dbname = "**";


		function __construct(){
			$this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
			if ($this->conn->connect_error) {
				die("'link_stroage' Connection failed: " . $this->conn->connect_error);
			} 
		}

		public function readNewIPs(){
			$this->uploadNewIPs();
		}

		public function resolveLocation(){
			$this->getIPs();
		}

		private function uploadNewIPs(){
		/*
			This function takes the ipMaster.txt file generated by analytics.php
			and uploads the unique IPs in 200 count batches while ignoring
			duplicates.
		*/
			// declarations
				$input = fopen('../../stats/analytics/ipMaster.txt', 'r');
				$baseQuery = "INSERT IGNORE INTO `users_location` (`ip`) VALUES ";
				$query = $baseQuery;
				$i = 0;

			// read every line of file and upload to 200 record at a time to the database
				if ($input) {
					while (($line = fgets($input)) !== false) {
						$i++;
						$query .= '("' . trim(fgets($input)) . '"), ';
						if ($i > 200) {
							$result = $this->conn->query(substr($query, 0, -2));
							if ($result){ $query = $baseQuery; } else { var_dump($result); die();}
						}
					}
				}
		}

		private function uploadData( $data ) {
		/*
			This function takes the ip-api.com information and uploads it to the users_location
			table.
		*/
			// declarations
				global $i;

			// upload the record
				$sql = @"UPDATE users_location SET country = '{$data['country']}', countryCode = '{$data['countryCode']}', region = '{$data['region']}', regionName = '{$data['regionName']}', city = '{$data['city']}', zip = '{$data['zip']}', lat = '{$data['lat']}', lon = '{$data['lon']}', timezone = '{$data['timezone']}', isp = '{$data['isp']}', org = '{$data['org']}', mobile = '{$data['mobile']}', proxy = '{$data['proxy']}' WHERE `ip` = '{$data['query']}'";
				$result = $this->conn->query($sql);
				if (!$result) {
					$sql = @"UPDATE users_location lat = 'ERROR' WHERE `ip` = '{$data['query']}'";
					$result = $this->conn->query($sql);
				} else {
					$i++;
				}

		}

		private function getLocation( $ip ) {
		/*
			This function calls ip-api.com with a URL from the `users_location` table. The
			site limits 150 requests per minute. It tries to pass relatively real data.
		*/
			$url = "http://ip-api.com/php/" . $ip;
			$referer = 'https://www.google.com/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#q=ip%20api%20php';
			$header=array(
			  	'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12',
			  	'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			  	'Accept-Language: en-us,en;q=0.5',
			  	'Accept-Encoding: gzip,deflate',
			  	'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			  	'Content-type: text/html; charset=UTF-8',
			  	'Keep-Alive: 115',
		  		'Connection: keep-alive',
			);

		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_REFERER, $referer);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		    curl_setopt($ch, CURLOPT_HEADER, 0);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		    $output = curl_exec($ch);
		    curl_close($ch);

		    $this->uploadData(unserialize($output));
		}

		private function getIPs() {
		/*
			This function takes the `users_location` table data that hasn't found the location yet
			and calls the necessary functions to retrieve and upload the location data.
		*/
			// read 150 empty records
				$sql = "SELECT `ip` FROM `users_location` WHERE (`lat` = '') ORDER BY `id` ASC LIMIT 150";
				$result = $this->conn->query($sql);
				if ($result) {
					while ($row = $result->fetch_assoc()) {
						$this->getLocation($row['ip']);
					}
				}
		}

		public function getMyIP(){
		/*
			This function prints the iPower server's IP to the screen in order to unban
			it from the IP API.
		*/
			$ch = curl_init('http://whatismyip.org/');
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
			$myIp = curl_exec($ch);
			curl_close($ch);
			echo '<pre>';
			print_r($myIp);
			echo '</pre>';
			die();
		}
	}

	if ( isset($_GET['ip']) ){
		$analytics = new AnalyticsIP;
		$analytics->getMyIP();
	} elseif ( isset($_GET['cronjon'])){
		$analytics = new AnalyticsIP;
		$analytics->readNewIPs();
		$analytics->resolveLocation();
		echo '{ "success":"true", "error":"false" }';
	} else {
		echo '{ "success":"false", "error":"missing parameters" }';
	}
?>