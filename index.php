<?
    require('secrets.php');
	function debug_r($text)
	{
		echo '<pre>';
		print_r($text);
		echo '</pre>';
	}

	if(file_exists('cache'))
		$cache = unserialize(file_get_contents('cache'));
	else
		$cache = array();

	if(isset($_REQUEST['debug']))
	{
		debug_r($_REQUEST);
		debug_r($cache);
		$debug = 1;
	}
?>

<html>
	<head>

		<title> WherethefuckRU - Rutgers Off Campus Bus Stop Assistant </title>
		<!--<link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />-->
		<link href="offcampus.css" rel="stylesheet" type="text/css" />

		<script type="text/javascript" src="http://maps.google.com/maps/api/js?key=<?=$apikey?>&sensor=false"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	</head>

	<body>
		<div id="center">
			<h1> Wherethefuck<span id="red">RU</span></h1>
			<a id="help-link" href="#">What's this? </a>
			<div id="help-text">
				<a id="hide-help-link" href="#"> Hide </a>
				<p> WherethefuckRU is a service that allows you to search an address around Rutgers, and figure out where it is relative to common bus stops. It shows you a map of walking directions from the closest bus stop. This is especially useful if you are looking for Off Campus housing. </p>
			</div>
		<?php $stops = unserialize(file_get_contents('output')); ?>
			<form id="stop-form" action="" method="get">
				Address<br />
				<input id="address" type="text" name="address" value="<?=@$_REQUEST['address']?>" /><br />
				<br />
				Stops to search:<br />
				<?php
					$default_search_stops = array(1055, 1064, 1001, 1052, 1062, 1016);
					if(isset($_REQUEST['search_stops']))
						$default_search_stops = $_REQUEST['search_stops'];
					foreach($stops as $stop) {
						echo ' <input type="checkbox" '.((in_array($stop['stopId'], $default_search_stops)) ? 'checked="checked"' : '').' name="search_stops[]" value="'.$stop['stopId'].'"> &nbsp;&nbsp;&nbsp; '.$stop['title'].'<br />';
					}

				?>
				<? if(isset($_REQUEST['debug'])) { ?>
					<input type="hidden" name="debug" value="1">
				<? } ?>
				<br />
				<input id="submit" type="Submit" value="Submit"/>
			</form>

			<script type="text/javascript">
				var directionDisplay;
				var directionsService = new google.maps.DirectionsService();
				var map;


				function initialize() {
					directionsDisplay = new google.maps.DirectionsRenderer();
					var myOptions = {
						zoom:7,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
					};
					map = new google.maps.Map($("#map_canvas")[0], myOptions);
					directionsDisplay.setMap(map);
				};

				$(window).load(initialize);

				$("#help-link").click(function(event) {
					$(this).hide();
					$("#help-text").show();
					event.preventDefault();
				});

				$("#hide-help-link").click(function(event) {
					$("#help-text").hide();
					$("#help-link").show();
					event.preventDefault();
				});

				$(function() {
					$("#help-text").hide();
				});

				var geocoded = '';
				var stops = <?=json_encode($stops)?>;

				var minstop = '';
				var minduration = 10000000000;
				var mindistance_text = '';
				var minduration_text = '';

				var address;
				var init_address;

				$("#stop-form").submit(function() {
					minduration = 10000000000;
					address = $("#address").val();

					init_address = address;

					address += " near New Brunswick, NJ";

					var checkboxes = $(this).find(':checked');

					var checked_boxes = [];

					$.each(checkboxes, function(index, element) {
						checked_boxes.push(element.value);
					});

					var geocoder = new google.maps.Geocoder();

					geocoder.geocode({'address': address}, function(results, status) {
						if(status == google.maps.GeocoderStatus.OK) {
							geocoded = results[0].geometry.location;

							var table_div = $('#table_div');
							table_div.html('');

							var table = $('<table>').appendTo('#table_div');
							table.append('<tr><th>Stop</th><th>Distance</th><th>Walking Time</th></tr>');
							$.each(stops, function(index, stop) {
								if($.inArray(stop.stopId, checked_boxes) == -1)
									return true;

								var origin = geocoded;
								var dest = new google.maps.LatLng(stop.lat, stop.lon);

								var request = {
									origin:origin,
									destination:dest,
									travelMode: google.maps.DirectionsTravelMode.WALKING,
								};
								directionsService.route(request, function(response, status) {
									if (status == google.maps.DirectionsStatus.OK) {
										var info = response.routes[0].legs[0];
										var duration = info.duration.value;

										var distance = info.distance.value;

										if(duration < minduration)
										{
											minduration = duration;
											mindistance = distance;
											mindistance_text = info.distance.text;
											minduration_text = info.duration.text;

											directionsDisplay.setDirections(response);

											$('#map_text').html('<br />And here\'s a pretty map to make you happy..\n<br />');

											$('#closest_span').html('The closest Rutgers bus stop to '+init_address+' is '+stop.title+', which is <b>'+mindistance_text+'</b> away, and <b>'+minduration_text+'</b> of a walk.\n<br />');
										}

										table.append('<tr><td>'+stop.title+'</td><td>'+info.distance.text+'</td><td>'+info.duration.text+'</td></tr>');
									}
								});
							});
						}
					});

					return false;
				});

			</script>

			<div id="table_div"> </div>

			<span id="closest_span"> </span>

			<span id="map_text"> </span>
			<div id="map_canvas" style="width: 800px; height: 600px;"> </div>

	<?
		if(isset($_REQUEST['address'])):
			$origin = $_REQUEST['address'];
			$minresult = NULL;
			$minstop = '';
			if(isset($_REQUEST['search_stops']))
				$search_stops = $_REQUEST['search_stops'];
			else
				$search_stops = array();


			echo "You entered $origin as the Origin. \n<br />";
			$origin .= ' near New Brunswick, NJ';

			$origin = strtolower($origin);
		?>
			<table>
				<tr>
					<th> Stop </th>
					<th> Distance </th>
					<th> Walking Time </th>
				</tr>
		<?
			foreach($stops as $stop)
			{
				if(!in_array($stop['stopId'], $search_stops))
					continue;
				$url = 'http://maps.googleapis.com/maps/api/directions/json?mode=walking&sensor=false&';

				$geocoded_origin = $origin;

				if(isset($cache[$origin]))
				{
					$geocoded_origin = $cache[$origin];
					if(isset($debug))
						echo 'Cache hit for '.$origin." \n<br />";
				}

				$query_params = array(
					'origin' => $geocoded_origin,
					'destination' => $stop['lat'].','.$stop['lon'],
				);

				$url = $url.http_build_query($query_params);

				$content = file_get_contents($url);
				$result = json_decode($content);

				if(isset($debug))
					debug_r($result);

				$latlong = (string)($result->routes[0]->legs[0]->start_location->lat.','.$result->routes[0]->legs[0]->start_location->lng);

	//			echo "Caching ".$geocoded_origin.' latlon '.$latlong.' <br />';
				$cache[$geocoded_origin] = $latlong;

	//			debug_r($cache);


				$duration = $result->routes[0]->legs[0]->duration->value;
				$duration_text = $result->routes[0]->legs[0]->duration->text;

				$distance = $result->routes[0]->legs[0]->distance->text;

				echo '<tr>';
				echo '<td> '.$stop['title']. '</td>';
				echo '<td> '.$distance. '</td>';
				echo '<td> '.$duration_text. '</td>';
				echo '</tr>';

				if(!$minresult)
				{
					$minresult = $result->routes[0]->legs[0];
					$minstop = $stop;
				}
				if($minresult->duration->value > $duration)
				{
					$minresult = $result->routes[0]->legs[0];
					$minstop = $stop;
				}
			}

			echo '</table>';

			file_put_contents('cache', serialize($cache));

			echo "The closest stop is <b>".$minstop['title']."</b> which is <b> ".$minresult->distance->text."</b> away and a ".$minresult->duration->text." walk.\n<br />";

			$origin_arr = explode(',', $geocoded_origin);
			$origin_lat = $origin_arr[0];
			$origin_lon = $origin_arr[1];

			$dest_lat = $minstop['lat'];
			$dest_lon = $minstop['lon'];
		?>


			<script type="text/javascript">

			  function calcRoute() {
			  }
			</script>

		<? endif; ?>

		</div>
		<center id="copyright"> Copyright&copy; 2011 <a href="/"> Vaibhav Verma </a> </center>
	</body>
</html>
