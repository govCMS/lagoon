# Metatag

This module allows a site's builder to automatically provide structured
metadata, aka "meta tags", about the site and individual pages.

In the context of search engine optimization, providing an extensive set of
meta tags may help improve the site's and pages' rankings, thus may aid with
achieving a more prominent display of the content within search engine results.
They can also be used to tailor how content is displayed when shared on social
networks.

For additional information, see the [online documentation](https://www.drupal.org/docs/8/modules/metatag).

This version should work with all Drupal 9 releases, though it
is always recommended to keep Drupal core installations up to date.

## Requirements

Metatag for Drupal 9 requires the following:

* [Token](https://www.drupal.org/project/token): Provides a popup browser to
  see the available tokens for use in meta tag fields.

## Features

The primary features include:

* An administration interface to manage default meta tags.

* Use of standard fields for entity support, allowing for translation and
  revisioning of meta tag values added for individual entities.

* A large volume of meta tags available, covering commonly used tags, Open
  Graph tags, Twitter Cards tags, Dublin Core tags, Google+ tags, App Links
  tags, site verification tags and more; all but the basic meta tags are kept
  in separate submodules.

* Configuration can be added for individual paths using the Metatag Custom
  Routes submodule.

* The fifteen Dublin Core Basic Element Set 1.1 meta tags may be added by
  enabling the "Metatag: Dublin Core" submodule.

* Forty additional Dublin Core meta tags may be added by enabling the "Metatag:
  Dublin Core Advanced" submodule.

* The Open Graph Protocol meta tags, as used by Facebook, Pinterest, LinkedIn
  and other sites, may be added by enabling the "Metatag: Open Graph" submodule.

* The Twitter Cards meta tags may be added by enabling the "Metatag: Twitter
  Cards" submodule.

* Certain meta tags used by Google+ may be added by enabling the "Metatag:
  Google+" submodule.

* Facebook's fb:app_id, fb:admins and fb:pages meta tags may be added by
  enabling the "Metatag: Facebook" submodule. These are useful for sites which
  are using Facebook widgets or are building custom integration with Facebook's
  APIs, but they are not needed by most sites and have no bearing on the
  Open Graph meta tags.

* The Pinterest meta tags may be added by enabling the "Metatag: Pinterest"
  submodule.

* Site verification meta tags can be added, e.g. as used by the Google search
  engine to confirm ownership of the site; see the "Metatag: Verification"
  submodule.

* The Metatag: Mobile & UI Adjustments submodule adds the MobileOptimized,
  HandheldFriendly, viewport, cleartype, theme-color, format-detection,
  apple-mobile-web-app-capable, apple-mobile-web-app-status-bar-style, the
  android-app and ios-app alternative link meta tags, and the Android manifest
  tag.

* The hreflang meta tags are available via the Metatag:hreflang submodule.

* The App Links meta tags may be added by enabling the Metatag: App Links
  submodule.

* Support for meta tags specific to Google Custom Search Appliance are available
  in the "Metatag: Google Custom Search Engine (CSE)" submodule.

* Meta tags specific to Facebook are included in the "Metatag: Facebook"
  submodule.

* A plugin interface allowing for additional meta tags to be easily added via
  custom modules.

* A report page at /admin/reports/metatag-plugins which shows all of the meta
  tag plugins provided on the site, and indication as to which module provides
  them.


## Standard usage scenario

1. Install the module.
2. Open admin/config/search/metatag.
3. Adjust global and entity defaults. Fill in reasonable default values for any
   of the meta tags that need to be customized. Tokens may be used to
   automatically assign values.
4. Additional bundle defaults may be added by clicking on "Add default meta
   tags" and filling out the form.
5. To adjust meta tags for a specific entity, the Metatag field must be added
   first. Follow these steps:

  * Go to the "Manage fields" of the bundle where the Metatag field is to
    appear.

  * Select "Meta tags" from the "Add a new field" selector.

  * Fill in a label for the field, e.g. "Meta tags", and set an appropriate
    machine name, e.g. "meta_tags".

  * Click the "Save and continue" button.

  * If the site supports multiple languages, and translations have been
    enabled for this entity, select "Users may translate this field" to use
    Drupal's translation system.

Please note: no meta tags will be output while the site is in maintenance mode.


## Simplify the content administration experience

This module and its submodules gives a site's content team the ability to add
every meta tag ever. The standard meta tag form added by the Metatag field on
content entities can be overwhelming to content creators and editors who just
need to manage a few options.

The easiest way of simplifying this for content teams is to add new fields to
the content type for the meta data fields that are needed and skip adding the
Metatag field entirely, then use tokens for those fields in the defaults
(/admin/config/search/metatag). These fields can be used in the entity's
display, or just left hidden.


Alternative option to simplify the content administration experience
--------------------------------------------------------------------------------
On the settings page (/admin/config/search/metatag/settings) are options to
control which meta tag groups are available for each entity bundle. This allows
e.g. the Favicon meta tags to be available for global configurations but to hide
them on entity forms.


Programmatically assign meta tags to an entity
--------------------------------------------------------------------------------
There are two ways to assign an entity's meta tags in custom module. Both
scenarios require a "Metatag" field be added to the entity's field settings, the
field name "field_meta_tags" is used but this is completely arbitrary.

Option 1:

```$entity_type = 'node';
$values = [
  'nid' => NULL,
  'type' => 'article',
  'title' => 'Testing metatag creation',
  'uid' => 1,
  'status' => TRUE,
  'field_meta_tags' => serialize([
    'title' => 'Some title',
    'description' => 'Some description.',
    'keywords' => 'Some,Keywords',
  ]),
];
$node = \Drupal::entityTypeManager()
  ->getStorage($entity_type)
  ->create($values);
$node->save();
```

Option 2:

```$node = Node::create([
  'type' => article,
  'langcode' => 'en',
  'status' => 1,
  'uid' => 1,
]);
$node->set('title', 'Testing metatag creation');
$node->set('field_meta_tags', serialize([
  'title' => 'Some title',
  'description' => 'Some description.',
  'keywords' => 'Some,Keywords',
]));
$node->save();
```

In both examples, the custom meta tag values will still be merged with the
values defined via the global defaults prior to being output - it is not
necessary to copy each value to the new record.


## Obtain meta tags for an entity

For developers needing to access the rendered meta tags for a given entity, a
function is provided to make this easy to do:

`$metatags = metatag_generate_entity_metatags($entity);`

This will return an array with the following structure:

```
  [
    'title' => [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'title',
        'content' => 'The What | D8.4',
      ],
    ],
    'canonical_url' => [
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'canonical',
        'href' => 'https://example.com/what',
      ],
    ],
    'description' => [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'description',
        'content' => 'I can't even.',
      ],
    ],
    'generator' => [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'generator',
        'content' => 'Drupal 9!',
      ],
    ],
  ]
```

The meta tags are keyed off the meta tag plugin's ID, e.g. "generator". Each
meta tag is then provided as arguments suitable for use in a render array with
the type "html_tag". Extracting the value of the meta tag will depend upon the
type of meta tag, e.g. the generator meta tag uses the "content" attribute while
the link tag uses the "href" attribute.


## Migration / Upgrade from Drupal 6 or 7

An upgrade path from Nodewords on Drupal 6 or Metatag on Drupal 7 is provided.

Two migration processes are supported:

1. A guided migration using either the Migrate Drupal UI from core or the
   [Migrate Upgrade](https://www.drupal.org/project/migrate_upgrade)
   contributed module. This will automatically create a field named
   "field_metatag" and import any meta tag data that existed in Nodewords on D6
   or Metatag on D7.

   This migration configuration is all prepared in
   metatag_migration_plugins_alter(), the data is loaded onto the migrated
   entity in metatag_migrate_prepare_row(), and then the data is remapped in
   either \Drupal\metatag\Plugin\migrate\process\d6\NodewordsEntities or
   \Drupal\metatag\Plugin\migrate\process\d7\MetatagEntities depending upon
   what the source is.

2. A custom migration using [Migrate
   Plus](https://www.drupal.org/project/migrate_plus) and possibly [Migrate
   Tools](https://www.drupal.org/project/migrate_tools).
   This will require manually creating the meta tag fields and assigning a
   custom process plugin as the source for its data. For example, if the name
   of the field is "field_meta_tags" the lines from the "process" section of
   the migration yml file would need to look line the following:

   For migrating from Nodewords on D6:

```process:
...
  field_meta_tags:
    plugin: d6_nodewords_entities
    source: pseudo_metatag_entities
...
```

   For Migrating from Metatag on D7:

```process:
...
  field_meta_tags:
    plugin: d7_metatag_entities
    source: pseudo_metatag_entities
...
```

  The important items are the "plugin" and the "source" values, if these are
  not present the migration will not work as expected.

  The data will then be loaded into the migrating entity using
  metatag_migrate_prepare_row().

  See also:
  * \Drupal\metatag\Plugin\migrate\process\d6\NodewordsEntities
  * \Drupal\metatag\Plugin\migrate\process\d7\MetatagEntities


## Related modules

Some modules are available that extend Metatag with additional or complimentary
functionality:

* [Schema.org Metatag](https://www.drupal.org/project/schema_metatag):
  Extensive solution for adding schema.org / JSON-LD support to Metatag.

* [Context Metadata](https://www.drupal.org/project/context_metadata):
  Allow assignment of meta tags based upon different system contexts, e.g. per
  path.

* [Real-time SEO for Drupal](https://www.drupal.org/project/yoast_seo):
  Uses the [YoastSEO.js library and service](https://yoast.com/) to provide
  realtime feedback on the meta tags.

* [Metatag Cxense](https://www.drupal.org/project/metatag_cxense):
  Adds support for the Cxense meta tags used by their DMP and Insight services.

* [Metatag Google Scholar](https://www.drupal.org/project/metatag_google_scholar):
  Adds support for a number of meta tags used with the Google Scholar system.


## Known issues

### Uninstalling Metatag

In order to uninstall the module any "Metatag" fields must first be removed
from all entities. In order to see whether there are fields blocking the
module from being uninstalled, load the module uninstall page
(admin/modules/uninstall) and see if any are listed, it will look something
like the following:

`The Meta tags field type is used in the following field:
node.field_meta_tags`

In order to uninstall the module, go to the appropriate field settings pages
and remove the Metatag field listed in the message. Once this is done it will
be possible to uninstall the module.

### Meta tags are not displayed

In order for the meta tags to be displayed on the page the html.html.twig file
must output the main `{{ page }}` element. If the meta tags are not visible on
the page check, to see if the html.html.twig instead contains this:

`{{ page.content }}`

To fix this, simply change that line back to `{{ page }}` and it should work.

## Credits / contact

Currently maintained by [Damien
McKenna](https://www.drupal.org/u/damienmckenna). Drupal 7 module originally
written by [Dave Reid](https://www.drupal.org/u/dave-reid). Early work on
Drupal 8 port by Damien McKenna and [Michelle
Cox](https://www.drupal.org/u/michelle), and sponsored by
[Mediacurrent](https://www.mediacurrent.com/); key improvements by [Juampy
Novillo Requena](https://www.drupal.org/u/juampynr) with insights from Dave
Reid and sponsorship by [Lullabot](https://www.lullabot.com/) and
[Acquia](https://www.acquia.com/). Additional contributions to the 8.x-1.0
release from [cilefen](https://www.drupal.org/u/cilefen), [Daniel
Wehner](https://www.drupal.org/u/dawehner), [Jesus Manuel
Olivas](https://www.drupal.org/u/jmolivas), [Lee
Rowlands](https://www.drupal.org/u/larowlan), [Michael
Kandelaars](https://www.drupal.org/u/mikeyk), [Ivo Van
Geertruyen](https://www.drupal.org/u/mr.baileys), [Nikhilesh
Gupta](https://www.drupal.org/u/nikhilesh-gupta), [Rakesh
James](https://www.drupal.org/u/rakeshgectcr), and many others.

Ongoing development is sponsored by [Mediacurrent](https://www.mediacurrent.com/).

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the [project issue
queue](https://www.drupal.org/project/issues/metatag).
