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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>WhoIsOk Twitter</title>
    <link rel="stylesheet" type="text/css" href="style.css" />
  </head>
 
  <body>
 		<div id="container">
		
			<h2>WhoIsOk Twitter</h2>
			
			<form method = "post" action="<?php echo $PHP_SELF;?>">
				<label>Local Date & Time of Disaster:<input name="timeStamp" type="datetime-local" required="required" value="2010-12-04 17:00"/></label>
				<input type="submit" value="Submit Query"/>
				
			</form>
			
			<div id='leftnav'>Friends who have responded</div>
			<div id='content'>Friends who have yet to respond</div>
			<?php
				$result = $connection->get('statuses/friends_timeline');
				$timeSpecified = strtotime($_POST['timeStamp']);
				
				//check if friend has responded
				foreach($result as $tweet) {
					//so it doesn't include yourself
					if (($tweet->user->id) != ($yourid)) {
						$tweetDate = strtotime($tweet->created_at);
						if($tweetDate - $timeSpecified > 0) {
							array_push($friendsResponded, $tweet->user->id);
						}
					}
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
							echo "from ".$tweet->place->full_name.", ".$tweet->place->country_code;
						}
						echo "</div>";
						$numberOkay++;
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