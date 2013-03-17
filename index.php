<?php
require_once 'google-api-php-client/src/Google_Client.php';
require_once ('google-api-php-client/src/contrib/Google_PlusService.php');

// Set your cached access token. Remember to replace $_SESSION with a
// real database or memcached.

session_start();

if(isset($_REQUEST['logout']))
{
	session_destroy();
	header('Location: index.php');
	exit;
}

$client = new Google_Client();
$client->setApplicationName('Smileese');
// Visit https://code.google.com/apis/console?api=plus to generate your
// client id, client secret, and to register your redirect uri.
$client->setClientId('844185797978.apps.googleusercontent.com');
$client->setClientSecret('06qw-_VvWUcB4IdqiUTXQbT0');
$client->setRedirectUri('http://localhost/googleplus/index.php');
$client->setDeveloperKey('AIzaSyAitzlX3CXcuygrenEc5qEZBz9rR1giDVA');
$plus = new Google_PlusService($client);

if (isset($_GET['code'])) {
  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if ($client->getAccessToken()) {


	// print '<pre>' . print_r($plus->activities->listActivities('107367382218779881065', 'public'), true) . '</pre>';
  

// '':)'' or '':-)'' or '=)'	 Smile
// 	 ':D'or ':-D'	 Big Smile - show those teeth!
// 	 ':('or ':-(' or =(	 Sad
// 	 ':'('	 Crying
// 	 ':p 'or ':P 'or ':-p' or ':-P'	 Stick that tongue out
// 	 ':o 'or 8-0 or =8-0	 Shocked
// 	 ':@	' Angry
// 	 ':s 'or ':S	' Confused
// 	 ;) or ;-)	 Wink
// 	 ':$	' Embarrassed
// 	 ':|	' Disappointed
// 	 +o(	 Sick
// 	 ':-#'	 Shut Mouth
// 	 |-)	 Sleepy
// 	 8-)	 Eyeroll
// 	 ':\ 'or *-) or ':-\'	 Thinking
// 	 (lying) or ':--')	 Thinking
// 	 8-|	 Nerdy Smile
// 	 8o|	 Baring Teeth


// :) :D ;) :'( :-o :-/ x-( :( B-) :P <3 :-| '
  // $smileyList = array(':)' => 'Smile',
  // 						':-)' => 'Smile',
  // 						'=)' => 'Smile',
  // 						':D' => 'Big Smile',
  // 						':-D' => 'Big Smile',
  // 						':(' => 'Sad',
  // 						':-(' => 'Sad',
  // 						'=(' => 'Sad',
  // 						':\'(' => 'Crying',
  // 						':@' => 'Angry' 
  // 						);


function getSmileyProfile($id, $plus)
{
	$smileyList = array( 	'Smile' => array(':)', ':-)', '=)'),
  						'Big Smile' => array(':D', ':-D'),
  						'Exclamation' => array('!'),
  						'Sad' => array(':(', ':-(', '=('),
  						'Stick Tongue Out' => array(':P', ';P'),
  						'Crying' => array(':\'(', 'T.T'),
  						'Thinking' => array(':\\', ':-\\'),
  						'Wink' => array(';)', ';-)'),
  						'Embarrassed' => array(':$')
  						);
	
	$smileyCount = array();
	foreach ($smileyList as $key => $value) {
		$smileyCount[$key] = 0;
	}

	$activities = $plus->activities->listActivities($id, 'public', array('fields' => 'items(title), nextPageToken'));
	$i = 0;
	while($activities['nextPageToken'] && $i < 20)
	{  	
		foreach ($activities['items'] as $key => $post) {
			foreach ($smileyList as $smileType => $emoticonList) {
				foreach ($emoticonList as $emoticonNumber => $emoticon) {
					$n = substr_count($post['title'], $emoticon);
					if($n > 0)
						$smileyCount[$smileType]+=$n;
				}
			}
		}
		++$i;
		// print 'Your Activities: <pre>' . print_r($activities, true) . '</pre>';
		$activities = $plus->activities->listActivities($id, 'public', array('pageToken' => $activities['nextPageToken'], 'fields' => 'items(title), nextPageToken'));
	}
	foreach ($activities['items'] as $key => $post) {
		foreach ($smileyList as $smileType => $emoticonList) {
			foreach ($emoticonList as $emoticonNumber => $emoticon) {
				if(strstr($post['title'], $emoticon))
					$smileyCount[$smileType]++;
			}
		}
	}
	return $smileyCount;
}


//getPeopleList

// items(displayName,gender,id,isPlusUser,kind,language,nickname,objectType,plusOneCount,relationshipStatus,tagline,url,verified)
$nextPageExists = false;

if(!isset($_REQUEST['friends']))
{
	$mysp = getSmileyProfile('me', $plus);
	$finalTable['me'] = $mysp;
	$nextPageExists = false;
}
else
{
	if(isset($_REQUEST['nextPage']))
	{
		$people = $plus->people->listPeople('me', 'visible', array('maxResults' => 5, 'orderBy' => 'best', 'pageToken' => $_REQUEST['nextPage'],
			'fields' => 
		'items(displayName,gender,id,isPlusUser,kind,language,nickname,objectType,plusOneCount,relationshipStatus,tagline,url,verified), nextPageToken'));
	}
	else
	{
		$people = $plus->people->listPeople('me', 'visible', array('maxResults' => 5, 'orderBy' => 'best', 'fields' => 
		'items(displayName,gender,id,isPlusUser,kind,language,nickname,objectType,plusOneCount,relationshipStatus,tagline,url,verified), nextPageToken'));
	}

	if($people['nextPageToken'])
	{
		$nextPageExists = true;
		$nextPageToken = $people['nextPageToken'];
	}
	$finalTable = array();

	foreach ($people['items'] as $key => $person) {
		$sp = getSmileyProfile($person['id'], $plus);
		$finalTable[$person['displayName']] = $sp;
	}
}


?>
<a href='index.php?logout=1'>Logout</a>
<br>
<a href='index.php'>My Page</a>
<br>
<a href = 'index.php?friends'>Friends</a>
<br>
<?php


if($nextPageExists)
{
	print 'Next page exists' . "<br>";
	print '<a href="index.php?friends=1&nextPage='.$nextPageToken.'">Next</a>';
}


print '<pre>' . print_r($finalTable, true) . '</pre>';


	// print '<pre>' . print_r($people, true). '</pre>';
	// exit;




// foreach ($smileyCount as $key => $value) {
// 	print 'Count for '. $key . ' was '. $value."\n";
// }

  // We're not done yet. Remember to update the cached access token.
  // Remember to replace $_SESSION with a real database or memcached.
  $_SESSION['token'] = $client->getAccessToken();
} else {
  $authUrl = $client->createAuthUrl();
  print "<a href='$authUrl'>Connect Me!</a>";
}