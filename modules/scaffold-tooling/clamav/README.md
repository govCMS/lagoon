About this module
-----------------

This module integrates with the open-source anti-virus scanner ClamAV.
Upload files are forwarded to the ClamAV service and checked. Infected files
are blocked in the validation routine, so they cannot be saved.


Prerequisites
-------------

This module requires a ClamAV service.

It is beyond the scope of this module to describe how to install ClamAV (as
this will depend on a number of variables, such as the ClamAV version, the
server's operating system, the package management tools available, whether a
custom build from source is required, etc), but the process is well documented
in the ClamAV documentation.

See http://www.clamav.net/documents/installing-clamav for installation and
configuration instructions.


Setup
-----

- Install a ClamAV service.
  For example: `sudo apt-get install clamav-daemon`

- Enable this module.

- Configure the module at /admin/config/media/clamav.
  The unix-socket daemon mode is quickest, but the ClamAV daemon must run on
  the same host as the web server. TCP/IP daemon mode may be best for a
  clustered web service. Executable mode is slowest but simplest.


Performance
-----------

Both the daemon modes are many orders-of-magnitude faster than executable mode.
This is due to the time it takes to load the virus-definitions into memory. In
daemon-mode, this overhead happens when the daemon starts, but the definitions
persist in memory. In executable mode, the definitions have to be re-loaded for
every scan.

In local testing, the mean scan-time for each usage was as follows:

- Executable: 10.3 seconds
- Daemon-mode (unix socket): 0.0009 seconds
- Daemon-mode (local TCP/IP): 0.0010 seconds

Unix-socket is typically slightly quicker than TCP/IP mode (because there's a
small overhead in initiating a TCP/IP connection), but both are around 10,000
times faster than executable mode.
