mail-count
==========

A Bash script for tracking the number of outgoing SMTP deliveries on a server, with a PHP page for viewing stats.

The objective is to provide easily-accessible data for a server admin, to help detect and curtail SMTP abuse.

Requirements
------------

  - Linux - tested on Debian 7 and Ubuntu 12.04
  - Postfix or Exim4 mailserver
  - Webserver with PHP for viewing stats.

To Do
-----

  - Commandline script for viewing stats.

Setup
-----

See INSTALL for setup instructions.

Changes
-------

Oct 27, 2014
  Modified previous days list (mail-count.php) to display day of week.
  
Oct 23, 2014
  First release.
