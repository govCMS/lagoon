## 10.x compatibility note.

Several placeholder modules and themes are shipped in this folder that are not yet ready for D10.

Where modules can be patched they will be pre-patched here. Any patches applied (other than updating the `core_version_requirement` value) will be shipped in the relevant module directory.

Modules that haven no viable D10 release (e.g no patches yet exist) will have stub modules created in the `stubs` folder.

### Distribution modules
* `panelizer`: Has 4.x-dev branch in place.
* `govcms8_layouts`: Provides display mode templates still depended on for many sites.
* `ckeditor`: Providers ckeditor4 for backwards compatibility.
