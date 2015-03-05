<?php
/*
*
* Create Download Link
* Jaocb Wyke
* jacob@frozensheep.com
*
*/

//connect to the DB
$dburl  = file_get_contents("dburl.secret");
$dbuser = file_get_contents("dbuser.secret");
$dbpass = file_get_contents("dbpass.secret");
$dbname = file_get_contents("dbname.secret");
$resDB = mysql_connect($dburl, $dbuser, $dbpass);
mysql_select_db($dbname, $resDB);

$downloadpath = $_SERVER['DOCUMENT_ROOT']."/dl/files/";

function createKey(){
	//create a random key
	$strKey = md5(microtime());
	
	//check to make sure this key isnt already in use
	$resCheck = mysql_query("SELECT count(*) FROM downloads WHERE downloadkey = '{$strKey}' LIMIT 1");
	$arrCheck = mysql_fetch_assoc($resCheck);
	if($arrCheck['count(*)']){
		//key already in use
		return createKey();
	}else{
		//key is OK
		return $strKey;
	}
}

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

if ( array_key_exists("filename",     $_POST)
  && array_key_exists("expirenumber", $_POST)
  && array_key_exists("expireunit",   $_POST)
  && array_key_exists("multiple",     $_POST) )
	$submitted = 1;
else
	$submitted = 0;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="description" content="Generate one-time use download links" />
		<meta name="keywords" content="one,time,download,link,generator" />
		<meta name="author" content="Michael Ripley" />
		<title>One Time Download Link Generator</title>
		
		<link rel="stylesheet" type="text/css" href="/css/style.css">
		<style type="text/css">
			table,th,td
			{
				border:0px;
				border-collapse:collapse;
				padding:5px;
			}
		</style>
		<!--[if lt IE 9]>
			<script src="/js/html5shiv.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<div id="container">
			<header><h1>One Time Download Link Generator</h1></header>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/navbar.php'; ?>

			<main class="hasSideBar">
				<form action="" method="post">
					<table>
						<tr>
							<td>
								File:
							</td>
							<td>
								<select name="filename">
<?php
if ($handle = opendir($downloadpath)) {
	while (false !== ($entry = readdir($handle))) {
		 if ($entry != "." && $entry != ".." && !startsWith($entry, '.ht')) {
			echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$entry.'">'.$entry.'</option>'."\n";
		}
	}
	closedir($handle);
}
?>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								Expires in
							</td>
							<td>
								<input type="number" name="expirenumber" min="0" step="1" value="30" required="required" />
								<select name="expireunit">
									<option value="1">seconds</option>
									<option value="60" selected="selected">minutes</option>
									<option value="3600">hours</option>
									<option value="86400">days</option>
									<option value="604800">weeks</option>
									<option value="2629800">months</option>
									<option value="31557600">years</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								Allow multiple downloads
							</td>
							<td>
								<input type="radio" name="multiple" value="1" />Yes
								<input type="radio" name="multiple" value="0" checked="checked" />No
							</td>
						</tr>
						<tr>
							<td>
								<input type="submit" value="Submit" />
							</td>
							<td></td>
						</tr>
					</table>
				</form>
<?php
if ( $submitted )
{
	//get a unique download key
	$strKey = createKey();
	$file = $_POST["filename"];
	$expires = time() + $_POST["expirenumber"] * $_POST["expireunit"];
	$expiresString = date("F j, Y g:i:s A", $expires);
	$multiple = $_POST["multiple"];
	$multipleString = $multiple ? "yes" : "no";

	//insert the download record into the database
	mysql_query("INSERT INTO downloads (downloadkey, file, expires, allowmultiple) VALUES ('{$strKey}', '{$file}', '{$expires}', '{$multiple}')");
}
?>
<?php if ($submitted) : ?>
				<p>
					Your unique download URL is:
					<strong><a href=".?key=<?=$strKey;?>"><?=$_SERVER['HTTP_HOST'];?>/dl?key=<?=$strKey;?></a></strong>
				</p>
				<table style="border:1px solid black">
					<tr>
						<td>Filename:</td>
						<td><a href="files/<?=$file;?>"><?=$file;?></a></td>
					</tr>
					<tr>
						<td>Link expiry date:</td>
						<td><?=$expiresString;?></td>
					</tr>
					<tr>
						<td>Allow multiple downloads:</td>
						<td><?=$multipleString;?></td>
					</tr>
				</table>
				<br />
				<p><i><a href="">Clear result</a></i></p>
<?php endif; ?>
			</main>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/footer.html'; ?>

		</div>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/piwik.html'; ?>

	</body>
</html>