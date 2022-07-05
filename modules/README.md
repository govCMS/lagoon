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
* `username_enumeration_prevention`: Has rector patch applied.
* `seckit`: Has rector patch applied.
* ...@todo: Lots to go through here.
