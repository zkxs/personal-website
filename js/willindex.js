// shim to fix IE8 and earlier being shit
if (!Date.now) {
	Date.now = function() { return new Date().getTime(); }
}



var debugging = false;
var cookieName = "willswfs";
var separator1 = ':';
var separator2 = '!';
var seenExpiry   = 1000 * 60 * 60 * 6;        // 6 hours (in ms)
var cookieExpiry =        60 * 60 * 24 * 365; // 1 year  (in s)
var currentSWF = null;
var swfNumber = 0; // the number of the SWF we are currently on (0 if none loaded yet)
var timeoutID;
var currentFilename;
var finished = false;
var ignoreUnpause = false;
var timeLoaded;

function debug()
{
	debugging = true;
	$('#swfDebug').show();
	return "You are now a developer!";
}

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
	
	finished = true;
	if (!paused())
	{
		finished = false;
		swfNumber += 1;
		
		/*
		 * You must be wondering why I don't use jQuery callbacks here to
		 * perform an action once the content is loaded. The answer is simple:
		 * the jQuery callback is called before the fucking content is done
		 * fucking loading. I'm not sure if this is a bug or some moron thought
		 * it would be a good idea to have the callback trigger a few hundered
		 * milliseconds before it is useful.
		 */
		
		if (requested)
		{
			// 'requested' should already be urlencoded
			$('#swfSlot').load('/php/randomwillswf.php?swf=' + requested);
		}
		else
		{
			$('#swfSlot').load('/php/randomwillswf.php');
		}
	}
	else
	{
		console.log("waiting for 'paused' checkbox to be unchecked");
	}
}

/*
 * if the user changes the page's hash, load the swf. If the hash is invalid
 * the PHP code will just give us a random file, and we'll change the hash on
 * this end to match.
 */
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

// magically called after the current swf is done loading
function queueRefresh(filename)
{
	var timeLoadedLocal = Date.now();
	
	console.log("loading " + filename);
	if (debugging)
	{
		// do some debug logging
		var debugText = $('#swfDebug').text();
		if (debugText) {
			//console.log("DEBUG INFO:\n" + debugText);
			$('#swfDebug').show();
		}
	}
	
	if (!timeLoaded)
	{
		console.log("setting timeLoaded for the fist time " + timeLoadedLocal);
		timeLoaded = timeLoadedLocal;
	}
	else if (timeLoadedLocal > timeLoaded)
	{
		console.log("updating timeLoaded to newer value " + timeLoadedLocal);
		timeLoaded = timeLoadedLocal;
	}
	else
	{
		// somehow this call happened after a more recent call
		console.log("this queueRefresh() happened after a more recent queueRefresh()");
		return;
	}
	
	updateCookie(timeLoaded, filename);

	// get the elements we're going to be working with
	var swf = document.randomSWF;
	var isFlash;
	
	if (isFlash = swf.StopPlay)
	{
		// pause the swf
		swf.StopPlay();
		
		// hide the swf
		swf.style.visibility = "hidden";
	}
	
	var slot = document.getElementById("swfSlot");
	var container = document.getElementById("swfContainer");
	var container_jquery = $("#swfContainer");
	var swf_jquery = $('#randomSWF');
	var progressNode = null; // might never come into being
	
	currentFilename = filename;
	location.hash = '#' + filename; // might need to be urlencoded
	
	if (isFlash)
	{
		// Set up a timer to periodically check value of PercentLoaded
		var loadCheckInterval = setInterval(function (){
			
			var swfPercent = swf.PercentLoaded();
			if (progressNode)
			{
				progressNode.setAttribute("value", swfPercent);
			}
			// Once value == 100 (fully loaded) we can do whatever we want
			if (timeLoaded != timeLoadedLocal) // if we're invalid
			{
				console.log("invalid during load");
				clearInterval(loadCheckInterval);
			}
			else if(swfPercent >= 100) // it has probably started playing
			{
				var timeDoneLoading = Date.now();
				console.log("done loading " + filename);
				
				// Clear timer
				clearInterval(loadCheckInterval);
				
				var endTransition = function()
				{
					if (timeLoaded == timeLoadedLocal) // if we're still valid
					{
						//container_jquery.hide();
						swf.style.visibility = "initial";
						swf.Play(); // Play the SWF
						//container_jquery.fadeIn(500);
						
						if (progressNode)
						{
							container.removeChild(progressNode);
						}
						// Execute function
						onObjectLoaded(swf_jquery);
					}
					else
					{
						console.log("invalid after load");
					}
				}
				
				// if we took a while to load
				if (progressNode && timeDoneLoading - timeLoaded > 1000)
				{
					// fade out the progress bar
					$("#swfProgress").fadeOut(250, endTransition);
				}
				else // we loaded really fast
				{
					// no transition
					endTransition();
				}
			}       // if we've been loading a little bit and are not done, add the progress bar
			else if (!progressNode && Date.now() - timeLoaded > 200) 
			{
				// add the progress bar
				container.style.position = "relative";
				progressNode = document.createElement("progress");
				progressNode.id = "swfProgress";
				progressNode.setAttribute("value", swfPercent);
				progressNode.setAttribute("max", 100);
				progressNode.style.position = "absolute";
				progressNode.style.left = "50%";
				progressNode.style.top = "50%";
				progressNode.style.transform = "translate(-50%, -50%)";
				progressNode.style.visibility = "hidden";
				container.appendChild(progressNode);
				$("#swfProgress").css('visibility','initial').hide().fadeIn(50);
			}
		}, 100);
	}
	else
	{
		// not a swf, so skip the loading polling
		console.log("not flash");
		onObjectLoaded(swf_jquery);
	}
}

function updateCookie(timeLoadedLocal, filename)
{
	var cookie = docCookies.getItem(cookieName);
	var seenByName = {};
	if (filename)
	{
		//console.log("adding " + filename + " to cookie");
		seenByName[filename] = timeLoadedLocal;
	}
	var newCookie = "";
	if (cookie)
	{
		var splitCookie = cookie.split(separator1);
		for (var i in splitCookie)
		{
			var splitPair = splitCookie[i].split(separator2);
			var file = splitPair[0];
			var timestamp = parseInt(splitPair[1]);
			
			if (timeLoadedLocal - timestamp <= seenExpiry) // discard old views
			{
				if (Object.prototype.hasOwnProperty.call(seenByName, file))
				{
					if (timestamp > seenByName[file])
					{
						seenByName[file] = timestamp;
					}
				}
				else
				{
					seenByName[file] = timestamp;
				}
			}
		}
	}
	var seenByTime = [];
	var times = [];
	var ii = 0;
	for (var name in seenByName)
	{
		times[ii++] = seenByName[name];
		seenByTime[seenByName[name]] = name;
	}
	seenByName = null;
	times.sort(function(a, b){return b - a;});
	var newCookie = "";
	for (var i = 0; i < Math.min(times.length, 50); i++)
	{
		var time = times[i];
		newCookie += seenByTime[time] + "!" + time;
		if (i < times.length - 1)
		{
			newCookie += ":";
		}
	}
	//console.log(newCookie);
	//console.log(newCookie.length);
	
	var output = new OutStream();
	var compressor = new LZWCompressor(output);
	compressor.compress(newCookie);
	//console.log(output.bytestream);
	//console.log(output.bytestream.length);
	
	docCookies.setItem(cookieName, newCookie, cookieExpiry);
	cookie = docCookies.getItem(cookieName);
	
	if (filename && newCookie !== cookie)
	{
		console.log(filename + " may not have been added to the cookie");
	}
}

var pausedCheckbox = $('#pausedcheckbox')
pausedCheckbox.removeAttr("checked");
pausedCheckbox.removeAttr("disabled");
pausedCheckbox.change(onPauseChange);
$('#nextbutton').click(onNextButtonClick);
$(window).on('hashchange', ohHashChange);

// update the cookie
updateCookie(Date.now());

// load the initial swf
if (location.hash)
{
	loadNextSwf(location.hash.substring(1));
}
else
{
	loadNextSwf();
}

console.log("For debug info, run debug()");
