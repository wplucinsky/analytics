<?php

	class Sitemap {
		private $servername = getenv("SERVERNAME");
		private $username = getenv("USERNAME");
		private $password = getenv("PASSWORD");
		private $dbname = getenv("DBNAME_S");
		private $conn;


		function __construct(){	

			$this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
			if ($this->conn->connect_error) {
				die("Connection failed: " . $this->conn->connect_error);
			}
		}
	}
?>