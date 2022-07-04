PhpRedis cache backend
======================

This client, for now, is only able to use the PhpRedis extension.

Get PhpRedis
------------

You can download this library at:

  https://github.com/nicolasff/phpredis

This is PHP extension, too recent for being packaged in most distribution, you
will probably need to compile it yourself.

Default behavior is to connect via tcp://localhost:6379 but you might want to
connect differently.

Use the Sentinel high availability mode
---------------------------------------

Redis can provide a master/slave mode with sentinels server monitoring them.
More information about setting it : https://redis.io/topics/sentinel.

This mode needs the following settings:

Modify the host as follow:
    // Sentinels instances list with hostname:port format.
    $settings['redis.connection']['host']      = ['1.2.3.4:5000','1.2.3.5:5000','1.2.3.6:5000'];

Add the new instance setting:

    // Redis instance name.
    $settings['redis.connection']['instance']  = 'instance_name';

Connect via UNIX socket
-----------------------

Just add this line to your settings.php file:

  $conf['redis_cache_socket'] = '/tmp/redis.sock';

Don't forget to change the path depending on your operating system and Redis
server configuration.

Connect to a remote host and database
-------------------------------------

See README.md file.

For this particular implementation, host settings are overridden by the
UNIX socket parameter.
