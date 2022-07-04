## 10.x compatibility note.

Several placeholder modules and themes are shipped in this folder that are not yet ready for D10.

Where modules can be patched they will be pre-patched here. Any patches applied (other than updating the `core_version_requirement` value) will be shipped in the relevant module directory.

Modules that haven no viable D10 release (e.g no patches yet exist) will have stub modules created in the `stubs` folder.

### Scaffold-tooling modules
* ...

### Distribution modules
* `key`: Has [patch applied](https://www.drupal.org/project/key/issues/3278542) to fix drush commands.
* `username_enumeration_prevention`: Has rector patch applied.
* `seckit`: Has rector patch applied.
* ...
