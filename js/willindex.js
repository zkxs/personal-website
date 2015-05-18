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
	
	// get the elements we're going to be working with
	var slot = document.getElementById("swfSlot");
	var container = document.getElementById("swfContainer");
	var swf = document.randomSWF;
	var progressNode; // might never come into being
	
	// pause the swf
	var pauseParamNode = document.createElement("param");
	pauseParamNode.setAttribute("name", "play");
	pauseParamNode.setAttribute("value", "false");
	swf.appendChild(pauseParamNode);
	
	// hide the swf
	swf.style.visibility = "hidden";

	// do some debug logging
	var debugText = $('#swfDebug').text();
	if (debugText) {
		console.log(debugText);
	}
	
	
	var id = initialTimeoutID = setTimeout(function (){
	
		var swf_jquery = $('#randomSWF');
		var filename = swf_jquery.attr('data');
		filename = filename.substring(filename.lastIndexOf('/') + 1);
		currentFilename = filename;
		location.hash = '#' + filename; // might need to be urlencoded
		if (swf_jquery.attr('type') === "application/x-shockwave-flash")
		{
			// Set up a timer to periodically check value of PercentLoaded
			var loadCheckInterval = setInterval(function (){
				
				
				// Ensure Flash Player's PercentLoaded method is available and returns a value
				if(typeof swf.PercentLoaded !== "undefined" && swf.PercentLoaded())
				{
					var swfPercent = swf.PercentLoaded();
					if (progressNode)
					{
						progressNode.setAttribute("value", swfPercent);
					}
					// Once value == 100 (fully loaded) we can do whatever we want
					if (id != initialTimeoutID)
					{
						clearInterval(loadCheckInterval);
					}
					else if(swfPercent >= 100) // it has probably started playing
					{
						var timeDoneLoading = Date.now();
						
						// Clear timer
						clearInterval(loadCheckInterval);
						
						var endTransition = function()
						{
							swf.style.visibility = "initial";
							swf.Play(); // Play the SWF
							if (progressNode)
							{
								container.removeChild(progressNode);
							}
						}
						
						// if we took a while to load
						if (timeDoneLoading - timeLoaded > 1000)
						{
							// fade out the progress bar
							$("#swfProgress").fadeOut(500, endTransition);
						}
						else // we loaded really fast
						{
							// no transition
							endTransition();
						}
						
						// Execute function
						onObjectLoaded(swf_jquery);
					}
					else
					{
						// add the progress bar
						if (!progressNode)
						{
							container.style.position = "relative";
							progressNode = document.createElement("progress");
							progressNode.id = "swfProgress";
							progressNode.setAttribute("value", swfPercent);
							progressNode.setAttribute("max", 100);
							progressNode.style.position = "absolute";
							progressNode.style.left = "50%";
							progressNode.style.top = "50%";
							progressNode.style.transform = "translate(-50%, -50%)";
							container.appendChild(progressNode);
						}
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