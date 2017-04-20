<?php

	class SitemapDAO {

		private $servername = getenv("SERVERNAME");
		private $username = getenv("USERNAME");
		private $password = getenv("PASSWORD");
		private $dbname = getenv("DBNAME_S");
		private $conn;
		private $requiredDataKeys = array('fullname', 'lastmod', 'priority');

		function __contrust(){
			$this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
			if ($this->conn->connect_error) {
				die("Connection failed: " . $this->conn->connect_error);
			}

		}

		public function getNewPages () {
			$sql = "SELECT *
					FROM `view`
					WHERE NOT EXISTS ( 
						SELECT 1 FROM `sitemap`
						WHERE `view`.`fullname` = `sitemap`.`adname`)
						AND `fullname` <> 'NPoCP'
					ORDER BY `views` DESC
					LIMIT 30";

			return $this->query($sql);
		}

		public function getlastmod($adname) {
			$sql = "SELECT *
					FROM `analytics`
					WHERE `nickname` = '$adname'";
			$ad = $this->query($sql);

			if ($ad) {
				if ($ad['dateAdded'] == "2010-01-01 11:00:00") {
					return "2016-10-14 11:13:08";
				}
				return $ad['dateAdded'];
			} else {
				return "2016-10-14 11:13:08";
			}

		}

		public function getPriority ($adname) {
			$sql = "SELECT *
					FROM `view`
					WHERE `fullname` = '$adname'";

			$ad = $this->query($sql);
			if ($ad){
				return $ad['views'];
			} else {
				return '1';
			}
		}

		public function getNsfw ($adname) {
			$sql = "SELECT *
					FROM `storage`
					WHERE `fullname` = '$adname'";

			$ad = $this->query($sql);
			if ($ad){
				return $ad['nsfw'];
			} else {
				return '0';
			}
		}

		public function uploadSitemap($ads){
			if ( is_array($ads) ){
				foreach ($ads as $key => $ad) {
					if(count(array_intersect_key(array_flip($this->requiredDataKeys), $data)) === count($this->requiredDataKeys)) {
						$p = $conn->prepare('INSERT INTO sitemap (adname, lastmod, priority) VALUES (:fullname, :lastmod, :priority)');
						$p->execute(array('fullname' => $ad['fullname'], 'lastmod' => $ad['lastmod'], 'priority' => $ad['priority']));
			        } 
				}
			}

			return 0;
		}

		private function query($sql) {
			return $this->conn->query($sql);
		}

	}

?>