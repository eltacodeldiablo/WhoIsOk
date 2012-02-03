<?php
	//include
	require_once('twitteroauth/twitteroauth.php');
	require_once('twitteroauth/OAuth.php');
	require_once('config.php');
	session_start();

	/* If access tokens are not available redirect to connect page. */
	if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
		header('Location: ./clearsessions.php');
	}
	
	/* Get user access tokens out of the session. */
	$access_token = $_SESSION['access_token'];

	/* Create a TwitterOauth object with consumer/user tokens. */
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
	$connection->format = 'json';
	
	//variables
	$you = $connection->get('account/verify_credentials');
	$yourid = $you->id;
	//echo $yourid;
	
	//all your friends
	$allFriends = $connection->get('friends/ids');
	//array of friends who have responded
	$friendsResponded = array();
	//check if posted already (its a counter so that WhoIsOk only shows the most recent update
	$twitterCount = array();
	
	$numberOkay = 0;
	$numberNotOkay = 0;
		
	$timeS = $_POST['timeStamp'];
	$locationOfDisaster = $_POST['address1'];
	$radiusToSearch = $_POST['radius'];
	
	$distanceFromDisaster = 0;
	$lonDisaster = $_GET['lon']; 
	$latDisaster = $_GET['lat'];
	$addressDisaster = $_GET['address'];

	//distance formula
	function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
	{
		$pi80 = M_PI / 180;
		$lat1 *= $pi80;
		$lng1 *= $pi80;
		$lat2 *= $pi80;
		$lng2 *= $pi80;

		$r = 6372.797; // mean radius of Earth in km
		$dlat = $lat2 - $lat1;
		$dlng = $lng2 - $lng1;
		$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		$km = $r * $c;

		return ($miles ? ($km * 0.621371192) : $km);
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>WhoIsOk Twitter</title>
    <link rel="stylesheet" type="text/css" href="style.css" />
	<script src="http://maps.google.com/maps?file=api&v=2&key=cNHxRSXmhpdJ16gmFpWBcKccMtsjFxmCw" type="text/javascript"></script>
<!-- According to the Google Maps API Terms of Service you are required display a Google map when using the Google Maps API. see: http://code.google.com/apis/maps/terms.html -->
    <script type="text/javascript">
 
    var geocoder, location1, location2, lat1;
 
	function initialize() {
		geocoder = new GClientGeocoder();
	}
 
	function showLocation() {
		geocoder.getLocations(document.forms[0].address1.value, function (response) {
			if (!response || response.Status.code != 200)
			{
				alert("Sorry, we were unable to geocode the first address");
			}
			else
			{
				location1 = {lat: response.Placemark[0].Point.coordinates[1], lon: response.Placemark[0].Point.coordinates[0], address: response.Placemark[0].address};
				geocoder.getLocations(document.forms[0].address2.value, function (response) {
					if (!response || response.Status.code != 200)
					{
						alert("Sorry, we were unable to geocode the second address");
					}
					else
					{
						location2 = {lat: response.Placemark[0].Point.coordinates[1], lon: response.Placemark[0].Point.coordinates[0], address: response.Placemark[0].address};
						//calculateDistance();
						lat1 = location1.lat;
						lon1 = location1.lon;
						window.location.href = "./index.php?lat=" + lat1 + "&lon=" + lon1;
					}
				});
			}
		});
	}
 
	function calculateDistance()
	{
		try
		{
			var glatlng1 = new GLatLng(location1.lat, location1.lon);
			var glatlng2 = new GLatLng(location2.lat, location2.lon);
			var miledistance = glatlng1.distanceFrom(glatlng2, 3959).toFixed(1);
			var kmdistance = (miledistance * 1.609344).toFixed(1);
 
			document.getElementById('results').innerHTML = '<strong>Address 1: </strong>' + location1.address + '<br /><strong>Address 2: </strong>' + location2.address + '<br /><strong>Distance: </strong>' + miledistance + ' miles (or ' + kmdistance + ' kilometers)';
		}
		catch (error)
		{
			alert(error);
		}
	}
 
    </script>
  </head>
 
  <body onload="initialize()">
 		<div id="container">
		
			<h2>WhoIsOk Twitter</h2>

			<form method = "post" action="<?php echo $PHP_SELF;?>">
				<label>Location of Disaster:<input type="text" name="address1" value="" class="address_input" size="30" /></label>
				<br />
				<label>Radius to look for (in miles):
					<select name="radius" >
					  <option value="5">5</option>
					  <option value="25">25</option>
					  <option value="50">50</option>
					  <option value="100">100</option>
					</select>		
				</label>
				<br />
				<label>Local Date & Time of Disaster:<input name="timeStamp" type="datetime-local" required="required" value="2010-12-04 17:00"/></label>
				<input type="submit" value="Submit Query"/>
			</form>
			
			<div id='leftnav'>Friends who have responded</div>
			<div id='content'>Friends who have yet to respond</div>
			
			<?php
				$result = $connection->get('statuses/friends_timeline');
				$timeSpecified = strtotime($timeS);
				
				//check if friend has responded
				foreach($result as $tweet) {
					//so it doesn't include yourself
					//if (($tweet->user->id) != ($yourid)) {
						$tweetDate = strtotime($tweet->created_at);
						if($tweetDate - $timeSpecified > 0) {
							array_push($friendsResponded, $tweet->user->id);
						}
					//}
				}
				
				foreach($result as $tweet)
				{
					if (in_array($tweet->user->id,$friendsResponded) && !in_array($tweet->user->id,$twitterCount))
					{
						array_push($twitterCount, $tweet->user->id);
						echo "<div id='leftnav'>
						<img src='".$tweet->user->profile_image_url."'>
						<strong>
							<a href='http://twitter.com/".$tweet->user->screen_name."'>".$tweet->user->name."
							</a>
						</strong> ".$tweet->text."<br />";
						if ($tweet->place != null)
						{
							$coord = $tweet->place->bounding_box->coordinates;
							$tweetlat = $coord[0][0][1];
							$tweetlon = $coord[0][0][0];
							echo $tweetlat.",".$tweetlon."<br />";
							echo $latDisaster.",".$lonDisaster;
							echo "from ".$tweet->place->full_name.", ".$tweet->place->country_code;
							if ($latDisaster == "" && $lonDisaster == "") {
								$dist = distance($latDisaster,$lonDisaster,$tweetlat,$tweetlon);
								echo " ".$dist." miles away.";
							}
						}
						echo "</div>";
						$numberOkay++;
						
						/*10.12 use or display the Content without a corresponding Google map, 
						unless you are explicitly permitted to do so in the Maps APIs Documentation, 
						the Street View API Documentation, or through written permission from Google 
						(for example, you must not use geocodes obtained through the Service except
						 in conjunction with a Google map, but the Street View API Documentation 
						 explicitly permits you to display Street View imagery without a corresponding 
						 Google map);*/
					}
				}
				
				//if not responded (doesn't include yourself)
				$friendsNotResponded = array_diff($allFriends,$friendsResponded);
				foreach ($friendsNotResponded as $fr)
				{
					$tweetPosition = 'content';
					$usersShow = 'users/show/'.$fr;
					$friendInfo = $connection->get($usersShow);
					$names = $friendInfo->name;
					echo "<div id='content'>
					<img src='".$friendInfo->profile_image_url."'>
					<strong>".$names."</strong><br /></div>";
					$numberNotOkay++;
				}
				
				echo "<div id='leftnav'>
					<strong>Number Okay: ".$numberOkay."</strong><br /></div>";
				echo "<div id='content'>
					<strong>Number Not Okay: ".$numberNotOkay."</strong><br /></div>";
				
			?>
		</div>
  </body>
</html>