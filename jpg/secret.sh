#!/bin/bash

echo -e "<!DOCTYPE html>

<html>
\t<head>
\t\t<title>Secret Index</title>
\t</head>
\t<body>
\t\t<p>file &lt;directory&gt; {unknown}</p>
\t\t<ul>" > secret.html

for f in *; do
  echo -ne "\t\t\t<li><a href=\"$f\">" >> secret.html
  if [ -f "$f" ]; then
    echo -ne "$f" >> secret.html
  elif  [ -d "$f" ]; then
    echo -ne "&lt;$f&gt;" >> secret.html
  else
    echo -ne "{$f}" >> secret.html
  fi
  echo -e "</a></li>" >> secret.html
done

echo -e "\t\t</ul>
\t\t<p>Run secret.sh to update this page.</p>
\t</body>
</html>" >> secret.html
