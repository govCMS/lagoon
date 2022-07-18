# Entity Reference Display

## Overview

This module defines a simple field type for display mode selection for entity
reference fields. This allows an editor to select how they would like the
references displayed.

**"Display mode" field type**: This field allows you to specify a display mode
for the rendering of entity reference fields. You can configure available
options to show only certain display modes. The user then selects one from these
and affects the way your entity reference field renders items.

**"Selected display mode" field formatter**: This formatter allows you to render
referenced entities with a selected display mode. The formatter is available
only for entity reference fields where the base entity contains at least one
display mode field. When there are more display fields available, you can choose
one.

## Related Modules

- [Entity reference viewmode selector](http://drupal.org/project/er_viewmode)
(D7 only) - This module provides separate display mode selection for every
referenced entity. Use our module if you want only one selection per entity
reference field.
- [Entity reference multiple display](http://drupal.org/project/entityreference_multiple)
(D7 only) - Provides formatter that let site administrators configure different
view modes for groups of entities. Use our module if you want display mode
selection for site editors.

## Installation

1. Module can be installed via the
[standard Drupal installation process](http://drupal.org/node/1897420).
2. Go to the "Manage fields" page for an entity.
3. Add a new field of "Display mode" type.
4. You can then adjust the field settings (e.g. default value, excluded or
included modes).
5. After saving the field, go to the "Manage form display" tab.
6. Select the desired widget for the display mode field and save the form.
7. Go to "Manage display" and find your existing entity reference field.
8. Choose "Selected display mode" format and save the form again.
9. That's it. Just edit the entity and select different displays.

## Maintainers

These modules are maintained by developers at Morpht. For more information on
the company and our offerings, see [morpht.com](http://morpht.com/).
