CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Conditions
 * Reactions
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Context allows you to manage contextual conditions and reactions for different
portions of your site. You can think of each context as representing a "section"
of your site. For each context, you can choose the conditions that trigger this
context to be active and choose different aspects of Drupal that should react
to this active context.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/context

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/context


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


CONDITIONS
----------

Context for Drupal 8 uses the built in condition plugins supplied by Drupal
through the [Plugin API](https://www.drupal.org/developing/api/8/plugins). 
So any conditional plug-ins supplied by other modules can also be used with
context.


REACTIONS
---------

Reactions for the context module are defined trough the new Drupal 8 
[Plugin API](https://www.drupal.org/developing/api/8/plugins).

The context module defines a plugin type named Context Reaction that you can
extend when creating your own plugins.

A context reaction requires a configuration form and execute method. The 
execution of the plugin is also something that will have to be handled by the
author of the reaction.


INSTALLATION
------------

 * Install the Context module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and the
       submodule Context UI.
    2. Navigate to Administration > Structure > Context to associate menus,
       views, blocks, etc. with different contexts.
    3. Select "Add context" to add general details for a new context. Save.
    4. Add conditions. When there are no added conditions the context will be
       considered sitewide.
    5. Add reactions.
    6. Save and continue.


MAINTAINERS
-----------

Current maintainers:
 * Bostjan Kovac (boshtian) - https://www.drupal.org/u/boshtian
 * Christoffer Palm (NormySan) - https://www.drupal.org/u/normysan
 * Alexander Hass (hass) - https://www.drupal.org/u/hass
 * Chris Johnson (tekante) - https://www.drupal.org/u/tekante
 * Colan Schwartz (colan) - https://www.drupal.org/u/colan
 * Paulo Henrique Starling (paulocs) - https://www.drupal.org/u/paulocs

Supporting organizations:
 * AGILEDROP - https://www.drupal.org/agiledrop
 * Colan Schwartz Consulting - https://www.drupal.org/agiledrop
 * CI&T - https://www.drupal.org/cit
