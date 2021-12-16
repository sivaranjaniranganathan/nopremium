NODE OPTION PREMIUM
===================

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Who can fully view a premium node?
 * Limitations
 * Maintainers


INTRODUCTION
------------

This module adds a new node option "Premium content" to the core publishing
options (which are "Published", "Promoted to front page" and "Sticky at top of
lists"). When a node is published as premium content, only users with proper
privileges may view the full content of the node. Users without these privileges
can still access premium nodes, but only get the content rendered in teaser
context when trying to view the full content. An additional message informs them
that the content is available to premium users only.


FEATURES
--------

 * Mimics the way core publishing options work to ensure maximum integration
   with Drupal core and contributed modules.
 * Easy to use and to configure: you just need to set permissions.
 * Provides a permission to view full premium content for each content type.
 * Provides a permission to override the premium option for each content type.
   It allows users without the "administer nodes" permission to change the
   Premium content option on node editing. This completes what provides the
   essential Override Node Options module for core publishing options.
 * Integrates with Views: the module exposes a Premium content field, filter and
   sort.
 * Allows to theme the message displayed to non-premium users.
 * Allows to theme the teaser & message combination displayed to non-premium
   users.

The following features from the D7 version are not yet ported:

* Provides 2 actions/node operations: "Make content premium" and "Make content
  non-premium".
* Integrates with Rules: the module provides a "Content is premium" condition.


REQUIREMENTS
------------

No special requirements, the module only depends on the Node module, which is
included with Drupal Core.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/extending-drupal/installing-modules
   for further information.


CONFIGURATION
-------------

 * Navigate to admin/people/permissions and set the proper permissions for each
   content type.

 * Navigate to admin/config/content/nopremium to set the message for each
   content type that will be displayed to non-premium users.


WHO CAN FULLY VIEW A PREMIUM NODE?
----------------------------------

Obviously, any user with the view full NODE_TYPE premium content permission can
fully view it. But also:

 * Any user with the "administer nodes" permission.
 * The author of the node.
 * Any user with the edit any NODE_TYPE content permission.


LIMITATIONS
-----------

Node Option Premium does not have any effect on individual fields displayed in
views. But remember that you can control the way fields are displayed at the
theming level by overriding the proper views templates and checking the value of
the "Premium content" field. Node Option Premium has no built-in support for
time-limited premium contents.


MAINTAINERS
-----------

Current maintainers:
 * Youri van Koppen (MegaChriz) - https://www.drupal.org/u/megachriz
   https://www.webcoo.nl
 * Heshan Wanigasooriya (heshanlk) - https://www.drupal.org/u/heshanlk
   http://heididev.com

D7 module maintainer:
 * Henri MEDOT (anrikun) - https://www.drupal.org/u/anrikun
   http://www.absyx.fr

This project has been sponsored by:
 * Absyx
   Development of the D6 and D7 versions.
   http://www.absyx.fr
 * ElectricSage, LLC
   Ported the initial D8 version.
   http://electricsage.com
 * WebCoo
   Fixed bugs, provided automated tests and applied coding standards.
   https://www.webcoo.nl
