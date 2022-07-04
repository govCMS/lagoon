Redis backends
====================

This package provides two different Redis backends. If you want to use
Redis as cache backend, you have to choose one of the two, but you cannot use
both at the same time.

PhpRedis
--------

This implementation uses the PhpRedis PHP extension. In order to use it, you
will need to compile the extension yourself.

Predis
------

Support for the Predis PHP library is experimental, but feel free to try it out.
You can install the required library using composer. Check out the README.Predis.txt file
for more information.

Important notice
----------------

This module requires at least Redis 2.4, additionally, the lock backend
requires Redis 2.6 to support millisecond timeouts and atomic lock operations.

Getting started
===============

Quick setup
-----------

Here is a simple yet working easy way to setup the module.

This method will allow Drupal to use Redis for all caches.

    $settings['redis.connection']['interface'] = 'PhpRedis'; // Can be "Predis".
    $settings['redis.connection']['host']      = '1.2.3.4';  // Your Redis instance hostname.
    $settings['cache']['default'] = 'cache.backend.redis';

To use some Predis goodness, including a redis primary/replica setup you can use a config like this.

    $settings['redis.connection']['interface'] = 'Predis'; // Use predis library.
    $settings['redis.connection']['replication'] = TRUE; // Turns on replication.
    $settings['redis.connection']['replication.host'][1]['host'] = '1.2.3.4';  // Your Redis instance hostname.
    $settings['redis.connection']['replication.host'][1]['port'] = '6379'; // Only required if using non-standard port.
    $settings['redis.connection']['replication.host'][1]['role'] = 'primary'; // The redis instance role.
    $settings['redis.connection']['replication.host'][2]['host'] = '1.2.3.5';
    $settings['redis.connection']['replication.host'][2]['port'] = '6379';
    $settings['redis.connection']['replication.host'][2]['role'] = 'replica';
    $settings['redis.connection']['replication.host'][3]['host'] = '1.2.3.6';
    $settings['redis.connection']['replication.host'][3]['port'] = '6379';
    $settings['redis.connection']['replication.host'][3]['role'] = 'replica';
    $settings['cache']['default'] = 'cache.backend.redis';

Either include the default example.services.yml from the module, which will
replace all supported backend services (that currently includes the cache tags
checksum service and the lock backends, check the file for the current list)
or copy the service definitions into a site specific services.yml.

    $settings['container_yamls'][] = 'modules/redis/example.services.yml';

Note that for any of this, the redis module must be enabled. See next chapters
for more information.

Is there any cache bins that should *never* go into Redis?
----------------------------------------------------------

TL;DR: No.

Redis has been maturing a lot over time, and will apply different sensible
settings for different bins; It's today very stable.

Advanced configuration
======================

Choose the Redis client library to use
--------------------------------------

Note: This is not yet supported, only the PhpRedis interface is available.

Add into your settings.php file:

    $settings['redis.connection']['interface'] = 'PhpRedis';

You can replace 'PhpRedis' with 'Predis', depending on the library you chose.


Tell Drupal to use the cache backend
------------------------------------

Usual cache backend configuration, as follows, to add into your settings.php
file like any other backend:

    # Use for all bins otherwise specified.
    $settings['cache']['default'] = 'cache.backend.redis';

    # Use this to only use it for specific cache bins.
    $settings['cache']['bins']['render'] = 'cache.backend.redis';

Tell Drupal to use the lock backend
-----------------------------------

See the provided example.services.yml file on how to override the lock services.

Tell Drupal to use the queue backend
------------------------------------

This module provides reliable and non-reliable queue implementations. Depending
on which is to be use you need to choose "queue.redis" or "queue.redis_reliable"
as a service name.

When you have configured basic information (host, library, ... - see Quick setup)
add this to your settings.php file:

    # Use for all queues unless otherwise specified for a specific queue.
    $settings['queue_default'] = 'queue.redis';

    # Or if you want to use reliable queue implementation.
    $settings['queue_default'] = 'queue.redis_reliable';


    # Use this to only use Redis for a specific queue (aggregator_feeds in this case).
    $settings['queue_service_aggregator_feeds'] = 'queue.redis';

    # Or if you want to use reliable queue implementation.
    $settings['queue_service_aggregator_feeds'] = 'queue.redis_reliable';


Common settings
===============

Connect to a remote host
------------------------

If your Redis instance is remote, you can use this syntax:

    $settings['redis.connection']['interface'] = 'PhpRedis'; // Can be "Predis".
    $settings['redis.connection']['host']      = '1.2.3.4';  // Your Redis instance hostname.
    $settings['redis.connection']['port']      = '6379';  // Redis port

Port is optional, default is 6379 (default Redis port).

Use persistent connections
--------------------------

This mode needs the following setting:

    $settings['redis.connection']['persistent'] = TRUE;

Compression
-------------------------
Compressing the data stored in redis can massively reduce the nedeed storage.

To enable, set the minimal length after which the cached data should be
compressed:

    $settings['redis_compress_length'] = 100;

By default, compression level 1 is used, which provides considerable storage
optimization with minimal CPU overhead, to change:

    $settings['redis_compress_level'] = 6;

Using a specific database
-------------------------

Per default, Redis ships the database "0". All default connections will be use
this one if nothing is specified.

Depending on you OS or OS distribution, you might have numerous database. To
use one in particular, just add to your settings.php file:

    $settings['redis.connection']['base']      = 12;

Connection to a password protected instance
-------------------------------------------

If you are using a password protected instance, specify the password this way:

    $settings['redis.connection']['password'] = "mypassword";

Depending on the backend, using a wrong auth will behave differently:

 - Predis will throw an exception and make Drupal fail during early boostrap.

 - PhpRedis will make Redis calls silent and creates some PHP warnings, thus
   Drupal will behave as if it was running with a null cache backend (no cache
   at all).

Prefixing site cache entries (avoiding sites name collision)
------------------------------------------------------------

If you need to differentiate multiple sites using the same Redis instance and
database, you will need to specify a prefix for your site cache entries.

Cache prefix configuration attempts to use a unified variable across contrib
backends that support this feature. This variable name is 'cache_prefix'.

This variable is polymorphic, the simplest version is to provide a raw string
that will be the default prefix for all cache bins:

    $settings['cache_prefix'] = 'mysite_';

Alternatively, to provide the same functionality, you can provide the variable
as an array:

    $settings['cache_prefix']['default'] = 'mysite_';

This allows you to provide different prefix depending on the bin name. Common
usage is that each key inside the 'cache_prefix' array is a bin name, the value
the associated prefix. If the value is FALSE, then no prefix is
used for this bin.

The 'default' meta bin name is provided to define the default prefix for non
specified bins. It behaves like the other names, which means that an explicit
FALSE will order the backend not to provide any prefix for any non specified
bin.

Here is a complex sample:

    // Default behavior for all bins, prefix is 'mysite_'.
    $settings['cache_prefix']['default'] = 'mysite_';

    // Set no prefix explicitely for 'cache' and 'cache_bootstrap' bins.
    $settings['cache_prefix']['cache'] = FALSE;
    $settings['cache_prefix']['cache_bootstrap'] = FALSE;

    // Set another prefix for 'cache_menu' bin.
    $settings['cache_prefix']['cache_menu'] = 'menumysite_';

Note that if you don't specify the default behavior, the Redis module will
attempt to use the HTTP_HOST variable in order to provide a multisite safe
default behavior. Notice that this is not failsafe, in such environment you
are strongly advised to set at least an explicit default prefix.

Note that this last notice is Redis only specific, because per default Redis
server will not namespace data, thus sharing an instance for multiple sites
will create conflicts. This is not true for every contributed backends.

Flush mode
----------

@todo: Update for Drupal 8

Redis allows to set a time-to-live at the key level, which frees us from
handling the garbage collection at clear() calls; Unfortunately Drupal never
explicitely clears single cached pages or blocks. If you didn't configure the
"cache_lifetime" core variable, its value is "0" which means that temporary
items never expire: in this specific case, we need to adopt a different
behavior than leting Redis handling the TTL by itself; This is why we have
three different implementations of the flush algorithm you can use:

 * 0: Never flush temporary: leave Redis handling the TTL; This mode is
   not compatible for the "page" and "block" bins but is the default for
   all others.

 * 1: Keep a copy of temporary items identifiers in a SET and flush them
   accordingly to spec (DatabaseCache default backend mimic behavior):
   this is the default for "page" and "block" bin if you don't change the
   configuration.

 * 2: Flush everything including permanent or valid items on clear() calls:
   this behavior mimics the pre-1.0 releases of this module. Use it only
   if you experience backward compatibility problems on a production
   environement - at the cost of potential performance issues; All other
   users should ignore this parameter.

You can configure a default flush mode which will override the sensible
provided defaults by setting the 'redis_flush_mode' variable.

  // For example this is the safer mode.
  $conf['redis_flush_mode'] = 1;

But you may also want to change the behavior for only a few bins.

  // This will put mode 0 on "bootstrap" bin.
  $conf['redis_flush_mode_cache_bootstrap'] = 0;

  // And mode 2 to "page" bin.
  $conf['redis_flush_mode_cache_page'] = 2;

Note that you must prefix your bins with "cache" as the Drupal 7 bin naming
convention requires it.

Keep in mind that defaults will provide the best balance between performance
and safety for most sites; Non advanced users should ever change them.

Default lifetime for permanent items
------------------------------------

@todo: Update for Drupal 8

Redis when reaching its maximum memory limit will stop writing data in its
storage engine: this is a feature that avoid the Redis server crashing when
there is no memory left on the machine.

As a workaround, Redis can be configured as a LRU cache for both volatile or
permanent items, which means it can behave like Memcache; Problem is that if
you use Redis as a permanent storage for other business matters than this
module you cannot possibly configure it to drop permanent items or you'll
loose data.

This workaround allows you to explicity set a very long or configured default
lifetime for CACHE_PERMANENT items (that would normally be permanent) which
will mark them as being volatile in Redis storage engine: this then allows you
to configure a LRU behavior for volatile keys without engaging the permenent
business stuff in a dangerous LRU mechanism; Cache items even if permament will
be dropped when unused using this.

Per default the TTL for permanent items will set to safe-enough value which is
one year; No matter how Redis will be configured default configuration or lazy
admin will inherit from a safe module behavior with zero-conf.

For advanturous people, you can manage the TTL on a per bin basis and change
the default one:

    // Make CACHE_PERMANENT items being permanent once again
    // 0 is a special value usable for all bins to explicitely tell the
    // cache items will not be volatile in Redis.
    $conf['redis_perm_ttl'] = 0;

    // Make them being volatile with a default lifetime of 1 year.
    $conf['redis_perm_ttl'] = "1 year";

    // You can override on a per-bin basis;
    // For example make cached field values live only 3 monthes:
    $conf['redis_perm_ttl_cache_field'] = "3 months";

    // But you can also put a timestamp in there; In this case the
    // value must be a STRICTLY TYPED integer:
    $conf['redis_perm_ttl_cache_field'] = 2592000; // 30 days.

Time interval string will be parsed using DateInterval::createFromDateString
please refer to its documentation:

    http://www.php.net/manual/en/dateinterval.createfromdatestring.php

Last but not least please be aware that this setting affects the
CACHE_PERMANENT ONLY; All other use cases (CACHE_TEMPORARY or user set TTL
on single cache entries) will continue to behave as documented in Drupal core
cache backend documentation.

Lock backends
-------------

@todo: Update for Drupal 8

Both implementations provides a Redis lock backend. Redis lock backend proved to
be faster than the default SQL based one when using both servers on the same box.

Both backends, thanks to the Redis WATCH, MULTI and EXEC commands provides a
real race condition free mutexes if you use Redis >= 2.1.0.

Testing
=======

I did not find any hint about making tests being configurable, so per default
the tested Redis server must always be on localhost with default configuration.
