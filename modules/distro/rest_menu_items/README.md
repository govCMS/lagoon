## REQUIREMENTS

None.

## INSTALLATION

* Enable the module as usual.
* You can enable the REST endpoint through the [REST UI](http://www.drupal.org/project/restui) module or checkout [RESTful Web Services API overview](https://www.drupal.org/docs/8/api/restful-web-services-api/restful-web-services-api-overview) page

## CONFIGURATION

Once the module has been installed, navigate to `admin/config/services/rest_menu_items`
(`Configuration > Web Services > Rest Menu Items` through the administration panel) and
configure the available values you want to output in the JSON.

## Change the endpoint URL
If you ever want to change the endpoint URL you can do this with `hook_rest_resource_alter` (Thanks to [cgomezg](https://www.drupal.org/u/cgomezg)):
~~~~
/**
 * Update canonical for rest_menu_item.
 *
 * Implements hook_rest_resource_alter
 */
function MYMODULE_rest_resource_alter(&$definitions) {
  if (!empty($definitions['rest_menu_item'])) {
    $definitions['rest_menu_item']['uri_paths']['canonical'] = '/api/v2/menu-items/{menu_name}';
  }
}
~~~~

## TROUBLESHOOTING

* If you get a `406 - Not Acceptable` error you need to add the `?_format=json|hal_json|xml` attribute to the URL.

  See https://www.drupal.org/node/2790017 for further information.

## CONTACT

Current maintainers:
* Fabian de Rijk ([fabianderijk](https://www.drupal.org/u/fabianderijk))

## Sponsors

This project has been sponsored by:
* [Finalist](https://www.drupal.org/finalist)
