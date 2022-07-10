# Consumers

When working in a decoupled environment it is of critical importance that
your server is agnostic of any logic that belongs to the client application
. In fact it is considered a best practice that your server is completely
unaware of the existing consumers of your API.

**However** there are exceptional situations where this principle cannot
be ensured. These situations include OAuth2 and image styles per consumer.
This module will help in those situations.

This module will provide a centralized repository to register consumer
applications. The main goal is that the developer of a consumer
application will create the consumer. That consumer will provide the
configuration necessary for that particular application.

This pattern is very common in other sites. For instance Facebook and
Twitter have both a dedicated UI so anyone can register a client
application.
See [https://developers.facebook.com](https://developers.facebook.com)
for an example outside of Drupal.

## Usage

After installing the module visit `/admin/config/services/consumers` to
register a consumer.

This module does nothing by itself, it is meant to be used by other modules
. It will only provide a common way to convey the notion of a consumer
application.

## Integrations

Currently the following modules integrate with *Consumers*:

  * [Simple OAuth](https://drupal.org/project/simple_oauth).
  * [Consumer Image Styles](https://drupal.org/project/consumer_image_styles).
