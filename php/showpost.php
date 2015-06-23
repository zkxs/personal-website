<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="description" content="Michael Ripley's homepage" />
		<meta name="keywords" content="michael,ripley,home" />
		<meta name="author" content="Michael Ripley" />
		<title>Snazzy Landing Page</title>
		<link rel="stylesheet" type="text/css" href="/css/style.css">
		<!--[if lt IE 9]>
			<script src="/js/html5shiv.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<div id="container">
			<header><h1>Snazzy Landing Page</h1></header>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/navbar.php'; echo "\n"; ?>
			<main class="hasSideBar">
				<pre id="post">
<?php
	echo '$_GET = ';
	print_r($_GET);
	echo '$_POST = ';
	print_r($_POST);
	echo '$_FILES = ';
	print_r($_FILES);
?>
				</pre>
			</main>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/footer.html'; echo "\n"; ?>
		</div>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/piwik.html'; echo "\n"; ?>
	</body>
</html>