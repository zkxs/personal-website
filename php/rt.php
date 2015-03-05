<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="description" content="Rotten Tomatoes Scraper" />
		<meta name="keywords" content="rotten,tomatoes,scraper" />
		<meta name="author" content="Michael Ripley" />
		<title>Rotten Tomatoes Scraper</title>
	</head>
	<body>
<?php
	header('Content-Type: text/html');
	
	if(!empty($_GET['id'])) {
		
		// example url: http://www.rottentomatoes.com/m/raiders_of_the_lost_ark/
		// example id:  raiders_of_the_lost_ark
		
		$id = $_GET['id'];
		
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
			echo "<p>Error: invalid id</p>";
			exit(1);
		}
		$url="http://www.rottentomatoes.com/m/" . $id . "/";
		
		$doc = new DOMDocument();
		$doc->loadHTMLFile($url);
		$xpath = new DOMXPath($doc);
		
		//$title = $xpath->evaluate("string(//span[@itemprop='name']/span[@class='h3 subtle'])");
		
		$titleNodes = $xpath->query("//h1[@class='movie_title']/span[@itemprop='name']");
		
		if ($titleNodes->length != 1) {
			echo "<p>Error: invalid page: " . $url . "</p>";
			exit(1);
		}
		$titleNode = $titleNodes->item(0);
		
		$title = trim($xpath->evaluate("string(./text())", $titleNode));
		$year = trim($xpath->evaluate("string(./span[@class='h3 subtle'])", $titleNode), "() \t\n\r\0\x0B");
		$rating = $xpath->evaluate("string(//div[@id='all-critics-numbers']//a[@id='tomato_meter_link']/span[@class='meter-value']/span[@itemprop='ratingValue'])");
		
		echo "\t\t<table>\n\t\t\t<tr>\n";
		echo "\t\t\t\t<td>" . $title . "</td>\n";
		echo "\t\t\t\t<td>" . $year . "</td>\n";
		echo "\t\t\t\t<td>" . $rating . "%</td>\n";
		echo "\t\t\t</tr>\n\t\t</table>\n";
	} else {
		echo "<p>:(</p>";
	}
?>
	</body>
</html>
