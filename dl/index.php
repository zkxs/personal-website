<?php
/*
*
* One Time Download
* Jacob Wyke
* jacob@frozensheep.com
*
*/

//The directory where the download files are kept - keep outside of the web document root
$strDownloadFolder = $_SERVER['DOCUMENT_ROOT']."/dl/files/";

//connect to the DB
$dburl  = file_get_contents("dburl.secret");
$dbuser = file_get_contents("dbuser.secret");
$dbpass = file_get_contents("dbpass.secret");
$dbname = file_get_contents("dbname.secret");
$resDB = mysql_connect($dburl, $dbuser, $dbpass);
mysql_select_db($dbname, $resDB);

if(!empty($_GET['key'])){
	//check the DB for the key
	$resCheck = mysql_query("SELECT * FROM downloads WHERE downloadkey = '".mysql_real_escape_string($_GET['key'])."' LIMIT 1");
	$arrCheck = mysql_fetch_assoc($resCheck);
	if(!empty($arrCheck['file'])){
		//check that the download time hasnt expired
		if($arrCheck['expires']>=time()){
			if(!$arrCheck['downloads'] OR $arrCheck['allowmultiple']){
				//everything is hunky dory - check the file exists and then let the user download it
				$strDownload = $strDownloadFolder.$arrCheck['file'];
				
				if(file_exists($strDownload)){
					
					// use X-Sendfile because it's way easier than hacky PHP shenanigans
					header("X-Sendfile: " . $strDownload);
					header("Content-type: application/octet-stream");
					header('Content-Disposition: attachment; filename="' . basename($strDownload) . '"');
					
					// echo the file to the user
					//echo $strFile;
					
					// update the DB to say this file has been downloaded
					mysql_query("UPDATE downloads SET downloads = downloads + 1 WHERE downloadkey = '".mysql_real_escape_string($_GET['key'])."' LIMIT 1");
					
					exit;
					
				}else{
					echo "We couldn't find the file to download. Bug Michael about it.";
				}
			}else{
				//this file has already been downloaded and multiple downloads are not allowed
				echo "This file has already been downloaded.";
			}
		}else{
			//this download has passed its expiry date
			echo "This download has expired.";
		}
	}else{
		//the download key given didnt match anything in the DB
		echo "Invalid download key.";
	}
}else{
	//No download key wa provided to this script
	echo "No download key was provided.";
}

?>
