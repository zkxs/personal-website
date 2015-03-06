<?php
	// lets keep all this PHP shit up at the top and just use inline vars in the actual HTML
	require ("swfheader.class.php");
	
	function random_pic($dir)
	{
		$files = glob($dir . '/*.*');
		$file = array_rand($files);
		return $files[$file];
	}
	
	$filepathLocal = random_pic($_SERVER['DOCUMENT_ROOT'].'/img/will');
	$filepath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $filepathLocal);
	$filename = preg_replace("/.*\//", "", $filepath);
	$filenameNoExt = preg_replace("/\.[a-zA-Z0-9]{1,4}$/", "", $filename);
	$fileExt = strtolower(preg_replace("/^.*\.([a-zA-Z0-9]{1,4})$/", "$1", $filename));
	$filenameNoExt = strtoupper($filenameNoExt);
	$imageSize = getimagesize($filepathLocal);
	$mimeType = $imageSize["mime"];
	$width = $imageSize[0];
	$height = $imageSize[1];
	
	$extensions = array("jpg", "rar", "flac", "pdf", "txt", "ppt", "html", "ttf", "exe", "wmv");
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
		$swfLength = $swf->frames / $fps; // this is a float TODO: is this safe?
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
?>					<h2 style="text-align: center; margin: 0px;" title="actually .<?=$fileExt?>"><?=$filenameNoExt?>.<?=$randomExtension?></h2>
					<object
						id="randomSWF"
						time="<?=$swfLength?>"
						class="centered"
						style="border: 1px solid black; background-color:#FF0000;"
						type="<?=$mimeType?>"
						data="<?=$filepath?>"
						width="<?=$width?>"
						height="<?=$height?>">
						Object <a href="<?=$filepath?>"><?=$filename?></a> failed to display. No appropriate plugin was found.
					</object>
