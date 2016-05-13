# Michael's Website
My personal website.  This is where I screw around with web stuff and host a bunch of random files.

## Viewing it
I've got a bunch of dynamic dns set up:
* [www.michaelripley.net](www.michaelripley.net)
* [michael.evanforbes.net](http://michael.evanforbes.net:8080)

## Structure
style.css is the main stylesheet, despite there being a sheet named main.css.
Quite a few files are just html5boilerplate holdovers. The .htaccess file in the root of the site is where all
the interesting things are.  The .htaccess files in other folders are primarily there just to turn indexing on
or off as appropriate. A decent percentage of the site's actual content is just files I'm hosting and have no reason to
version control. What you see here is the code I've written/collected for the parts that aren't random files. 

## Missing Files
There are a few files that I'm specifically not tracking to their sensitive content, namely /.htpasswd and /.htgroups. 
Also note the sql login information used in the /dl/*.php scripts is loaded from an external, untracked file. 
Due to this the site cannot simply be dropped in and expected to work completely.

## Webserver Requirements
In addition to PHP, my Apache setup has the following modules. I'm not sure if all of them are being used.
* core
* so
* watchdog
* http
* logio
* version
* unixd
* alias
* autoindex
* cgi ?
* deflate
* dir
* env
* expires
* filter
* headers
* include
* mime
* negotiation
* perl ?
* reqtimeout
* rewrite
* setenvif
* ssl
* status
* substitute
* xsendfile

## Attribution of other's work
* [HTML5 Boilerplate](https://html5boilerplate.com/)
* [Jacob Wyke's code from one of his blog posts as the basis of the /dl/*.php files](http://www.webvamp.co.uk/blog/coding/creating-one-time-download-links/)
* [Stuart Langridge's code for the sorting of table columns.](http://www.kryogenix.org/code/browser/sorttable/)
* [Carlos Falo Hervás' PHP library for reading SWF file headers](http://www.phpclasses.org/package/1653-PHP-Parse-header-of-Flash-files-to-extract-metadata.html)
