## 10.x compatibility note.

Several placeholder modules and themes are shipped in this folder that are not yet ready for D10.

Where modules can be patched they will be pre-patched here. Any patches applied (other than updating the `core_version_requirement` value) will be shipped in the relevant module directory.

Modules that haven no viable D10 release (e.g no patches yet exist) will have stub modules created in the `stubs` folder.

### Scaffold-tooling modules
* `redis`: Has [rector patch](https://www.drupal.org/project/redis/issues/3289284) applied.
* `stage_file_proxy`: Has [manual patch](https://www.drupal.org/project/stage_file_proxy/issues/3283529) applied.
* `fast404`: Has [rector patch](https://www.drupal.org/project/fast_404/issues/3287465) applied.
* `purge`: Has been created as a stub. First needs [8.1 merging](https://www.drupal.org/project/purge/issues/3259320) followed by [D10 readiness](https://www.drupal.org/project/purge/issues/3272193).
* All other modules only required an updated `core_version_requirement` value.

### Distribution modules
* `key`: Has [patch applied](https://www.drupal.org/project/key/issues/3278542) to fix drush commands.
* `username_enumeration_prevention`: Has rector patch applied (see patch in folder).
* `seckit`: Has rector patch applied (see patch in folder).
* `key`: Has rector patch applied, not 100% compatible yet.
* `crop`: Has [patch applied](https://www.drupal.org/project/crop/issues/3286828).
* `adminimal_admin_toolbar`: Added ^10 as a supported version.
* `adminimal_theme`: Added ^10 as a supported version, not 100% compatible yet.
* `bigmenu`: Added ^10 as a supported version.
* `block_place`: Added ^10 as a supported version.
* `config_ignore`: Added ^10 as a supported version.
* `config_perms`: Added ^10 as a supported version.
* `config_update`: Added ^10 as a supported version.
* `consumers`: Added ^10 as a supported version.
* `contact_storage`: Added ^10 as a supported version, not 100% compatible yet.
* `context`: Added ^10 as a supported version.
* `diff`: Added ^10 as a supported version, not 100% compatible yet.
* `embed`: Added ^10 as a supported version, not 100% compatible yet.
* `encrypt`: Added [rector patches](https://www.drupal.org/project/encrypt/issues/3297063).
* `entity_class_formatter`: Added ^10 as a supported version, not 100% compatible yet.
* `entity_embed`: [Rector patches](https://www.drupal.org/project/entity_embed/issues/3287235) + added ^10
* `entity_hierarchy`: Added ^10 as a supported version.
* `entity_reference_display`: Added ^10 as a supported version.
* `fakeobjects`: Added ^10 as a supported version.
* `field_group`: Added ^10 as a supported version and [issue patch](https://www.drupal.org/project/field_group/issues/3278537).
* `focal_point`: Added ^10 as a supported version.
* `google_analytics`: Added [issue patch](https://www.drupal.org/project/google_analytics/issues/3287765).
* `govcms_dlm`: Added ^10 as a supported version.
* `inline_entity_form`: Added ^10 as a supported version.
* `layout_builder_modal`: Added [rector patch](https://www.drupal.org/project/layout_builder_modal/issues/3288232).
* `layout_builder_restrictions`: Added [issue patch](https://www.drupal.org/project/layout_builder_restrictions/issues/3257889).
* `linked_field`: Added ^10 as a supported version.
* `media_entity_file_replace`: Added [rector patch](https://www.drupal.org/project/media_entity_file_replace/issues/3288492).
* `media_file_deleta`: Added ^10 as a supported version.
* `menu_block`: Added [rector patch](https://www.drupal.org/project/menu_block/issues/3288540).
* `menu_trail_by_path`: Added [rector patch](https://www.drupal.org/project/menu_trail_by_path/issues/3288570).
* `metatag`: Added [issue patch](https://www.drupal.org/project/metatag/issues/3252150).
* `recaptcha`: Added [issue patch](https://www.drupal.org/project/recaptcha/issues/3272700).
* `rest_menu_items`: Added ^10 as a supported version.
* `robotstxt`: Added [rector patch](https://www.drupal.org/project/robotstxt/issues/3297979).
* `ga_login`: Added ^10 as a supported version, added [rector patch](https://www.drupal.org/project/ga_login/issues/3297284).
* `real_aes`: Added ^10 as a supported version.
* `securitytxt`: Added ^10 as a supported version.
* `update_notifications_disable`: Added ^10 as a supported version.


#### Known problem modules:
 * `adminimal_theme`: Included version not 100% compatible yet.
 * `components`: No viable D10 patch available, stub created.
 * `contact_storage`: Included version not 100% compatible yet.
 * `diff`: Included version not 100% compatible yet.
 * `embed`: Included version not 100% compatible yet.
 * `password_policy`: No viable D10 patch available, stub created.
 * `tfa`: No viable D10 patch available, stub created.
 * `devel`: No viable D10 patch available, stub created, commentary [here](https://gitlab.com/drupalspoons/devel/-/issues/392)
 * `ds`: No viable D10 patch available, stub created.
 * `features`: No viable D10 patch available, stub created.
 * `key`: No viable D10 patch available. Included version is patched but not 100% compatible.
 * `linkit`: No viable D10 patch available, stub created.
 * `login_security`: No viable D10 patch available, stub created.
 * `migrate_file`: No viable D10 patch available, stub created.
 * `minisite`: No viable D10 patch available, stub created.
 * `module_permissions`: No viable D10 patch available, stub created.
 * `panelizer`: No viable D10 patch available, stub created.
 * `panels`: No viable D10 patch available, stub created.
 * `password_policy`: No viable D10 patch available, stub created.
 * `shield`: No viable D10 patch available (blocked by `key`), stub created.
 * `simple_oauth`: No viable D10 patch available, stub created.
 * `swiftmailer`: Replaced by Symfony Mailer, stub created.
 * `twig_tweak`: No viable D10 patch available, stub created.
 * `video_embed_field`: No viable D10 patch available, stub created.
 * `webform`: No viable D10 patch available, stub created.