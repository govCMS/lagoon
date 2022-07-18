CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Usage
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Focal Point allows you to specify the portion of an image that is most
important. This information can be used when the image is cropped or cropped and
scaled so that you don't, for example, end up with an image that cuts off the
subject's head.

This module borrows heavily from the ImageField Focus module but it works in a
fundamentally different way. In this module the focus is defined as a single
point on the image.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/focal_point

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/focal_point


REQUIREMENTS
------------

This module requires the following module outside of Drupal core:

 * Crop API - https://www.drupal.org/project/crop


INSTALLATION
------------

 * Install the Focal Point module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content types > [Content type to
       edit] > Manage Form Display and choose the "Image (Focal Point)" widget
       for the image field.

 * Setting the focal point for an image:
   To set the focal point on an image, go to the content edit form (ex. the node
   edit form) and upload an image. You will notice a crosshair in the middle of
   the newly uploaded image. Drag this crosshair to the most important part of
   your image. Done.

   Pro tip: you can double-click the crosshair to see the exact coordinates (in
   percentages) of the focal point.

 * Cropping your image:
   The focal point module comes with two image effects:

    1. focal point crop
    2. focal point crop and scale

   Both effects will make sure that the defined focal point is as close to the
   center of your image as possible. It guarantees the focal point will be not
   be cropped out of your image and that the image size will be the specified
   size.


MAINTAINERS
-----------

 * Alexander Ross (bleen) - https://www.drupal.org/u/bleen
