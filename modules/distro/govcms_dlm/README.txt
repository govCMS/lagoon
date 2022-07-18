GovCMS DLM
-------------------

This module adds the option for a user to set a Dissemination Limiting Marker
(DLM) appended to the end of the subject for all outgoing emails sent using
drupal_mail() on your site.


Requirements
------------

* Drupal 8


Installation and configuration
------------------------------

1. Install GovCMS DLM module as you install a contributed Drupal module.
   See https://www.drupal.org/documentation/install/modules-themes/modules-8

2. Go to /admin/config/system/dlm to configure the module.

Your site will now append the selected DLM to the end of all email subjects sent
using drupal_mail(). Please note that any module that sends emails not using
drupal_mail will not append the DLM to the email subject.


Further reading
---------------

* https://govcms.gov.au
* https://drupal.org/project/govcms
