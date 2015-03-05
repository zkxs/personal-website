<?php
	// lets keep all this PHP shit up at the top and just use inline vars in the actual HTML
	
	function random_pic($dir)
	{
		$files = glob($dir . '/*.*');
		$file = array_rand($files);
		return $files[$file];
	}
	
	$filepathlocal = random_pic($_SERVER['DOCUMENT_ROOT'].'/img/will');
	$filepath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $filepathlocal);
	$filename = preg_replace("/.*\//", "", $filepath);
	$filenamenoext = preg_replace("/\.[a-zA-Z0-9]{1,4}$/", "", $filename);
	$fileext = preg_replace("/^.*\.([a-zA-Z0-9]{1,4})$/", "$1", $filename);
	$filenamenoext = strtoupper($filenamenoext);
	$imagesize = getimagesize($filepathlocal);
	$mimetype = $imagesize["mime"];
	$width = $imagesize[0];
	$height = $imagesize[1];
	
	$extensions = array("jpg", "rar", "flac", "pdf", "txt", "ppt", "html", "ttf", "exe", "wmv");
	$randomextension = strtoupper($extensions[array_rand($extensions)]);
	
	// upscale small swfs
	$desiredwidth = 840;
	$desiredheight = 480;
	if ($width < $desiredwidth && $height < $desiredheight ) {
		// both multipliers are guaranteed to be greater than one
		// we want to take the one that is smaller
		$mult = min($desiredwidth / $width, $desiredheight / $height);
		
		$width =  (int) ($width  * $mult);
		$height = (int) ($height * $mult);
	}
?>
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
		<style>
			span.dim {
				color: #4D4D4D; /* I guess this is redundant now... */
				font-size: 0.6em;
				text-shadow: none;
			}
			a.dim:link, a.dim:visited {
				color: #295E77;
			}
			span.linkoverlay { /* This is a crazy hack that totally works */
				position:absolute; 
				width:100%;
				height:100%;
				top:0;
				left: 0;
				z-index: 1;
				background-image: url('/img/empty.gif'); /* fixes overlap error in IE7/8 */
			}
		</style>
	</head>
	<body>
		<div id="container">
			<header><h1>WILL THIS IS FOR YOU</h1></header>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/navbar.php'; ?>
			<main class="hasSideBar">
				<h2 style="text-align: center; margin: 0px;" title="actually .<?=$fileext?>"><?=$filenamenoext?>.<?=$randomextension?></h2>
				<object
					class="centered"
					style="border: 1px solid black; background-color:#FF0000;"
					type="<?=$mimetype?>"
					data="<?=$filepath?>"
					width="<?=$width?>"
					height="<?=$height?>">
					Object <a href="<?=$filepath?>"><?=$filename?></a> failed to display. No appropriate plugin was found.
				</object>
				<div style="text-align: center; margin-top: 30px">
					<span class="dim" style="position: relative; margin-left: auto; margin-right: auto">
						<a href="/index.shtml"><span class="linkoverlay"></span></a> <!-- MAGIC to make the entire parent element, which must be relative, a clickable link -->
						...but if you actually need the real index it's
						<a style="position: relative; z-index: 2" class="dim" href="/index.shtml">here</a><!-- Extra sparkles to make the real link still underline on mouseover -->
					</span>
				</div>
			</main>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/footer.html'; ?>
		</div>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/piwik.html'; ?>
	</body>
</html>
