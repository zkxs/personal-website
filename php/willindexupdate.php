<?php
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
	header('Content-Type: text/plain');
	$dburl	= file_get_contents('../dl/dburl.secret');
	$dbuser = file_get_contents('../dl/dbuser.secret');
	$dbpass = file_get_contents('../dl/dbpass.secret');
	$dbname = file_get_contents('../dl/dbname.secret');
	
	if (!isset($_POST['token']))
	{
		http_response_code(401);
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
		http_response_code(401);
		exit('Authentication error: ' . $e->getMessage());
	}
	
	if (!$ticket) {
		http_response_code(500);
		exit('No ticket... not sure what could cause this');
	}
	
	$gid = $ticket->getAttributes()['payload']['sub'];
	
	// open database
	try
	{
		$db = new PDO('mysql:host='.$dburl.';dbname='.$dbname, $dbuser, $dbpass,
			array(PDO::ATTR_EMULATE_PREPARES => false, 
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
				
		
		try
		{
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
				http_response_code(403);
				exit('You do not have permission to do this');
			}
			
		}
		catch (PDOException $e)
		{
			http_response_code(500);
			exit('Database error: ' . $e->getMessage());
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
	exit('Everything is OK');
?>