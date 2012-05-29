German version see below

Installation of the plugin
==========================

Article options patch
---------------------

Adds attributes length, height, width and booking enabled to articles. 

  1. Change database with patch.sql
  2. Change fies with patch.diff or manually as described in patch.md
  
Delivery plugin
---------------

  1. Copy files according to directory structure
  2. Acivate the shipping plugin in the admin area.

For tests, change the variable $api_url in modules/shipping/tiramizoo.php to "https://api-staging.tiramizoo.com/v1"

Shipping dropdown box
---------------------

  1. Include a current version of jQuery in the shops code
  2. Include the file includes/tiramizoo-de.js or includes/tiramizoo-en.js into checkout_shipping.php.


---------------------------------


Installation des Plugins
========================

Artikeloptionen-Patch
---------------------

Fügt den Artikeln die Merkmale Höhe, Länge, Breite und Buchungsverfügbarkeit hinzu.

  1. Datenbank ändern mit patch.sql
  2. Dateien ändern mit patch.diff oder manuell wie in patch.md beschrieben
  
Versand-Plugin
--------------

  1. Dateien ensprechend der Verzeichnisstruktur kopieren
  2. Shipping-Plugin im Admin-Bereich aktivieren

Zum Testen die Variable $api_url in modules/shipping/tiramizoo.php ändern in "https://api-staging.tiramizoo.com/v1"

Versandmethoden-Dropdown
------------------------

  1. Eine Aktuelle version von jQuery im Shop einbinden
  2. Die Datei includes/tiramizoo-de.js oder includes/tiramizoo-en.js in checkout_shipping.php einbinden.

