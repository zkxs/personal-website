#!/bin/bash

if [ "$1" == "makelink" ]; then # $2 is full path to file
	
	FILENAME="$(basename "$2")"
	
	#find . -type l -exec readlink -f "{}" \; | fgrep -c "$2" # find duplicates
	
	ln -s "$2" "$FILENAME"
	
	

elif [ "$1" == "cleanup" ]; then #2 is file
	
	# this function checks for invalid filenames
	# it should be called after the renaming is complete
	
	LINKPATH="$2"
	FILENAME="$(basename "$2")"
	REALFILE="$(readlink -f "$2")"
	
	function check {
		RESULT=$(echo "$FILENAME" | grep -c "$1")
		
		if [ $RESULT -ne 0 ]; then
			echo "$FILENAME ($REALFILE) is a duplicate, or not allowed"
			rm "$LINKPATH"
			exit 1
		fi
	}
	
	
	check "[A-Z]"
	check " "
	check "__"
	check "'"
	check "[)(!~]"
	check "_-_"
	check "iron[^a-z]*man"
	check "flow\.swf"
	
	if [ ! -e "../$FILENAME" ]; then
		mv "$FILENAME" ..
	fi
	
else
	
	SCRIPTPATH="$(readlink -f "$0")"
	
	function renamingfunction {
		rename $@ 'y/A-Z/a-z/' * # upper to lower case
		rename $@ 's/[ ]/_/g' * # spaces
		rename $@ 's/__+/_/g' * # multiple _ characters
		rename $@ "s/'//g" * # the ' character
		rename $@ 's/[)(!~]//g' * # weird characters
		rename $@ 's/_-_/-/g' * # the _-_ sequence that sometimes happense after space replacement
	}
	
	
	cd will
	
	# process our trusted files first
	renamingfunction -v
	
	# begin to process files from other directories
	if [ -d links ] ; then rm -rf links ; fi
	if [ ! -d links ] ; then mkdir links ; fi
	cd links
		find "/home/wsmith/swf" -name "*.swf" -exec "$SCRIPTPATH" makelink "{}" \;
		renamingfunction 2>/dev/null
		find . -type l -exec "$SCRIPTPATH" cleanup "{}" \;
		find . -exec readlink -f "{}" \; | sort | uniq -d | sed 's/\(.*\)/Duplicate links to \1/'
	cd ..
	rm -rf links
	
	
fi
