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
		<script src="/js/jquery.min.js"></script>
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
				<div id="swfSlot">
<?php include $_SERVER['DOCUMENT_ROOT'].'/php/randomwillswf.php'; ?>
				</div>
				<div style="text-align: center; margin-top: 30px">
					<span class="dim" style="position: relative; margin-left: auto; margin-right: auto">
						<a href="/index.shtml"><span class="linkoverlay"></span></a> <!-- MAGIC to make the entire parent element, which must be relative, a clickable link -->
						...but if you actually need the real index it's
						<a style="position: relative; z-index: 2" class="dim" href="/index.shtml">here</a><!-- Extra sparkles to make the real link still underline on mouseover -->
					</span>
				</div>
				
				<a href="#" onclick="refreshSwf()">derp</a>
				
			</main>
			<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/footer.html'; ?>
		</div>
		<?php include $_SERVER['DOCUMENT_ROOT'].'/snippets/piwik.html'; ?>
		<script type="text/javascript">
			function refreshSwf() {
				$('#swfSlot').load('/php/randomwillswf.php', queueRefresh);
			}
			function queueRefresh() {
				var time = parseFloat($('#randomSWF').attr('time')); // time in seconds 
				if (time > 10)
				{
					console.log("Refresh queued in " + time + " seconds");
					setTimeout(refreshSwf, Math.floor(1000 * parseFloat(time)));
				}
				else
				{
					console.log("This object is too short, so ne refresh was queued");
				}
			}
			queueRefresh();
		</script>
	</body>
</html>
