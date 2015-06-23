<?php
	ob_end_clean();
	header("Content-Encoding: none\r\n");
	header('Content-Type: text/plain');
	header("Connection: close");
	ignore_user_abort(true); // just to be safe
	ob_start();

	function print_result($result) {
		echo "<table class=\"sortable\">\n";
		echo '<thead>';
		for ($i = 0; $i < $result->columnCount(); ++$i) {
			$metadata = $result->getColumnMeta($i);
			echo '<th>';
			echo $metadata['name'];
			echo '</th>';
		}
		echo "</thead>\n";

		while($row = $result->fetch(PDO::FETCH_NUM)) {
			echo '<tr>';
			foreach ($row as $entry) {
				echo '<td>';
				echo $entry;
				echo '</td>';
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	require_once 'google-api-php-client/src/Google/autoload.php';
	$dburl	= file_get_contents('../dl/dburl.secret');
	$dbuser = file_get_contents('../dl/dbuser.secret');
	$dbpass = file_get_contents('../dl/dbpass.secret');
	$dbname = file_get_contents('../dl/dbname.secret');
	
	if (!isset($_POST['token']))
	{
//		http_response_code(400);
		exit('No token supplied');
	}
	
	$token = $_POST['token'];
	$client = new Google_Client();
	$client->setApplicationName("Will Index");
	$client->setAuthConfigFile('client_secrets.json');
	try
	{
		$ticket = $client->verifyIdToken($token);
	}
	catch (Google_Auth_Exception $e)
	{
//		http_response_code(400);
		exit('Authentication error: ' . $e->getMessage());
	}
	
	if (!$ticket) {
//		http_response_code(500);
		exit('No ticket... not sure what could cause this');
	}
	
	$gid = $ticket->getAttributes()['payload']['sub'];
	
	// open database
	try
	{
		$db = new PDO('mysql:host='.$dburl.';dbname='.$dbname, $dbuser, $dbpass,
			array(PDO::ATTR_EMULATE_PREPARES => false, 
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		$sql = <<<SQL
SELECT COUNT(u.userID) AS count FROM map_user_permission m
LEFT JOIN user u ON m.userID = u.userID
LEFT JOIN permission p ON m.permissionID = p.permissionID
WHERE u.googleID LIKE :gid
AND p.permission LIKE 'swf.update';
SQL;
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':gid', $gid);
		$stmt->execute();
		$perm = $stmt->fetch(PDO::FETCH_NAMED)['count'];
		
		// $perm is 1 if we have the swf.update permission
		
		if ($perm != 1)
		{
//			http_response_code(403);
			exit('You do not have permission to do this');
		}
		
	}
	catch (PDOException $e)
	{
		http_response_code(500);
		exit('Database error: ' . $e->getMessage());
	}
	
	// at this point we would actually do the updating
	
	//TODO: do dat
	
	
	http_response_code(201);
	echo('Initiated SWF update.');
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush(); // Unless both are called!
	
	// if you're using sessions, this prevents subsequent requests
	// from hanging while the background process executes
	if (session_id()) session_write_close();
	
	// Do background processing here
	
	$sql = <<<SQL
UPDATE swf
SET
	present = b'0';
SQL;
	$stmt = $db->prepare($sql);
	$stmt->execute();
	
	$dir = $_SERVER['DOCUMENT_ROOT'].'/img/will';
	$files = glob($dir . '/*.*');
	
	$sql = <<<SQL
INSERT INTO swf
	(filename)
VALUES
	(:filename)
ON DUPLICATE KEY UPDATE
	present = b'1';
SQL;
	$stmt = $db->prepare($sql);
	
	foreach ($files as $filepath)
	{
		$filename = preg_replace("/.*\//", "", $filepath);
		$stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
		$stmt->execute();
	}
	
/* 	$sql = <<<SQL
DELETE FROM swf
WHERE present = b'0';
SQL;
	$stmt = $db->prepare($sql);
	$stmt->execute(); */
	
?>