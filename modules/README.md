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
* `username_enumeration_prevention`: Has rector patch applied.
* `key`: Has rector patch applied.
* `crop`: Has [patch applied](https://www.drupal.org/project/crop/issues/3286828).
* `adminimal_admin_toolbar`: Added ^10 as a supported version.
* `adminimal_theme`: Added ^10 as a supported version.
* `bigmenu`: Added ^10 as a supported version.
* `block_place`: Added ^10 as a supported version.
* `config_ignore`: Added ^10 as a supported version.
* `config_perms`: Added ^10 as a supported version.
* `config_update`: Added ^10 as a supported version.
* `consumers`: Added ^10 as a supported version.
* `contact_storage`: Added ^10 as a supported version.
* `context`: Added ^10 as a supported version.
* `diff`: Added ^10 as a supported version.
* `embed`: Added ^10 as a supported version.
* `encrypt`: Added ^10 as a supported version.
* `ga_login`: Added ^10 as a supported version.
* `real_aes`: Added ^10 as a supported version.
* `securitytxt`: Added ^10 as a supported version.
* `update_notifications_disable`: Added ^10 as a supported version.


#### Known problem modules:
 * `components`: No viable D10 patch available, stub created.
 * `password_policy`: No viable D10 patch available, stub created.
 * `tfa`: No viable D10 patch available, stub created.
 * `devel`: No viable D10 patch available, commentary [here](https://gitlab.com/drupalspoons/devel/-/issues/392)
 * `ds`: No viable D10 patch available

