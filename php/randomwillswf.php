<?php
	require ("swfheader.class.php");
	
	$errorLog = [];
	
	function errlog($message)
	{
		global $errorLog;
		array_push($errorLog, $message);
	}
	
	function random_pic($dir, $seen)
	{
		$files = glob($dir . '/*.*');
		
		if (!empty($_GET['swf'])) {
			$requested = $_GET['swf'];
			$requestedFile = $dir . '/' . $requested;
			if (file_exists($requestedFile))
			{
				return $requestedFile;
			}
			else
			{
				errlog('"' . $requested . '" was not found. Loading a random file.');
			}
		}
		
		$numFiles = count($files);
		
		foreach ($seen as $seenfile)
		{
			if ($numFiles <= 1)
			{
				break;
			}
			else
			{
				$index = array_search($dir . '/' . $seenfile['name'], $files);
				if ( $index !== false)
				{
					unset($files[$index]);
					//errlog("unset " . $seenfile['name']);
				}
				else
				{
					errlog('File "' . $seenfile['name'] . '" referenced in cookie is not valid.');
				}
				$numFiles -= 1;
			}
		}
		
		//errlog("files remaining: " . $numFiles);
		
		$file = array_rand($files);
		return $files[$file];
	}
	
	$debugPrint = true;
	
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
	
	$filepathLocal = random_pic($_SERVER['DOCUMENT_ROOT'].'/img/will', $seen);
	$filepath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $filepathLocal);
	$filename = preg_replace("/.*\//", "", $filepath);
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
		
		$width =  (int) ($width  * $mult);
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
		
		$width =  (int) ($width  * $mult);
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