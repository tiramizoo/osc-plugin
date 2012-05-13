Installation des Plugins
========================

Dimensions-Patch
----------------

Fügt den Artikeln die Merkmale Höhe, Länge und breite hinzu.

  1. Datenbank ändern mit patch.sql
  2. Dateien ändern mit patch.diff oder manuell wie in patch.txt beschrieben
  
Versand-Plugin
--------------

  1. Dateien ensprechend der Verzeichnisstruktur kopieren
  2. Shipping-Plugin aktivieren

Zum Testen die Variable $api_url in modules/shipping/tiramizoo.php ändern in "https://api-staging.tiramizoo.com/v1"

Versandmethoen-Dropdown
-----------------------

Die Datei includes/tiramizoo.js in checkout_shipping.php einbinden.

