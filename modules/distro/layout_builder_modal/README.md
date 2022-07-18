CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Demo


INTRODUCTION
------------

Layout Builder provides you with the tools to create a modern authoring
experience.

It was initially implemented as a site building tool for you to layout the page
template of a content type, but it can also be used as an authoring tool for
specific content on unique nodes.

Drupal is known for its abilities to provide the author with a structured format
for their content. This spirit carries over to the Layout Builder solution.
To provide the user with a flexible way to create content and position them
within a layout you should leverage Layout Builder's ability to use blocks as
content carriers.

Let's say you want to provide the user with the ability to write text segments
and position them in a different layouts within a specific page, you should then
create a custom block consisting of a text area and make it available in Layout
Builder.

The interface for the user to write content within this custom block would be
through the off-canvas dialog which is the standard solution selected by the
Layout Builder developers. The off-canvas dialog is very narrow and not an
optimal user experience for this scenario, so to provide the author with a
better interface this module lets you use a modal dialog instead.


REQUIREMENTS
------------

This module requires the following core dependencies:

 * Drupal 8 >= 8.70
 * Drupal 8 core module Layout Builder

This module requires no additional modules.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420/ for further information.


CONFIGURATION
-------------

    1. Add the 'administer layout builder modal' permission to the roles who
       should be able to configure the module.
    2. You can now configure the module at:
       `admin/config/user-interface/layout-builder-modal`.


DEMO
----

[Watch a demo](https://www.youtube.com/watch?v=1cZuQAevJeY) of the Layout
Builder Modal module.
