#!/bin/bash

echo -e "<!DOCTYPE html>

<html>
\t<head>
\t\t<title>Secret Index</title>
\t\t<meta charset=\"UTF-8\">
\t\t<meta name=\"description\" content=\"Secret index\">
\t\t<meta name=\"keywords\" content=\"secret,index,michael,shenanigans\">
\t\t<meta name=\"author\" content=\"Michael Ripley\">
\t\t<style>
\t\t\t.mono {
\t\t\t\tfont-family: \"Courier New\", Courier, monospace
\t\t\t}
\t\t</style>
\t</head>
\t<body>
\t\t<p class=\"mono\">file &lt;directory&gt; {unknown}</p>
\t\t<ul class=\"mono\" style=\"list-style: none;\">" > secret.shtml

for f in *; do
  echo -ne "\t\t\t<li><a href=\"$f\">" >> secret.shtml
  if [ -f "$f" ]; then
    echo -ne "$f" >> secret.shtml
  elif  [ -d "$f" ]; then
    echo -ne "&lt;$f&gt;" >> secret.shtml
  else
    echo -ne "{$f}" >> secret.shtml
  fi
  echo -e "</a></li>" >> secret.shtml
done

echo -e "\t\t</ul>
\t\t<p class=\"mono\">Run secret.sh to update this page.</p>
\t\t<!--#include virtual=\"/snippets/piwik.html\" -->
\t</body>
</html>" >> secret.shtml

chmod o+r secret.shtml