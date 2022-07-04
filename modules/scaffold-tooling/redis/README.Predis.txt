Predis cache backend
====================

Using Predis for the Drupal 8 version of this module is still experimental.

Get Predis
----------

Predis can be installed to the vendor directory using composer like so:

composer require predis/predis


Configuration of module for use with Predis
----------------------------

There is not much different to configure about Predis.
Adding this to settings.php should suffice for basic usage:

$settings['redis.connection']['interface'] = 'Predis';
$settings['redis.connection']['host']      = '1.2.3.4';  // Your Redis instance hostname.
$settings['cache']['default'] = 'cache.backend.redis';

To add more magic with a primary/replica setup you can use a config like this:

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