# supervisord-proxy

## Description

This is a small proxy which can sit between supervisord and
supervisordctl (or any supervisord XML-RPC client).

As it is currently implemented, it will perform an IDENT request
against the host the client is connecting from and fetch the remote
username. It will match that against the local supervisord config
by parsing them and either proxy the command to the actual daemon
or deny the request.

This somewhat enables multi-tenancy, albeit with some caveats.

## Installation

Simply clone the project anywhere and point your webserver at the
webroot.

### Configuration

Create a .environment.php file as follows:

    <?php
    $environment = [
        'supervisord_logtail_endpoint' => 'http://username:password@127.0.0.1:9001',
        'supervisord_xmlrpc_endpoint' => 'unix:///var/run/supervisor.sock',
    ];

## Caveats

The code makes quite some assumptions about the supervisord setup.
All jobs should be defined in supervisord's conf.d directory, which
should not contain subdirectories. Each configuration file should
contain exactly one job, and the filename should be <jobname>.conf.

Additionally, at least the logtail interface should be available over
TCP, the actual XML-RPC interface can be the default UNIX socket.

## TODO

* Extend supervisord's XML-RPC interface to return the UNIX username 
  which the job is running, use that instead of parsing the config.
* Implement an HTTP client which can handle UNIX sockets (that rules
  out PHP-cURL) and implements chunked HTTP in a sane way, use that
  instead of the current uglyness of cURL calls and PHP sockets.
