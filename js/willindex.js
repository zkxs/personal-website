// shim to fix IE8 and earlier being shit
if (!Date.now) {
	Date.now = function() { return new Date().getTime(); }
}

var timeoutID;
var initialTimeoutID;
var currentFilename;
var finished = false;
var ignoreUnpause = false;
var timeLoaded;

function paused()
{
	return $('#pausedcheckbox').prop('checked');
}

function onPauseChange()
{
	// if we changed from paused to unpaused and should load the next swf
	if (!ignoreUnpause && finished && !paused())
	{
		loadNextSwf();
	}
}

function onNextButtonClick()
{
	var currentTime = Date.now();
	
	// the user did not click immediately after a refresh
	if (currentTime - timeLoaded > 500)
	{
		ignoreUnpause = true;
		pausedCheckbox.removeAttr("checked");
		loadNextSwf();
		ignoreUnpause = false;
	}
	
	return false; // cancel normal html link navigation
}

function onObjectLoaded(swf)
{
	var time = parseFloat(swf.attr('time')); // time in seconds 
	if (time > 10)
	{
		timeoutID = setTimeout(loadNextSwf, Math.floor(1000 * parseFloat(time)));
		console.log("Refresh queued in " + time + " seconds");
	}
	else
	{
		timeoutID = setTimeout(loadNextSwf, 60000);
		console.log("Refresh queued in 60 seconds (object loops)");
	}
}

function loadNextSwf(requested)
{
	// clear any running timers
	if (timeoutID) {
		clearTimeout(timeoutID);
		timeoutID = null;
	}
	if (initialTimeoutID) {
		clearTimeout(initialTimeoutID);
		initialTimeoutID = null;
	}
	
	finished = true;
	if (!paused())
	{
		finished = false;
		
		if (requested)
		{
			$('#swfSlot').load('/php/randomwillswf.php?swf=' 
					+ requested, queueRefresh); // should already be urlencoded
		}
		else
		{
			$('#swfSlot').load('/php/randomwillswf.php', queueRefresh);
		}
	}
	else
	{
		console.log("waiting for 'paused' checkbox to be unchecked");
	}
}

function ohHashChange()
{
	if (location.hash)
	{
		var newFilename = location.hash.substring(1);
		if (newFilename != currentFilename)
		{
			console.log("hash changed to " + location.hash);
			loadNextSwf(newFilename);
		}
	}
}

// called after the current swf is done loading
function queueRefresh()
{
	timeLoaded = Date.now();
	
	// pause the swf
	var node = document.createElement("param");
	node.setAttribute("name", "play");
	node.setAttribute("value", "false");
	document.randomSWF.appendChild(node);

	// do some debug logging
	var debugText = $('#swfDebug').text();
	if (debugText) {
		console.log(debugText);
	}
	
	
	var id = initialTimeoutID = setTimeout(function (){
	
		var swf = $('#randomSWF');
		var filename = swf.attr('data');
		filename = filename.substring(filename.lastIndexOf('/') + 1);
		currentFilename = filename;
		location.hash = '#' + filename; // might need to be urlencoded
		if (swf.attr('type') === "application/x-shockwave-flash")
		{
			// Set up a timer to periodically check value of PercentLoaded
			var loadCheckInterval = setInterval(function (){
				
				
				// Ensure Flash Player's PercentLoaded method is available and returns a value
				if(typeof document.randomSWF.PercentLoaded !== "undefined" && document.randomSWF.PercentLoaded())
				{
					var swfPercent = document.randomSWF.PercentLoaded();
					console.log(swfPercent + "% loaded");
					// Once value == 100 (fully loaded) we can do whatever we want
					if (id != initialTimeoutID)
					{
						clearInterval(loadCheckInterval);
					}
					else if(swfPercent >= 100) // it has probably started playing
					{
						// Clear timer
						clearInterval(loadCheckInterval);
						
						// Play the SWF
						document.randomSWF.Play();
						
						// Execute function
						onObjectLoaded(swf);
					}
				}
				else
				{
					console.log("0% loaded");
				}
			}, 100);
		}
		else
		{
			// not a swf, so skip the loading polling
			onObjectLoaded(swf);
		}
	}, 200);
}

var pausedCheckbox = $('#pausedcheckbox')
pausedCheckbox.removeAttr("checked");
pausedCheckbox.removeAttr("disabled");
pausedCheckbox.change(onPauseChange);
$('#nextbutton').click(onNextButtonClick);
$(window).on('hashchange', ohHashChange);

// load the initial swf
if (location.hash)
{
	loadNextSwf(location.hash.substring(1));
}
else
{
	loadNextSwf();
}