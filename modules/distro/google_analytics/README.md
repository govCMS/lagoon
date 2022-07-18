## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Page specific tracking
 * Custom Dimensions And Metrics
 * Advanced setting
 * Manual Js Debugging
 * Usage
 * Maintainers


## INTRODUCTION

The module provides

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/google_analytics

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/google_analytics


## REQUIREMENTS

This module requires no modules outside of Drupal core.


## INSTALLATION

 * Install the Google Analytics module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


## CONFIGURATION

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > System > Google Analytics
       to configure tracking behavior.
    3. Enter your Google Analytics account number.
    4. All pages will now have the required JavaScript added to the HTML footer
       can confirm this by viewing the page source from the browser.


## PAGE SPECIFIC TRACKING

The default is set to "Add to every page except the listed pages". By
default the following pages are listed for exclusion:

```
/admin
/admin/*
/batch
/node/add*
/node/*/*
/user/*/*
```

These defaults are changeable by the website administrator or any other
user with 'Administer Google Analytics' permission.

Like the blocks visibility settings in Drupal core, there is a choice for
"Add if the following PHP code returns TRUE." Sample PHP snippets that can be
used in this textarea can be found on the handbook page "Overview-approach to
block visibility" at https://drupal.org/node/64135.


## CUSTOM DIMENSIONS AND METRICS

One example for custom dimensions tracking is the "User roles" tracking.

    1. In the Google Analytics (https://marketingplatform.google.com/about/analytics/)
       Management Interface you need to setup Dimension #1 with name
       e.g. "User roles". This step is required. Do not miss it, please.

    2. Enter the below configuration data into the Drupal custom dimensions
       settings form under admin/config/services/googleanalytics. You can also
       choose another index, but keep it always in sync with the index used in
       step #1.

   Index: 1
   Value: [current-user:role-names]

More details about custom dimensions and metrics can be found in the Google API
documentation at https://developers.google.com/analytics/devguides/collection/analyticsjs/custom-dims-mets.


## ADVANCED SETTINGS

You can include additional JavaScript snippets in the custom javascript
code textarea. These can be found on the official Google Analytics pages
and a few examples at https://drupal.org/node/248699. Support is not
provided for any customisations you include.

To speed up page loading you may also cache the Google Analytics "analytics.js"
file locally.


## MANUAL JS DEBBUGING

For manual debugging of the JS code you are able to create a test node. This
is the example HTML code for this test node. You need to enable debugging mode
in your Drupal configuration of Google Analytics settings to see verbose
messages in your browsers JS console.

Title: Google Analytics test page
Body:
```
<ul>
  <li><a href="mailto:foo@example.com">Mailto</a></li>
  <li><a href="tel:+1-303-499-7111">Tel</a></li>
  <li><a href="/files/test.txt">Download file</a></li>
  <li><a class="colorbox" href="#">Open colorbox</a></li>
  <li><a href="https://example.com/">External link</a></li>
  <li><a href="/go/test">Go link</a></li>
</ul>
```

Text format: Full HTML


## USAGE

In the settings page enter your Google Analytics account number.

All pages will now have the required JavaScript added to the HTML footer can
confirm this by viewing the page source from our browser.


## MAINTAINERS

 * Alexander Hass (hass) - https://www.drupal.org/user/85918
