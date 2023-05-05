## 10.x compatibility note.

Several placeholder modules and themes are shipped in this folder that are not yet ready for D10.

Where modules can be patched they will be pre-patched here. Any patches applied (other than updating the `core_version_requirement` value) will be shipped in the relevant module directory.

Modules that haven no viable D10 release (e.g no patches yet exist) will have stub modules created in the `stubs` folder.

### Scaffold-tooling modules
* `fast404`: Has [rector patch](https://www.drupal.org/project/fast_404/issues/3287465) applied.
* All other modules only required an updated `core_version_requirement` value.

### Distribution modules
* `entity_embed`: Has 1.x-dev branch in place.
* `config_perms`: Has 2.x-dev branch in place.
* `panelizer`: Has 4.x-dev branch in place.
* `adminimal_theme`: Has a blank stub module created.
* `adminimal_admin_toolbar`: Has a blank stub module created.
* `block_place`: Has a blank stub module created.
* `features`: Has a blank stub module created.
* `features_ui`: Has a blank stub module created.
* `color`: Has a blank stub module created.
* `ckeditor`: Has a blank stub module created.
* `govcms8_layouts`: Provides display mode templates still depended on for many sites.
