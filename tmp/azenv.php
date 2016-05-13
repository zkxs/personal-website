<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="description" content="A-Z Environment variables 1.04" />
		<meta name="keywords" content="variables,environment,php,michael" />
		<meta name="author" content="Michael Ripley" />
		<title>AZ Environment variables 1.04</title>
	</head>
	<body>
	<pre>
<?php
		##########################################################################
		#
		#	AZ Environment variables 1.04 © 2004 AZ
		#	Civil Liberties Advocacy Network
		#	http://clan.cyaccess.com   http://clanforum.cyaccess.com
		#
		#	AZenv is written in PHP & Perl. It is coded to be simple,
		#	fast and have negligible load on the server.
		#	AZenv is primarily aimed for programs using external scripts to
		#	verify the passed Environment variables.
		#	Only the absolutely necessary parameters are included.
		#	AZenv is free software; you can use and redistribute it freely.
		#	Please do not remove the copyright information.
		#
		##########################################################################

		foreach ($_SERVER as $header => $value )
		{if(strpos($header , 'REMOTE')!== false || strpos($header , 'HTTP')!== false ||
			strpos($header , 'REQUEST')!== false) {echo $header.' = '.$value."\n";} }
		?>
	</pre>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/piwik.html'; ?>

	</body>
</html>
