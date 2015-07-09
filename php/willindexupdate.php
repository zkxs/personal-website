<?php
	ob_end_clean();
	header("Content-Encoding: none\r\n");
	header('Content-Type: text/plain');
	header("Connection: close");
	ignore_user_abort(true); // just to be safe
	ob_start();

	// filter out files we don't want to copy
	$badFilenames = array(
		'/iron[^a-z]*man/',           // extremely loud
		'/flow\.swf/',                // game
		'/hyper[^a-z]*railgun/',      // has start button
		'/james[^a-z]*driving/',      // has start button
		'/bad[^a-z]*apple/',          // ?
		'/citronnade[^a-z]*flower/',  // has start button "CITRONNADE-FLOWER.swf"
		'/rolling[^a-z]*udonge/',     // ?
		'/wew12/',                    // ?
		'/nichijou[^a-z]*white/'      // use 'white.swf' instead please (duplicate)
	);

	function startsWith($haystack, $needle)
	{
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	function endsWith($haystack, $needle)
	{
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	class MyRecursiveFilterIterator extends RecursiveFilterIterator
	{

		public function accept()
		{
			global $badFilenames;
			global $logFile;
			$file = $this->current();
			$filename = $file->getFilename();
			$filepath = $file->getPathname();
			// Skip hidden files and directories.
			if ($filename[0] === '.') // must not start with a dot
			{
				//fwrite($logFile, "Ignoring file: '$filepath' Reason: Starts with '.'\n");
				return false;
			}
			else if ($file->isDir()) // directories are OK
			{
				if ($file->isReadable() && $file->isExecutable()) // but only if we can read them
				{
					return true;
				}
				else
				{
					fwrite($logFile, "Ignoring directory: '$filepath' Reason: Not readable/executable\n");
					return false;
				}
			}
			else if (!$file->isReadable()) // file must be readable
			{
				fwrite($logFile, "Ignoring file: '$filepath' Reason: Not readable\n");
				return false;
			}
			else if ( !(
				endsWith($filename, '.swf') ||
				endsWith($filename, '.jpg') ||
				endsWith($filename, '.png') )
			) // must end in swf, jpg, or png
			{
				fwrite($logFile, "Ignoring file: '$filepath' Reason: Invalid extension\n");
				return false;
			}

			// blacklist bad filenames
			$filenameLower = strtolower($filename);
			foreach ($badFilenames as $pattern)
			{
				//echo "checking $filenameLower against $pattern: ";
				if (preg_match($pattern, $filenameLower))
				{
					fwrite($logFile, "Ignoring file: '$filepath' Reason: Manually blacklisted\n");
					return false;
				}
			}

			return true;
		}
	}

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

	function getSimpleName($filename)
	{
		$filename = strtolower($filename);                             // upper to lower case
		$filename = preg_replace('/[\s]/', '_', $filename);            // remove whitespace
		$filename = preg_replace("/'/", '', $filename);                // remove the ' character
		$filename = preg_replace('/[)(]/', '_', $filename);            // parens to '_'
		$filename = preg_replace('/^_+/', '', $filename);              // remove leading '_'
		$filename = preg_replace('/[!~;:#?&]/', '_', $filename);       // weird characters to '_'
		$filename = preg_replace('/__+/', '_', $filename);             // collapse multiple '__' sequences
		$filename = preg_replace('/[-_]{2,}/', '-', $filename);        // collapse adjacent '-' and '_' to a '-'
		$filename = preg_replace('/[._]{2,}/', '.', $filename);        // collapse adjacent '.' and '_' to a '.'
		$filename = preg_replace('/(\.[a-z]+)\1+$/', '$1', $filename); // remove dual extensions
		//$filename = preg_replace('', '', $filename);
		return $filename;
	}

	require_once 'google-api-php-client/src/Google/autoload.php';
	$dburl	= file_get_contents('../dl/dburl.secret');
	$dbuser = file_get_contents('../dl/dbuser.secret');
	$dbpass = file_get_contents('../dl/dbpass.secret');
	$dbname = file_get_contents('../dl/dbname.secret');
	
	$logFilepath = "../img/will.log";
	$logFile = fopen($logFilepath, 'w') or exit('Could not open log file for writing.');

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
	
	
	http_response_code(202); // 202 Accepted
	echo('Initiated SWF update.');
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush(); // Unless both are called!

	// if you're using sessions, this prevents subsequent requests
	// from hanging while the background process executes
	if (session_id()) session_write_close();

	
	// Do background processing here
	/* ********************************************************************** */
	
	
	//mark all SWFS as gone
	$sql = <<<SQL
UPDATE swf
SET
	present = b'0';
SQL;
	$stmt = $db->prepare($sql);
	$stmt->execute();
	
	// remove all links
	$dstpath = '/var/www/img/will/';
	$files = glob($dstpath . '*'); // get all file names
	$directory = new RecursiveDirectoryIterator($dstpath, FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::SKIP_DOTS);
	$iterator = new RecursiveIteratorIterator($directory);
	foreach($iterator as $file){ // iterate files
		if(is_link($file))
		{
			unlink($file); // delete file
		}
	}
	
	// prepare statement for use in loop
	$sql = <<<SQL
INSERT INTO swf
	(filename)
VALUES
	(:filename)
ON DUPLICATE KEY UPDATE
	present = b'1';
SQL;
	$stmt = $db->prepare($sql);
	
	
	$srcpath = '/home/wsmith/swf';
	$directory = new RecursiveDirectoryIterator($srcpath, FilesystemIterator::FOLLOW_SYMLINKS);
	$filter = new MyRecursiveFilterIterator($directory);
	$iterator = new RecursiveIteratorIterator($filter);
	foreach ($iterator as $info)
	{
		$simplename = getSimpleName($info->getFilename());
		//echo $info->getPathname() . " => " . $dstpath . $simplename . "\n";
		if (symlink($info->getPathname(), $dstpath . $simplename))
		{
			//fwrite($logFile, "Link Succeeded: [" . $info->getPathname() . " --> " . $dstpath . $simplename . "]\n");
			
			// link worked, stick it in the database
			$stmt->bindParam(':filename', $simplename, PDO::PARAM_STR);
			$stmt->execute();
		}
		else
		{
			// link failed
			
			if (error_get_last()["message"] == 'symlink(): File exists') // if duplicate files
			{
				fwrite($logFile, "Duplicate files: '" . $info->getPathname() . "' and '" . readlink($dstpath.$simplename) . "' \n");
			}
			else
			{
				fwrite($logFile, "Link Failed: " . error_get_last()["message"] . " [" . $info->getPathname() . " --> " . $dstpath . $simplename . "] \n");
			}
		}
	}
	fwrite($logFile, "Update complete.\n");
	fclose($logFile);
?>