#!/bin/bash

SCRIPTPATH="$(readlink -f "$0")"

if [ "$1" == "makelink" ]; then # $2 is full path to file
	
	FILEPATH="$2"
        FILENAME="$(basename "$2")"
	
	#find . -type l -exec readlink -f "{}" \; | fgrep -c "$2" # find duplicates
	
	# check if readable
	if [ -r "$FILEPATH" ]; then
		ln -s "$FILEPATH" "$FILENAME" 2>/dev/null
		RESULT="$?"
		if [ "$RESULT" = "0" ]; then # no problems
			: # do nothing
		elif [ "$RESULT" = "1" ]; then # duplicate file
			"$SCRIPTPATH" printdupe "$FILEPATH"
		else # unknown
			echo "Unknown error $RESULT when making link to $FILEPATH"
		fi
	else # not readable
		echo "No read permission: $FILEPATH"
	fi

elif [ "$1" == "printdupe" ]; then #2 is a file whose link already exists

	FILEPATH="$2"
        FILENAME="$(basename "$2")" # the name the link would have had
        REALFILE1="$(readlink -f "$FILENAME")" # path to first file
	REALFILE2="$(readlink -f "$FILEPATH")" # path to newer file
	echo "Duplicate files: ($REALFILE1) ($REALFILE2)"

	
elif [ "$1" == "deadlink" ]; then #2 is a dead link
	
	LINKPATH="$2"
	FILENAME="$(basename "$2")"
	REALFILE="$(readlink -f "$2")"
	
	echo "Preexisting link $FILENAME to $REALFILE is now dead, deleting."
	
	rm "$2"
	
	
elif [ "$1" == "cleanup" ]; then #2 is file
	
	# this function checks for invalid filenames
	# it should be called after the renaming is complete
	
	LINKPATH="$2"
	FILENAME="$(basename "$2")"
	REALFILE="$(readlink -f "$2")"
	
	function check {
		RESULT=$(echo "$FILENAME" | egrep -c "$1")
		
		if [ $RESULT -ne 0 ]; then
			if [ "$2" == "1" ]; then
				echo "$FILENAME ($REALFILE) is not allowed"	
			else
				# this implies the rename failed because the file exists
				"$SCRIPTPATH" printdupe "$LINKPATH"
			fi
			
			rm "$LINKPATH"
			exit 1
		fi
	}
	
	# Check for disallowed characters
	check "[A-Z]"
	check '\s'
	check "__"
	check "'"
	check "[)(!~]"
	check "_-_"

	# Check for disallowed files
	check "iron[^a-z]*man" 1
	check "flow\.swf" 1
	check "hyper.?railgun" 1
	check "james.?driving" 1
	check "bad.?apple" 1
	check "citronnade.?flower" 1
	check "rolling.?udonge" 1
	
	if [ -e "../$FILENAME" ]; then
		if [ "$(readlink -f "../$FILENAME")" != "$(readlink -f "$FILENAME")" ]; then 
			echo "Prexisting file: $FILENAME"
		fi
	else
		mv "$FILENAME" ..
	fi
	
elif [ "$1" == "operate" ]; then #2 is file
	
	function renamingfunction {
		rename $@ 'y/A-Z/a-z/' * # upper to lower case
		rename $@ 's/[\s]/_/g' * # whitespace
		rename $@ "s/'//g" * # the ' character
		rename $@ 's/[)(!~]//g' * # weird characters
		rename $@ 's/__+/_/g' * # multiple _ characters
		rename $@ 's/_-_/-/g' * # the _-_ sequence that sometimes happense after space replacement
	}
	
	
	cd will
	
	# remove dead symlinks
	find -L . -type l -exec "$SCRIPTPATH" deadlink "{}" \;
	
	# process our trusted files first
	renamingfunction -v
	
	# begin to process files from other directories
	if [ -d links ] ; then rm -rf links ; fi
	if [ ! -d links ] ; then mkdir links ; fi
	cd links
		find "/home/wsmith/swf" -name "*.swf" -exec "$SCRIPTPATH" makelink "{}" \;
		renamingfunction #2>/dev/null
		find . -type l -exec "$SCRIPTPATH" cleanup "{}" \;
		find . -exec readlink -f "{}" \; | sort | uniq -d | sed 's/\(.*\)/Duplicate links to \1/'
	cd ..
	rm -rf links
	
else # default options
	
	"$SCRIPTPATH" operate 2>&1 | tee will.log
	
fi
