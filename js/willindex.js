// shim to fix IE8 and earlier being shit
if (!Date.now) {
	Date.now = function() { return new Date().getTime(); }
}



var debugging = false;
var originalTitle = "SUPER WILL HOME PAGE 9000";
var cookieName = "willswfs";
var separator1 = ':';
var separator2 = '!';
var seenExpiry   = 1000 * 60 * 60 * 6;        // 6 hours (in ms)
var cookieExpiry =        60 * 60 * 24 * 365; // 1 year  (in s)
var swfNumber = 0; // the number of the SWF we are currently on (0 if none loaded yet)
var timeoutID;
var currentFilename;
var previousFilename;
var finished = false;
var ignoreUnpause = false;
var timeLoaded;
var idToken; // Google single sign-on id token

function debug(toDebug)
{
	if (toDebug === false)
	{
		if (debugging != false)
		{
			debugging = false;
			$('#debughead').hide();
			$('#swfDebug').hide();
			return "I GUESS IT WAS TOO MUCH FOR YOU!";
		}
		else
		{
			return "...and stay out!";
		}
	}
	else if (toDebug === true || toDebug === undefined)
	{
		if (debugging != true)
		{
			debugging = true;
			$('#debughead').show();
			$('#swfDebug').show();
			return "You are now a developer!";
		}
		else
		{
			return "You are already a developer!";
		}
	}
	else
	{
		return "What are you playing at?";
	}
}

function onGapiLoad() // called by platform.js?onload=onGapiLoad
{

	
	gapi.load('auth2', function() {
		
		var auth2 = gapi.auth2.init();
		auth2.isSignedIn.listen(onSignInChange);
		if (auth2.isSignedIn.get()) // gapi.auth2.getAuthInstance().isSignedIn.get()
		{
			console.log('already signed in');
			onSuccess(auth2.currentUser.get());
			start();
		}
		else
		{
 			console.log("not signed in, waiting a bit to see if anything changes");
			setTimeout(function() {
				if (!auth2.isSignedIn.get())
				{
					showButton();
					start();
				}
			}, 750); // wait a while for google to fucking tell us if we're signed in or not
			// Sign the user in, and then retrieve their ID.
/* 			auth2.signIn().then(function() {
				console.log('signed in!');
				onSuccess(auth2.currentUser.get());
				start();
			}, function(error) {
				console.log('sign in failed.');
				console.log(error);
				start();
			}); */
		}
		
		// how to start if not logged in?
	});
}

var buttonFadeTime = 100;
var buttonCreated = false;
function showButton()
{
	var button = document.getElementById('g-signin2-button');
	if (!buttonCreated)
	{
		buttonCreated = true;
		console.log("creating button");
		button.style.visibility = "hidden";
		gapi.signin2.render('g-signin2-button', {
			'scope': 'profile',
			'width': 120,
			'height': 36,
			'longtitle': false,
			'theme': 'dark',
			//'onsuccess': onSuccess,
			'onfailure': onFailure
		});
	}
	if (buttonCreated && (button.style.visibility === "hidden" || !$('#g-signin2-button').is(":visible")))
	{
		console.log("revealing button");
		$('#g-signin2-button').css('visibility','initial').hide().fadeIn(buttonFadeTime);
	}
}

function onSuccess(googleUser)
{
	// Useful data for your client-side scripts:
	var profile = googleUser.getBasicProfile();
	console.log("ID: " + profile.getId()); // Don't send this directly to your server!
	console.log("Name: " + profile.getName());
	console.log("Image URL: " + profile.getImageUrl());
	console.log("Email: " + profile.getEmail());
	
	// The ID token you need to pass to your backend:
	idToken = googleUser.getAuthResponse().id_token; // save this for later!
	console.log("ID Token: " + idToken);
	
	// hide the button
	console.log('gotta hide dat button!');
	$('#g-signin2-button').fadeOut(buttonFadeTime);
	
	start();
};

function onFailure(error)
{
	console.log(error);
}

function onSignInChange(isSignedIn)
{
	if (isSignedIn)
	{
		onSuccess(gapi.auth2.getAuthInstance().currentUser.get());
	}
}

function signOut() {
	if (gapi && gapi.auth2)
	{
		var auth2 = gapi.auth2.getAuthInstance();
		auth2.signOut().then(function () {
			idToken = undefined; // clear out idToken
			console.log('User signed out.');
		});
	}
	showButton();
}
var logOut = signOut; // alias for signOut for those with poor memory

var started = false;
function start()
{
	if (!started)
	{
		started = true;
		
		// load the initial swf
		if (location.hash)
		{
			loadNextSwf(location.hash.substring(1));
		}
		else
		{
			loadNextSwf();
		}
	}
}

function updateSwfs()
{
	$.post('/php/willindexupdate.php', {token: idToken}, function(data) {
		console.log(data);
	}, 'text');
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
			// 'requested' should already be urlencoded, but it may not be
			
			var pattern = // allowed characters
					new RegExp("^[-A-Za-z0-9\\._~:/?#[\\]@!$'()*+,;=]*$"); // do '?#&@' need to be in this string?
			if (!pattern.test(requested))
			{
				console.log('"' + requested + '" contains invalid characters, urlencoding them now');
				requested = encodeURIComponent(requested);
			}
			
			console.log('loading /php/randomwillswf.php?swf=' + requested);
			$('#swfSlot').load('/php/randomwillswf.php?swf=' + requested, {idtoken: idToken});
		}
		else
		{
			console.log('loading /php/randomwillswf.php');
			$('#swfSlot').load('/php/randomwillswf.php', {idtoken: idToken});
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
	else
	{
		loadNextSwf();
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
			$('#debughead').show();
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
		console.log("this queueRefresh() happened after a more recent queueRefresh()\n"
				+ "that really shouldn't happen");
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
	
	var thingsToAlign = ["errorhead", "errorlog", "swfDebug", "debughead"];
	for (var i in thingsToAlign)
	{
		var node = document.getElementById(thingsToAlign[i]);
		if (node)
		{
			node.style.marginLeft = "auto";
			node.style.marginRight = "auto";
			node.style.width = swf.width + "px";
		}
	}
	
	var firstTime = currentFilename == undefined;
	previousFilename = currentFilename;
	currentFilename = filename;
	
	if (firstTime && history && history.replaceState)
	{
		/*
		 * I want to replace the history state from the initial load with one
		 * that includes the correct title, but the following code does not work.
		 * Probably because history.state is always (mostly) null after a page refresh
		 */
		history.replaceState(filename,                  // state object
				filename + " - " + originalTitle,       // title
				window.location.href + '#' + filename)  // url
		console.log("replaced first history entry");
	}
	location.hash = '#' + filename; // might need to be urlencoded
	document.title = filename + " - " + originalTitle;
	
	if (isFlash)
	{
		// Set up a timer to periodically check value of PercentLoaded
		var loadCheckInterval = setInterval(function (){
			
			// Ensure Flash Player's PercentLoaded method is available
			if (swf && typeof swf.PercentLoaded !== "undefined")
			{
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
			}
			else
			{
				if (timeLoaded != timeLoadedLocal) // if we're invalid
				{
					console.log("invalid due to swf object being undefined");
					clearInterval(loadCheckInterval);
				}
				else
				{
					console.log(filename + " is not loaded yet. Trying again shortly.");
				}
			}
		}, 100);
	}
	else
	{
		// not a swf, so skip the loading polling
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
	var end = Math.min(times.length, 50);
	for (var i = 0; i < end; i++)
	{
		var time = times[i];
		newCookie += seenByTime[time] + "!" + time;
		if (i < end - 1)
		{
			newCookie += ":";
		}
	}
	//console.log(newCookie);
	//console.log(newCookie.length);
	
	//var output = new OutStream();
	//var compressor = new LZWCompressor(output);
	//compressor.compress(newCookie);
	//console.log(output.bytestream);
	//console.log(output.bytestream.length);
	
	if (newCookie)
	{
		docCookies.setItem(cookieName, newCookie, cookieExpiry);
		cookie = docCookies.getItem(cookieName);
		
		if (filename && newCookie !== cookie)
		{
			console.log(filename + " may not have been added to the cookie");
		}
	}
	else
	{
		docCookies.removeItem(cookieName);
		console.log("Removed cookie");
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

console.log("For debug info, run debug()");
