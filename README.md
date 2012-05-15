German version see below


Installation of the plugin
==========================

Dimensions patch
----------------

Adds attributes length, height and width to articles. 

  1. Change database with patch.sql
  2. Change fies with patch.diff or manually as described in patch.txt
  
Delivery plugin
---------------

  1. Copy files according to directory structure
  2. Acivate the shipping plugin in the admin area.

For tests, change the variable $api_url in modules/shipping/tiramizoo.php to "https://api-staging.tiramizoo.com/v1"

Shipping dropdown box
---------------------

Include the file includes/tiramizoo.js into checkout_shipping.php.


---------------------------------


Installation des Plugins
========================

Dimensions-Patch
----------------

Fügt den Artikeln die Merkmale Höhe, Länge und Breite hinzu.

  1. Datenbank ändern mit patch.sql
  2. Dateien ändern mit patch.diff oder manuell wie in patch.txt beschrieben
  
Versand-Plugin
--------------

  1. Dateien ensprechend der Verzeichnisstruktur kopieren
  2. Shipping-Plugin im Admin-Bereich aktivieren

Zum Testen die Variable $api_url in modules/shipping/tiramizoo.php ändern in "https://api-staging.tiramizoo.com/v1"

Versandmethoden-Dropdown
-----------------------

Die Datei includes/tiramizoo.js in checkout_shipping.php einbinden.

