var iframes;
iframes = document.evaluate(
	"//div[@class='sidebarAd']//iframe",
	document,
	null,
	XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE,
	null);
for (var i = 0; i < iframes.snapshotLength; i++)
{
	var iframe = iframes.snapshotItem(i); // the ad iframe
	var div = iframe.parentElement; // the ad div
	var safeDiv = div.parentElement; // a div that shouldn't be blocked
	
	/*~~~~~~~~~~~~~~~~~~~~ BEGIN TEST #1 ~~~~~~~~~~~~~~~~~~~~*/
	
	checkDiv(div, safeDiv);
	window.setInterval(function () {
		checkDiv(div, safeDiv);
    }, 3000); // repeat forever, polling every 3 seconds
	
	/*~~~~~~~~~~~~~~~~~~~~ END TEST #1 ~~~~~~~~~~~~~~~~~~~~*/
	
	/*~~~~~~~~~~~~~~~~~~~~ BEGIN TEST #2 ~~~~~~~~~~~~~~~~~~~~*/
	
	var src = iframe.src;
	var markIdx = src.indexOf('?');
	
	if (markIdx != -1)
	{
		// if there is a ?
		src = src.substring(0, src.indexOf('?')); // chop it off
	}
	src = src + "?herp=derp";
	
	var xmlHttp = null;
    xmlHttp = new XMLHttpRequest();
	xmlHttp.timeout = 3000;
	
	xmlHttp.ontimeout = function ()
	{
		console.log("xmlHttp timeout!");
		adBlockDetected(iframe, safeDiv);
	}
	
	xmlHttp.onreadystatechange = function (readystatechangeEvent)
	{
		var target = readystatechangeEvent.target;
		
		if (target.readyState == XMLHttpRequest.OPENED)
		{
			target.send( null );
		}
		else if (target.readyState == XMLHttpRequest.DONE)
		{
			console.log("GET returned with status " + target.status);
			if (target.status >= 100 && target.status < 400)
			{
				// success
			}
			else
			{
				// failure
				// might be adblock, but could be caused by cross-origin request
			}
		}
	};
	
	try
	{
		xmlHttp.open( "GET", src, true );
	}
	catch (err)
	{
		console.log(err.toString());
		if (err.name === "" && err.message === "")
		{
			adBlockDetected(iframe, safeDiv);
		}
	}
	
	/*~~~~~~~~~~~~~~~~~~~~ END TEST #2 ~~~~~~~~~~~~~~~~~~~~*/
}

function adBlockDetected(blockedItem, saveDiv)
{
	console.log("blocked item detected: " + printElement(blockedItem));
	
	
	if (blockedItem.classList.contains("sidebarAd"))
	{
		// safeDiv.style.backgroundImage = "url(\"/img/adblock-dim00-indexed-10colors.png\")";
	}
	else if (blockedItem.tagName !== "IFRAME")
	{
		var spanFrown = document.createElement("span");
		var spanText = document.createElement("span");
		spanFrown.classList.add("adBlockFrown");
		spanText.classList.add("adBlockText");
		safeDiv.classList.add("adBlockDiv");
		safeDiv.appendChild(spanFrown);
		safeDiv.appendChild(spanText);
	}
}

function printElement(element)
{
	var tag = "<"
	tag += element.localName;
	for (var i = 0; i < element.attributes.length; i++)
	{
		tag += " " + element.attributes[i].name;
		tag += "=\"" + element.attributes[i].value + "\"";
	}
	tag += ">"
	
	return tag;
}

function checkDiv(div, safeDiv)
{
	if (div.adBlockDetected == null && (div.offsetWidth == 0 || div.offsetHeight == 0 || div.hidden) )
	{
		div.adBlockDetected = true;
		adBlockDetected(div, safeDiv);
	}
}