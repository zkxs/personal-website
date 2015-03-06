#!/bin/bash
cd will
rename -v 'y/A-Z/a-z/' * # upper to lower case
rename -v 's/[ ]/_/g' * # spaces
rename -v 's/__+/_/g' * # multiple _ characters
rename -v "s/'//g" * # the ' character
rename -v 's/[)(!~]//g' * # weird characters
rename -v 's/_-_/-/g' * # the _-_ sequence that sometimes happense after space replacement
