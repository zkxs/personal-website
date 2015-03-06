<nav class="sidebar">
				<ul>
					<!-- <li><h1>Menu</h1></li> -->
					<li><a href="/">Home</a></li>
					<li><a href="/files">Files</a></li>
					<li><a href="/html.shtml">HTML Test</a></li>
					<li><a href="/tmp/exploit.shtml">Java Exploit</a></li>
					<li><a href="/dl/generate.php" title="One Time Download Link Generator">LinkGen</a></li>
					
					<?php
					$https = FALSE;
					if ( array_key_exists("HTTPS", $_SERVER) )
					{
						if ($_SERVER['HTTPS'] === "on")
						{
							$https = TRUE;
						}
					}
					
					if ($https)
					{
						$text = "Turn SSL off";
						$link = "http://zcraft.no-ip.org:8080" . $_SERVER['REQUEST_URI'];
					}
					else
					{
						$text = "Turn SSL on";
						$link = "https://zcraft.no-ip.org:7999" . $_SERVER['REQUEST_URI'];
					}
					?>
					
					
					<li><a href="<?=$link ?>">
						<?=$text ?>
					</a></li>
					<!-- <li><a href="/html5bpindex.html" title="HTML5 Boilerplate Index">HTML5bp Index</a></li> -->
				</ul>
			</nav>
