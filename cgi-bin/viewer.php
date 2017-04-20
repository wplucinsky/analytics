	<?php
		
		require_once('analytics.php');
		require_once('sitemap.php');

		class Viewer {
			private $servername = getenv("SERVERNAME");
			private $username = getenv("USERNAME");
			private $password = getenv("PASSWORD");
			private $dbname = getenv("DBNAME_L");
			private $ads = array();
			private $conversion = array();
			private $scroll = array();
			private $device = array();
			public $today;
			public $midnight;
			public $weeks;

			function __construct(){
				$analytics = new Analytics();
				$sitemap = new Sitemap();

				$this->today = new DateTime();
				$this->midnight = new DateTime("today midnight");
				$this->week = new DateTime("-1 week");

				$this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
				if ($this->conn->connect_error) {
					die("Connection failed: " . $this->conn->connect_error);
				}

				$this->start();
			}

			public function start(){
			/*
				This function gets an array of all ads with
					- one view within one week
					- a new view
					- a new ad
			*/
				// get all ads with the above criteria
					$sql = "SELECT * FROM `analytics` WHERE ((`views` = '1' AND `dateAdded` > '{$this->week->format('Y-m-d H:i:s')}') OR (`dateAdded` >= '{$this->midnight->format('Y-m-d H:i:s')}') OR (`dateUpdated` >= '{$this->midnight->format('Y-m-d H:i:s')}'))";
					$result = $this->conn->query($sql);
					if ($result) {
						while ($row = $result->fetch_assoc()) {
							$temp = array();
							foreach ($row as $key => $value) {
								$temp[$key] = $value;
							}
							$temp['change'] = intval($row['views']) - intval($row['prevViews']);
							array_push($this->ads, $temp);
						}
					}
			}

			public function toolConversion(){
			/*
				This function reads the toolConversion.txt file and outputs the data necessary
				to make a donut plot with chart.js. 
				File format:
					tool:#
					about:#
					image:#
				Returns 
					[0]=> name
					[1]=> val
					[2]=> sum
			*/
				// read all data
				$name = array();
				$val = array();
				foreach(file('../../stats/analytics/toolConversion.txt') as $line) {
					$temp = explode(':', $line);
					array_push($name, ucfirst($temp[0]));
					array_push($val, intval($temp[1]));

				}

				// shift conversion to be a percentage of total
				foreach (array_slice($val, 1) as $key => $value) {
					$val[0] = $val[0] - $value;
				}

				// get total sum
				$sum = array_sum($val);

				// push to array and return
				array_push($this->conversion, $name, $val, $sum);
				return $this->conversion;
			}

			public function scrollPercentages(){
			/*
				This function reads the scrollPercentages.txt file and outputs the data necessary
				to make a line plot with chart.js. 
				File format: 
					#%:# from 0 to 100%
				Returns 
					[0]=> name
					[1]=> val
					[2]=> sum
			*/
				// read all data
				$name = array();
				$val = array();
				foreach(file('../../stats/analytics/scrollPercentages.txt') as $line) {
					$temp = explode(':', $line);
					array_push($name, $temp[0]);
					array_push($val, intval($temp[1]));
				}

				// get total sum
				$sum = array_sum($val);

				// push to array and return
				array_push($this->scroll, $name, $val, $sum);
				return $this->scroll;
			}

			public function userDevices(){
			/*
				This function reads the scrollPercentages.txt file and outputs the data necessary
				to make a line plot with chart.js. 
				File format: 
					mobile:#
					desktop:#
				Returns 
					[0]=> name
					[1]=> val
					[2]=> sum
			*/
				// read all data
				$name = array();
				$val = array();
				foreach(file('../../stats/analytics/userDevices.txt') as $line) {
					$temp = explode(':', $line);
					array_push($name, $temp[0]);
					array_push($val, intval($temp[1]));
				}

				// get total sum
				$sum = array_sum($val);

				// push to array and return
				array_push($this->device, $name, $val, $sum);
				return $this->device;
			}

			public function returnAds(){
			/* 
				This function returns all the ads with the above criteria
			*/
				return $this->ads;
			}
		}

		$view = new Viewer();
		$conversion = $view->toolConversion();
		$scroll = $view->scrollPercentages();
		$device = $view->userDevices();
		$ads = $view->returnAds();

	?>
	<html>
	<head>
		<link rel="icon" type="image/png" href="../Images/iconSmall4.png">
		<title>CraigslistAdSaver Viewer</title>
		<style>
			/*tr:nth-child(even) {
				background-color: #f1f1f1;
			}
			tr:nth-child(odd) {
				background-color: #ffffff;
			}*/
			.number{
				text-align: center;
			}
			.noChange{
				background-color: #f39c12;
			}
			.smallChange{
				background-color: #ddffcc;
			}
			.bigChange{
				background-color: #339900;
			}
			.problem{
				background-color: #b32400;
			}
			.viewTable{
				margin: auto; width: 75%; margin-top: 100px; margin-bottom: 100px;
			}
			a, a:visited {
				color: #1ca8dd;
			}
			th{
				color: #1ca8dd;
			}
			tr:hover{
				background-color: #434857 !important;
				color: #cfd2da !important;
			}
			.strongText{
				text-align: center;
			    color: #999999;
			    margin-top: 0px;
			    margin-bottom: 0px;
			}
			h3{
				text-align: center; font-size: 24px; font-weight: 300; margin-top: 0px;
			}
			canvas{
				margin: auto;
			}
			body{
				color: #cfd2da; background-color: #252830; font-family: monospace;
			}
			#canvasContainer{
				width: 100%; margin: auto; height: 410px;
			}
			.canvas{
				width: 33%; float: left;
			}
			.paddThis{
				margin: auto; margin-bottom: 15px; width: 77%;
			}
			.leftAlign{
				float: left;
			}
			table{
				padding-bottom: 100px;
			}
			.tableDiv{
				width: 95%; margin: auto;
			}
		</style>
		<meta name="theme-color" content="#6B3FA0">
		<meta name="msapplication-navbutton-color" content="#6B3FA0">
		<meta name="apple-mobile-web-app-status-bar-style" content="#6B3FA0">
		<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.js"></script>
	</head>
	<body>
		<div class='viewTable'>
			<div id="canvasContainer">
				<div class="canvas">
					<div class="paddThis"><canvas id="conversion" width="300" height="300"></canvas></div>
					<h4 class="strongText">Homepage: <?php echo $conversion[2]; ?></h4>
					<h3>Conversion Rates</h3>
				</div>
				<div class="canvas">
					<div class="paddThis"><canvas id="scroll" width="300" height="300"></canvas></div>
					<h4 class="strongText">Views: <?php echo $scroll[2]; ?></h4>
					<h3>Scroll Percentages</h3>
				</div>
				<div class="canvas">
					<div class="paddThis"><canvas id="device" width="300" height="300"></canvas></div>
					<h4 class="strongText">Global: <?php echo $device[2]; ?></h4>
					<h3>Devices</h3>
				</div>
			</div>
			<div class="tableDiv">
				<h1 class="leftAlign">SQL 'view' Table</h1>
				<?php
					$totalSum = 0; $daySum = 0;
					echo "<table class='leftAlign'><tr><th>id</th><th>fullname</th><th>title</th><th>views</th><th>change</th><th>date added</th><th>date updated</th>";
					foreach ($ads as $key => $value) {
						$totalSum += intval($value['views']);
						$daySum += intval($value['change']);
						if ($value['fullname'] == 'Already Saved ERROR') {
							echo "<tr><td>" . $value['id'] . "</td><td>" . $value['fullname'] . " </td><td> " . $value['title'] . " </td><td class='number'> " . $value['views'] . " </td>";
						} else {
							echo "<tr><td>" . $value['id'] . "</td><td><a target='_blank' href='http://www.craigslistadsaver.com/view.php?name=" . $value['fullname'] . "'>" . $value['fullname'] . " </td><td> " . $value['title'] . " </td><td class='number'> " . $value['views'] . " </td>";
						}
						// coloring for view change
						if ($value['change'] == 0) {
							echo "<td class='noChange number'> " . $value['change'] . " </td>";
						} elseif ($value['change'] > 0 && $value['change'] <= 10) {
							echo "<td class='smallChange number'> " . $value['change'] . " </td>";
						} elseif ($value['change'] >= 11 ) {
							echo "<td class='bigChange number'> " . $value['change'] . " </td>";
						} else {
							echo "<td class='problem number'> " . $value['change'] . " </td>";
						}
						echo "<td class='number'> " . current(explode(' ', $value['dateAdded'])) . " </td><td class='number'> " . current(explode(' ', $value['dateUpdated'])) . " </td></tr>";
					}
					echo '<tr style="height:20px;"></tr><tr><td></td><td>Total Ads: '.sizeof($ads).'</td><td class="number">'.date('l F jS, Y h:i:s A').'</td><td class="number">'.$totalSum.'</td><td class="number">'.$daySum.'</td><td class="number"></td><td class="number"></td></tr>';
					echo "</table>";
				?>
			</div>
		</div>
		<script type="text/javascript">
			var options = {
			    maintainAspectRatio: false,
			    responsive: true
			};

			/* Conversion Chart */
			var ctxConversion = document.getElementById("conversion").getContext('2d');
			var conversion = [
				{
					value: <?php echo json_encode($conversion[1][0]); ?>,
					label: <?php echo json_encode($conversion[0][0]); ?>,
					color: "#34495e"
				},
				{
					value: <?php echo json_encode($conversion[1][1]); ?>,
					label: <?php echo json_encode($conversion[0][1]); ?>,
					color: "#95a5a6"
				},
				{
					value: <?php echo json_encode($conversion[1][2]); ?>,
					label: <?php echo json_encode($conversion[0][2]); ?>,
					color: "#ecf0f1"
				}
			];
			var myConversionChart = new Chart(ctxConversion).Doughnut(conversion, options);
			
			// /* Scroll Chart */
			ctxScroll = document.getElementById("scroll").getContext('2d');
			var scroll = {
			  labels: <?php echo json_encode($scroll[0]); ?>,
			  datasets: [{
			    fillColor: ["#242f24","#f7fdf7","#f0fbf0","#e8f9e8","#e1f8e1","#d9f6d9","#d2f4d2","#caf3ca","#c3f1c3","#bbefbb","#76e076"],
			    data: <?php echo json_encode($scroll[1]); ?>
			  }]
			}
			var myScrollChart = new Chart(ctxScroll).Bar(scroll, options)
			
			/* Device Chart */
			var ctxDevice = document.getElementById("device").getContext('2d');
			var device = [
				{
					value: <?php echo json_encode($device[1][1]); ?>,
					label: <?php echo json_encode($device[0][1]); ?>,
					color: "#34495e"
				},
				{
					value: <?php echo json_encode($device[1][0]); ?>,
					label: <?php echo json_encode($device[0][0]); ?>,
					color: "#95a5a6"
				}
			];
			var myDeviceChart = new Chart(ctxDevice).Doughnut(device, options);

		</script>
	</body>
	</html>