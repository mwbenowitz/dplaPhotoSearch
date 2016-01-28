<!DOCTYPE html>
<html>
<head>
	<title>Image frequency in the DPLA collections</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/bootstrap.min.js"></script>
	<link href="css/bootstrap.css" rel="stylesheet">
</head>
<body>
<div class="container">
	<div class="all-content">
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
			<h2>How often do certain photographs of certain topics appear in the DPLA collections?</h2>
			<p>This was a question I found myself asking as I explored photographs of sports in the DPLA collection. I could view the frequency at which one topic appeared, but not compare two or more of them. To answer this question I threw together the app below, charts this data for any combination of search terms and years (or well, you know, the years since photography was invented). </p>

			<p></p>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
			<form method="get">
				<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<label for="topicSearch">Enter the topics you would like to search by, separated by commas</label>
					<input class="form-control" type="text" name="topicSearch" id="topicSearch" size="100">
				</div>
				<div class="form-group col-xs-12 col-sm-6 col-md-4 col-lg-2">
					<label for="dateStart">Start Year</label>
					<input class="form-control" type="text" name="dateStart" id="dateStart">
				</div>
				<div class="form-group col-xs-12 col-sm-6 col-md-4 col-lg-2">
					<label for="dateEnd">End Year</label>
					<input class="form-control" type="text" name="dateEnd" id="dateEnd">
				</div>
				<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
					<button type="submit" class="btn btn-default btn-primary btn-lg">Submit</button>
				</div>
			</form>
			<?php
			//Create the curl_multi object
			$apiBase = 'http://api.dp.la/v2/';
			$apiKey = '7aa125caefd81154b6617af182b00976';
			if(isset($_GET['topicSearch']) && isset($_GET['dateStart']) && isset($_GET['dateStart'])) {
				$topics = $_GET['topicSearch'];
				$start = (int)$_GET['dateStart'];
				$end = $_GET['dateEnd'];
				$topicsData = array();
				$topics = explode(',', $topics);
				$curlCount = 0;
				$curls = array();
				$multiCurl = curl_multi_init();
				foreach($topics as $value){
					$value = urlencode(trim($value));
					$topicsData[$value] = [];
					for ($i=$start; $i<=$end; $i++){
						$url = $apiBase."items?q=".$value."&sourceResource.date.after=".$i."&sourceResource.date.before=".$i."&sourceResource.type=image&fields=object&api_key=".$apiKey;
						$curls[$curlCount] = curl_init();
						$timeout = 5;
						curl_setopt($curls[$curlCount], CURLOPT_URL, $url);
						curl_setopt($curls[$curlCount], CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curls[$curlCount], CURLOPT_CONNECTTIMEOUT, $timeout);
						curl_multi_add_handle($multiCurl, $curls[$curlCount]);
						$curlCount++;
					};
				};
				$active = null;
				$tempYears = array();
				$yearMarker = 0;
				do {
    				$curlResult = curl_multi_exec($multiCurl, $active);
				} while ($active > 0);
				foreach($curls as $value) {
					$html = curl_multi_getcontent($value);
					$html = json_decode($html, TRUE);
					$tempYears[$yearMarker] = $html['count'];
					curl_multi_remove_handle($multiCurl, $curls[$multiCurl]);
					$yearMarker++;
				};
				curl_multi_close($multiCurl);
				
				$span = $end - ($start-1);
				$yearSpans = array_chunk($tempYears, $span);
				$chunkCount = 0;
				foreach($topicsData as $key => $value){
					$topicsData[$key] = $yearSpans[$chunkCount];
					$chunkCount++;
				};
				$years = array();
				for ($i=$start; $i<=$end; $i++){
					$years[] = (string)$i;
				};
			};
			if (isset($_GET['topicSearch'])){
				echo '<div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>';
			}
			?>
			<script type="text/javascript">
			$(function () {
				<?php if(isset($years)) {
					echo 'var years = '.json_encode($years);
				}
				?>;
			    $('#container').highcharts({
			        title: {
			            text: 'Topic Frequency per Year in the DPLA Photograph Collection',
			            x: -20 //center
			        },
			        xAxis: {
			            categories: years
			        },
			        yAxis: {
			            title: {
			                text: 'Number of Photographs'
			            },
			            plotLines: [{
			                value: 0,
			                width: 1,
			                color: '#808080'
			            }]
			        },
			        legend: {
			            layout: 'vertical',
			            align: 'right',
			            verticalAlign: 'middle',
			            borderWidth: 0
			        },
			        series: [<?php
			        if (isset($topicsData)){
			        	foreach ($topicsData as $key=>$value) {
			        		echo '{ name: "'.str_replace("+", " ", $key).'",';
			        		echo 'data: '.json_encode($value)."},";
			    		};
			    	}
			    	?>
			    	]
			    });
			});
			</script>
			<script src="https://code.highcharts.com/highcharts.js"></script>
			<script src="https://code.highcharts.com/modules/exporting.js"></script>
	</div>
</div>
</div>
</div>
</body>
</html>