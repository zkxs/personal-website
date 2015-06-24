<?php
	require ("swfheader.class.php");
	require_once 'google-api-php-client/src/Google/autoload.php';
	
	$dburl	= file_get_contents('../dl/dburl.secret');
	$dbuser = file_get_contents('../dl/dbuser.secret');
	$dbpass = file_get_contents('../dl/dbpass.secret');
	$dbname = file_get_contents('../dl/dbname.secret');
	$errorLog = [];
	$creds = false;
	
	try
	{
		$db = new PDO('mysql:host='.$dburl.';dbname='.$dbname, $dbuser, $dbpass,
			array(PDO::ATTR_EMULATE_PREPARES => false, 
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	}
	catch (PDOException $e)
	{
		errlog('Database error: ' . $e->getMessage());
	}

	function errlog($message)
	{
		global $errorLog;
		array_push($errorLog, $message);
	}
	
	function random_pic($dir)
	{
		if (!empty($_GET['swf'])) {
			$requested = $_GET['swf'];
			if (file_exists($dir . '/' . $requested))
			{
				return $requested;
			}
			else
			{
				errlog('"' . $requested . '" was not found. Loading a random file.');
			}
		}
		
		$sql = <<<SQL
	SELECT CONVERT(filename USING utf8) AS filename FROM swf
	WHERE enabled = b'1'
	AND PRESENT = b'1'
	ORDER BY RAND()
	LIMIT 1
SQL;
		global $db;
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$filename = $stmt->fetch(PDO::FETCH_NAMED)['filename'];
		return $filename;
	}
	
	$debugPrint = true;
	
	if (isset($_POST['idtoken']))
	{
		$token = $_POST['idtoken'];
		$client = new Google_Client();
		$client->setApplicationName("Will Index");
		$client->setAuthConfigFile('client_secrets.json');
		try
		{
			$ticket = $client->verifyIdToken($token);
		}
		catch (Google_Auth_Exception $e)
		{
			errlog('Authentication error: ' . $e->getMessage());
		}
		if ($ticket) {
			$creds = $ticket->getAttributes()['payload'];
			//TODO: You must also verify the the hd claim (if applicable) with the Payload.getHostedDomain() method
			
			$iss = $creds['iss'];
			$sub = $creds['sub'];
			$azp = $creds['azp'];
			$email = $creds['email'];
			$at_hash = $creds['at_hash'];
			$email_verified = $creds['email_verified'];
			$aud = $creds['aud'];
			$iat = $creds['iat'];
			$exp = $creds['exp'];
			$name = $creds['name'];
			$picture = $creds['picture'];
			$given_name = $creds['given_name'];
			$family_name = $creds['family_name'];
			$locale = $creds['locale'];
			
			// log tokens
			try
			{
				$sql = <<<SQL
INSERT INTO tokens (token, iss, sub, azp, email, at_hash, email_verified, aud, iat, exp, name, picture, given_name, family_name, locale)
VALUES (:token, :iss, :sub, :azp, :email, :at_hash, :email_verified, :aud, :iat, :exp, :name, :picture, :given_name, :family_name, :locale);
SQL;
				$stmt = $db->prepare($sql);
				$stmt->bindParam(':token', $token);
				$stmt->bindParam(':iss', $iss);
				$stmt->bindParam(':sub', $sub);
				$stmt->bindParam(':azp', $azp);
				$stmt->bindParam(':email', $email);
				$stmt->bindParam(':at_hash', $at_hash);
				$stmt->bindParam(':email_verified', $email_verified);
				$stmt->bindParam(':aud', $aud);
				$stmt->bindParam(':iat', $iat);
				$stmt->bindParam(':exp', $exp);
				$stmt->bindParam(':name', $name);
				$stmt->bindParam(':picture', $picture);
				$stmt->bindParam(':given_name', $given_name);
				$stmt->bindParam(':family_name', $family_name);
				$stmt->bindParam(':locale', $locale);
				$stmt->execute();
			}
			catch (PDOException $e)
			{
				errlog('Database error: ' . $e->getMessage());
			}
				
			// update/insert user
			try
			{
				$sql = <<<SQL
INSERT INTO user
	(googleID, email, nameGiven, nameFamily, lastSeen)
VALUES
	(:googleID, :email, :nameGiven, :nameFamily, now())
ON DUPLICATE KEY UPDATE
	email      = VALUES(email),
	nameGiven  = VALUES(nameGiven),
	nameFamily = VALUES(nameFamily),
	lastSeen   = now();
SQL;
				$stmt = $db->prepare($sql);
				$stmt->bindParam(':googleID', $sub);
				$stmt->bindParam(':email', $email);
				$stmt->bindParam(':nameGiven', $given_name);
				$stmt->bindParam(':nameFamily', $family_name);
				$stmt->execute();
			}
			catch (PDOException $e)
			{
				errlog('Database error: ' . $e->getMessage());
			}
		}
		
		//TODO do something with this!
	}
	else
	{
		//TODO
	}
	
	$cookie;
	$seen = [];
	if (isset($_COOKIE['willswfs']))
	{
		$cookie = $_COOKIE['willswfs'];
		if (!empty($cookie))
		{
			$seen = explode(':', $cookie);
		}
	}
	
	for($i = 0, $size = count($seen); $i < $size; ++$i) {
		$split_pair = explode('!', $seen[$i]);
		$seen[$i] = ['name' => $split_pair[0], 'time' => $split_pair[1]];
	}
	
	$localdir = '/img/will';
	$dir = $_SERVER['DOCUMENT_ROOT'].$localdir;
	$filename = random_pic($dir);
	$filepath = $localdir . '/' . $filename;
	$filepathLocal = $dir . '/' . $filename;
	$filenameNoExt = preg_replace("/\.[a-zA-Z0-9]{1,4}$/", "", $filename);
	$fileExt = strtolower(preg_replace("/^.*\.([a-zA-Z0-9]{1,4})$/", "$1", $filename));
	$filenameNoExt = strtoupper($filenameNoExt);
	$imageSize = getimagesize($filepathLocal);
	$mimeType = $imageSize["mime"];
	$width = $imageSize[0];
	$height = $imageSize[1];
	
	$extensions = array("jpg", "rar", "zip", "pdf", "txt", "ppt", "ttf", "c", "exe", "scr", "md5", "sfv");
	$randomExtension = strtoupper($extensions[array_rand($extensions)]);
	
	$swfLength = 0;
	if ($fileExt == "swf")
	{
		// Create a new SWF header object with debug info, open with
		//disabled debug (false) for silent processing
		$swf = new swfheader(false);
		// Open the swf file...
		// Replace filename accordingly to your test environment...
		$swf->loadswf($filepathLocal);
		
		$fps = (float)($swf->fps[1] . "." . $swf->fps[0]);
		$swfLength = $swf->frames / $fps; // this is a float
	}
	
	// upscale small swfs
	$desiredwidth = 840;
	$desiredheight = 480;
	if ($width < $desiredwidth && $height < $desiredheight )
	{
		// both multipliers are guaranteed to be greater than one
		// we want to take the one that is smaller
		$mult = min($desiredwidth / $width, $desiredheight / $height);
		
		$width =	(int) ($width	* $mult);
		$height = (int) ($height * $mult);
	}
	
	// downscale giant swfs
	$desiredwidth = 1280;
	$desiredheight = 720;
	if ($width > $desiredwidth || $height > $desiredheight )
	{
		// both multipliers are guaranteed to be less than one
		// we want to take the one that is smaller
		$mult = min($desiredwidth / $width, $desiredheight / $height);
		
		$width =	(int) ($width	* $mult);
		$height = (int) ($height * $mult);
	}
	
	$diaz = $mimeType != "application/x-shockwave-flash" && strpos($filenameNoExt, 'DIAZ') !== FALSE;
	if ($diaz)
	{
		$swfLength = 13;
	}
	
?>
<h2 style="text-align: center; margin: 0px;" title="actually .<?=$fileExt?>"><a href="<?=$filepath?>"><?=$filenameNoExt?>.<?=$randomExtension?></a></h2>
<div id="swfContainer">
	<object
		id="randomSWF"
		time="<?=$swfLength?>"
		class="centered"
		style="border: 1px solid black"
		type="<?=$mimeType?>"
		data="<?=$filepath?>"
		width="<?=$width?>"
		height="<?=$height?>">
			Object <a href="<?=$filepath?>"><?=$filename?></a> failed to display. No appropriate plugin was found.
	</object>
	<script type="text/javascript">
		var swf = document.randomSWF;
		if (swf)
		{
			queueRefresh('<?=$filename?>');
		}
		else
		{
			console.log("this should not happen");
		}
	</script>
</div>

<?php if($diaz): ?>
	<audio src="/files/twilight_zone.mp3" autoplay="" style="display: none">
		Your browser does not support the <code>audio</code> element.
	</audio>
<?php endif; ?>

<?php if(count($errorLog) > 0): ?>
<h3 id="errorhead">Errors have occured:</h3>
<pre id="errorlog">
<?php
		foreach ($errorLog as $message)
		{
			echo $message . "\n";
		}
?>
</pre>
<?php endif; ?>

<h3 id="debughead" style="display: none">Debugging Information:</h3>
<pre id="swfDebug" style="display: none">
<?php
	if ($debugPrint)
	{
		if ($creds)
		{
			echo '$creds = ';
			print_r($creds);
		}
		else
		{
			echo '$creds' . " = false\n";
		}
		
		echo '$filepath = ';
		echo $filepath . "\n";
		
		echo '$imageSize = ';
		print_r($imageSize);
		
		if ($fileExt == "swf") {
			echo '$fileExt = ';
			print_r($swf);
		}
		
		echo '$swfLength = ';
		echo $swfLength . "\n";
	}
?>
</pre>