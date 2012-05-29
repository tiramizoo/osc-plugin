<?php

/*
  $Id$
  tiramizoo Shipping Module
  http://tiramizoo.com
  Copyright (c) 2011-2012 tiramizoo GmbH
*/

class tiramizoo {

	var $code, $title, $description, $icon, $enabled;
	
	var $api;

	function tiramizoo() {

		/**
			payment modules do provide an after_process() method, shipping modules don't. 
			so we have to hijack the shipping method and ride on it's after_process(). 
			if your shipping module fails, it's most likely because of this hack. 
			curses to those osc looneys wo never thought shipping methods wanted processing
			after an order is submitted.
		**/

		if (basename($GLOBALS["_SERVER"]["SCRIPT_FILENAME"]) === 'checkout_process.php' && class_exists("payment_proxy")) {

			$GLOBALS["payment_backup"] = $GLOBALS[$GLOBALS["payment"]];
			$GLOBALS[$GLOBALS["payment"]] = new payment_proxy();

		}

		$this->code = 'tiramizoo';
		$this->title = '<span id="tiramizoo-title">'.MODULE_SHIPPING_TIRAMIZOO_TEXT_TITLE.'</span>';
		$this->description = MODULE_SHIPPING_TIRAMIZOO_TEXT_DESCRIPTION;
		$this->icon = '';
		$this->enabled = ((MODULE_SHIPPING_TIRAMIZOO_STATUS == 'True') ? true : false);

		$this->api = new tiramizoo_api(MODULE_SHIPPING_TIRAMIZOO_USERID, MODULE_SHIPPING_TIRAMIZOO_APIKEY);

	}

	function quote($method = '') {

		/* get quotes */

		global $order;
				
		$this->quotes = array(
			"id" => $this->code,
         "module" => $this->title,
			"methods" => array()
		);

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
      }

      if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);

		$windows = $this->_find_time();
		
		if ($windows === false) {
			
			return false;
			
		}
		
		$data = array(
			"pickup" => array(
				"address_line_1" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_STREET." ".MODULE_SHIPPING_TIRAMIZOO_PICKUP_NUMBER),
				"postal_code" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_POSTCODE),
				"city" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_CITY),
				"country_code" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_COUNTRY),
				"delay" => (MODULE_SHIPPING_TIRAMIZOO_PICKUP_DELAY+0),
				"windows" => $windows
			),
			"delivery" => array(
				"address_line_1" => $this->_fix_string($order->delivery["street_address"]),
				"postal_code" => $this->_fix_string($order->delivery["postcode"]),
				"city" => $this->_fix_string($order->delivery["city"]),
				"country_code" => $this->_fix_string(strtolower($order->delivery["country"]["iso_code_2"])),
				"intervals" => true
			),
			"items" => array()
		);
			
		/* check min order amount */
				
		if (defined("MODULE_SHIPPING_TIRAMIZOO_MIN_AMOUNT") && MODULE_SHIPPING_TIRAMIZOO_MIN_AMOUNT > $order->info["total"]) {
			
			return false;
			
		}
									
		$weight_factor = (defined("MODULE_SHIPPING_TIRAMIZOO_WEIGHT_FACTOR") && preg_match("/^[0-9\.]$/", MODULE_SHIPPING_TIRAMIZOO_WEIGHT_FACTOR)) ? MODULE_SHIPPING_TIRAMIZOO_WEIGHT_FACTOR : 1;
		$size_factor = (defined("MODULE_SHIPPING_TIRAMIZOO_SIZE_FACTOR") && preg_match("/^[0-9\.]$/", MODULE_SHIPPING_TIRAMIZOO_SIZE_FACTOR)) ? MODULE_SHIPPING_TIRAMIZOO_SIZE_FACTOR : 1;					
						
		if (is_array($order->products)) {
			
			if (!array_key_exists("height", $order->products[0])) {
				
				/* this is not a patched oscommerce with product dimensions support */
				
				return false;
				
			}
			
			foreach ($order->products as $item) {

				if ($item["instant"] !== 'y') {
					
					return false;
					
				}

				$data["items"][] = array(
					"width" => ($item["width"] * $size_factor),
					"height" => ($item["height"] * $size_factor),
					"length" => ($item["length"] * $size_factor),
					"weight" => ($item["weight"] * $weight_factor),
					"quantity" => ($item["qty"]+0)
				);

			}
			
		}
		
		$result = $this->api->request('quotes', $data, $quotes);

		if (!$result) {
			
			/* assume that the delivery windows are 90 minutes after the pickup windows */
			
			$data["delivery"]["windows"] = $this->_find_time(5400);
			unset($data["delivery"]["intervals"]);
						
			$result = $this->api->request('quotes', $data, $quotes);

			if (!$result) {
				
				return false;
				
			}
			
		}
				
		tep_db_query("INSERT INTO tiramizoo_cache SET `expiry` = '".(time()+86400)."', `value` = '".tep_db_input(json_encode($quotes))."'");
		$_SESSION["tiramizoo_quotes_cache"] = tep_db_insert_id();
		
		global $currencies;
		
		foreach ($quotes as $id => $quote) {
			
			$this->quotes["methods"][] = array(
				"id" => $this->code."-".$id,
				"title" => '<span class="tiramizoo-element" data-tiramizoo=\'{"id":"'.$this->code.'_'.$this->code.'-'.$id.'","idhash":"'.md5($this->code.'-'.$id).'","price":"'.$currencies->format(tep_add_tax((($quote["price"]["gross"]/100) * $currencies->currencies[$quote["price"]["currency"]]["value"]), (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))).'","date":"'.constant("MODULE_SHIPPING_TIRAMIZOO_DATE_".strtoupper(date("D",strtotime($quote["delivery"]["after"])))).", ".date("d.m.",strtotime($quote["delivery"]["after"])).'","datehash":"'.md5(date("d.m.Y",strtotime($quote["delivery"]["after"]))).'","timehash":"'.md5(date("H:i",strtotime($quote["delivery"]["after"]))).'","after":"'.date("H:i",strtotime($quote["delivery"]["after"])).'","before":"'.date("H:i",strtotime($quote["delivery"]["before"])).'"}\'>'.MODULE_SHIPPING_TIRAMIZOO_TEXT_QUOTE_TITLE." ".$this->_print_delivery($quote["delivery"]["after"], $quote["delivery"]["before"]).'</span>',
				"cost" => (($quote["price"]["gross"]/100) * $currencies->currencies[$quote["price"]["currency"]]["value"])
			);
			
		}
				
      return $this->quotes;

	}
	
	function submit() {

		/* submit order to tiramizoo */
	
		global $order;
						
		$quote_id = preg_replace("/^.*tiramizoo-([0-9]+)$/", '$1', $_SESSION["shipping"]["id"]);
								
		$description = array();
				
		foreach ($order->products as $item) {
			
			$description[] = $item["qty"]."x ".mb_substr($item["name"],0,100);
			
		}
		
		$description = join("\n", $description);
		
		if (mb_strlen($description) > 512) {
			
			$description = mb_substr($description,0,509)+"...";
			
		}
		
		$result = tep_db_query("SELECT `value` FROM tiramizoo_cache WHERE `id` = '".$_SESSION["tiramizoo_quotes_cache"]."'");
		
		if (tep_db_num_rows($result) <> 1) { return false; }
		
		$quotes = tep_db_fetch_array($result);
		
		$quotes = json_decode($quotes["value"], true);		
			
		$data = array(
			"quote" => $quotes[$quote_id],
			"pickup" => array(
				"name" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_NAME),
				"phone_number" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_PHONE),
				"company" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_COMPANY),
				"email" => $this->_fix_string(MODULE_SHIPPING_TIRAMIZOO_PICKUP_EMAIL)
			),
			"delivery" => array(
				"name" => $this->_fix_string($order->delivery["firstname"]." ".$order->delivery["lastname"]),
				"phone_number" => $this->_fix_string($order->customer["telephone"]),
				"company" => $this->_fix_string($order->delivery["company"]),
				"email" => $this->_fix_string($order->customer["email_address"])
			),
			"description" => $this->_fix_string($description)
		);
						
		$result = $this->api->request('orders', $data, $orders);
						
		if ($result === false) {
			
			/* submitting the order failed. there is nothing we can do about it, but inform the shop owner. */
			
			global $insert_id;
		
			tep_mail(
				STORE_OWNER, 
				STORE_OWNER_EMAIL_ADDRESS,
				MODULE_SHIPPING_TIRAMIZOO_TEXT_SUBMIT_ERROR_TITLE,
				sprintf(MODULE_SHIPPING_TIRAMIZOO_TEXT_SUBMIT_ERROR_CONTENT, $insert_id),
				STORE_OWNER, 
				STORE_OWNER_EMAIL_ADDRESS
			);
			
		}
		
		/* purge old cache data */
		
		tep_db_query("DELETE FROM tiramizoo_cache WHERE `expiry` <= '".time()."'");
		
		return true;

	}

	function check() {

		/* check if the module installed properly */

      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_TIRAMIZOO_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;

	}

	function install() {
		
		/* we need curl for the api */

		if (!function_exists('curl_init')) {
						
			return false;
			
		}
		
		/* FIXME: i18n */

		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Versand mit Tiramizoo aktivieren', 'MODULE_SHIPPING_TIRAMIZOO_STATUS', 'True', 'Möchen Sie den Versand per Fahrradkurier durch Tiramizoo aktivieren?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Key', 'MODULE_SHIPPING_TIRAMIZOO_APIKEY', '', 'Der API Key für Tiramizoo', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Steuerklasse', 'MODULE_SHIPPING_TIRAMIZOO_TAX_CLASS', '0', 'Die Steuerklasse für den Versand mit Tiramizoo.', '6', '0', 'tep_get_tax_class_title', 'tep_cfg_pull_down_tax_classes(', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Vorname', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_FIRST_NAME', '', 'Der Vorname des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Nachname', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_LAST_NAME', '', 'Der Nachname des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Firma', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_COMPANY_NAME', '', 'Der Firmenname des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Straße, Hausnummer', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_ADDRESS_LINE_1', '', 'Straße und Hausnummer des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Adresse Zusatz', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_ADDRESS_LINE_2', '', 'Zusätzliche Adressdaten des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Stadt', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_CITY', '', 'Der Ort des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Bundesland', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_STATE', '', 'Das Bundesland des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Postleitzahl', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_ZIP_CODE', '', 'Die Postleitzahl des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Ländercode', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_COUNTRY_CODE', 'de', 'Der zweistellige Ländercode des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: Steuernummer', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_TAX_ID', '', 'Die Steuernummer des Rechnungsempfängers', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Rechnungsadresse: E-Mail', 'MODULE_SHIPPING_TIRAMIZOO_BILLING_EMAIL', '', 'Die E-Mail-Adresse des Rechnungsempfängers', '6', '2', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Name', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_NAME', '', 'Der vollständige Name des Absenders', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Firma', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_COMPANY', '', 'Der Firmenname der Adresse, an der die Sendung durch den Kurier abgeholt werden soll', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Telefonnummer', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_PHONE', '', 'Eine Telefonnummer, unter der Sie für den Kurier erreichbar sind', '6', '8', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: E-Mail', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_EMAIL', '', 'Eine E-Mail-Adresse, unter der Sie für den Kurier erreichbar sind', '6', '9', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Straße', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_STREET', '', 'Der Straßenname der Adresse, an der die Sendung durch den Kurier abgeholt werden soll', '6', '3', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Hausnummer', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_NUMBER', '', 'Die Hausnummer der Adresse, an der die Sendung durch den Kurier abgeholt werden soll', '6', '4', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Postleitzahl', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_POSTCODE', '', 'Die Postleitzahl der Adresse, an der die Sendung durch den Kurier abgeholt werden soll', '6', '5', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Ort', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_CITY', '', 'Die Stadt der Adresse, an der die Sendung durch den Kurier abgeholt werden soll', '6', '6', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Abholadresse: Land', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_COUNTRY', '', 'Der zweistellige Ländercode der Adresse, an der die Sendung durch den Kurier abgeholt werden soll', '6', '7', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Zeitraum für die Abholung', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_TIME', 'MO,DI,MI,DO,FR:8-22', 'Die Tage und Zeiten, zu denen eine Abholung erfolgen kann. Beispiel: MO,DI,MI,DO:8-16,17-18;FR:12-14', '6', '11', now())");      
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Vorlaufzeit für die Abholung', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_WAIT', '60', 'Anzahl der Minuten bevor eine Abholung möglich ist.', '6', '12', now())");      
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Bearbeitungszeit für die Abholung', 'MODULE_SHIPPING_TIRAMIZOO_PICKUP_DELAY', '5', 'Anzahl der Minuten die der Kurier bei der Abholung auf die Aushändigung warten muss.', '6', '12', now())");      
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Gewichtsfaktor', 'MODULE_SHIPPING_TIRAMIZOO_WEIGHT_FACTOR', '1.0', 'Faktor zur Umrechnung des Produktgewichts in Kilogramm.', '6', '13', now())");      
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Größenfaktor', 'MODULE_SHIPPING_TIRAMIZOO_SIZE_FACTOR', '1.0', 'Faktor zur Umrechnung der Produktmaße in Zentimeter.', '6', '13', now())");      
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mindestbestellwert', 'MODULE_SHIPPING_TIRAMIZOO_MIN_AMOUNT', '50.0', 'Mindestbestellwert für tiramizoo-Lieferung.', '6', '13', now())");      

		tep_db_query("DROP TABLE IF EXISTS `tiramizoo_cache`");
		tep_db_query("CREATE TABLE `tiramizoo_cache` (`id` int(8) NOT NULL AUTO_INCREMENT, `expiry` int(10) NOT NULL, `value` longtext NOT NULL, PRIMARY KEY (`id`), KEY `expiry` (`expiry`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

		return true;

	}

	function remove() {

		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		tep_db_query("DROP TABLE IF EXISTS `tiramizoo_cache`");

	}

	function keys() {
		
		return array('MODULE_SHIPPING_TIRAMIZOO_STATUS','MODULE_SHIPPING_TIRAMIZOO_APIKEY','MODULE_SHIPPING_TIRAMIZOO_TAX_CLASS','MODULE_SHIPPING_TIRAMIZOO_MIN_AMOUNT','MODULE_SHIPPING_TIRAMIZOO_BILLING_FIRST_NAME','MODULE_SHIPPING_TIRAMIZOO_BILLING_LAST_NAME','MODULE_SHIPPING_TIRAMIZOO_BILLING_COMPANY_NAME','MODULE_SHIPPING_TIRAMIZOO_BILLING_ADDRESS_LINE_1','MODULE_SHIPPING_TIRAMIZOO_BILLING_ADDRESS_LINE_2','MODULE_SHIPPING_TIRAMIZOO_BILLING_CITY','MODULE_SHIPPING_TIRAMIZOO_BILLING_STATE','MODULE_SHIPPING_TIRAMIZOO_BILLING_ZIP_CODE','MODULE_SHIPPING_TIRAMIZOO_BILLING_COUNTRY_CODE','MODULE_SHIPPING_TIRAMIZOO_BILLING_TAX_ID','MODULE_SHIPPING_TIRAMIZOO_BILLING_EMAIL','MODULE_SHIPPING_TIRAMIZOO_PICKUP_NAME','MODULE_SHIPPING_TIRAMIZOO_PICKUP_COMPANY','MODULE_SHIPPING_TIRAMIZOO_PICKUP_PHONE','MODULE_SHIPPING_TIRAMIZOO_PICKUP_EMAIL','MODULE_SHIPPING_TIRAMIZOO_PICKUP_STREET','MODULE_SHIPPING_TIRAMIZOO_PICKUP_NUMBER','MODULE_SHIPPING_TIRAMIZOO_PICKUP_POSTCODE','MODULE_SHIPPING_TIRAMIZOO_PICKUP_CITY','MODULE_SHIPPING_TIRAMIZOO_PICKUP_COUNTRY','MODULE_SHIPPING_TIRAMIZOO_PICKUP_TIME','MODULE_SHIPPING_TIRAMIZOO_PICKUP_WAIT','MODULE_SHIPPING_TIRAMIZOO_PICKUP_DELAY','MODULE_SHIPPING_TIRAMIZOO_WEIGHT_FACTOR','MODULE_SHIPPING_TIRAMIZOO_SIZE_FACTOR');

	}
	
	function _find_time($drift = 0) {
		
		$time = time();
		
		if (!defined("MODULE_SHIPPING_TIRAMIZOO_PICKUP_WAIT") || !preg_match('/^[0-9]+$/', MODULE_SHIPPING_TIRAMIZOO_PICKUP_WAIT)) {
						
			return false;
			
		}
		
		$time += (MODULE_SHIPPING_TIRAMIZOO_PICKUP_WAIT * 60);

		if (!defined("MODULE_SHIPPING_TIRAMIZOO_PICKUP_TIME") || !preg_match('/^((MO|DI|MI|DO|FR|SA|SO)(,(MO|DI|MI|DO|FR|SA|SO))*:([0-9]+\-[0-9]+)(,[0-9]+\-[0-9]+)*)(;(MO|DI|MI|DO|FR|SA|SO)(,(MO|DI|MI|DO|FR|SA|SO))*:([0-9]+\-[0-9]+)(,[0-9]+\-[0-9]+)*)*(;)?$/', str_replace(" ", "", MODULE_SHIPPING_TIRAMIZOO_PICKUP_TIME))) {
						
			return false;
			
		}		
		
		$dtable = array(
			"SO" => 0,
			"MO" => 1,
			"DI" => 2,
			"MI" => 3,
			"DO" => 4,
			"FR" => 5,
			"SA" => 6
		);
		
		$time_table = array(
			0 => array(),
			1 => array(),
			2 => array(),
			3 => array(),
			4 => array(),
			5 => array(),
			6 => array()
		);	
			
		foreach (explode(";", str_replace(" ", "", MODULE_SHIPPING_TIRAMIZOO_PICKUP_TIME)) as $t) {
			
			$t = explode(":", $t);
			$days = explode(",",$t[0]);
			$slides = explode(",",$t[1]);
			
			foreach ($slides as $sl) {
				
				$sl = explode("-", $sl);
				
				foreach ($days as $d) {
					
					$time_table[$dtable[strtoupper($d)]][] = array(
						"from" => $sl[0],
						"to" => $sl[1]
					);
					
				}
				
			}
			
		}
		
		/* sort */
		
		foreach ($time_table as $d => $slides) {
			
			 usort($time_table[$d], array($this, "_find_time_sort"));
			
		}
								
		/* check if the soonest time is in todays time slide */
		
		$d = date("w", $time);
		
		$times = array();
		$timehashes = array();

		if (count($time_table[$d]) > 0) {
			
			foreach ($time_table[$d] as $slide) {
				
				if (($slide["from"]*100) < date("Gi", $time) && ($slide["to"]*100) > date("Gi", $time)) {

					$timehashes[] = md5(gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["from"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))).gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["to"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))));
					$timehashes[] = md5(gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime(date("h", $time), date("i", $time), 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))).gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["to"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))));
					
					$times[] = array(
						"after" => gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime(date("h", $time), date("i", $time), 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))),
						"before" => gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["to"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time))))
					);

				}
				
			}
			
		}
		
		/* find next times */

		$add = 0;
		
		for ($i = $d; $i < ($d+6); $i++) {

			$dd = $i%7;
						
			if (count($time_table[$dd]) > 0) {

				foreach ($time_table[$dd] as $slide) {
					
					if (($slide["from"]*100) > date("Gi", $time) || $dd !== date("w", $time)) {

						$timehash = md5(gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["from"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))).gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["to"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))));

						if (!in_array($timehash, $timehashes)) {
							
							$timehashes[] = $timehash;
							
							$times[] = array(
								"after" => gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["from"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time)))),
								"before" => gmdate("Y-m-d\TH:i:s.000\Z", ($drift + mktime($slide["to"], 0, 0, date("n", $time), (date("j", $time)+$add), date("Y", $time))))
							);
							
						}
						
					}

				}

			}
			
			$add++;
			
		}
		
		return $times;

	}
	
	function _find_time_sort($a, $b) {
		
		if ($a["from"] == $b["from"]) {
			return 0;
		}

		return ($a["from"] < $b["from"]) ? -1 : 1;
		
	}
	
	function _print_delivery($after, $before) {
		
		$after = strtotime($after);
		$before = strtotime($before);
		
		if (date("Y.m.d", $after) == date("Y.m.d", $before)) {
			
			return sprintf(MODULE_SHIPPING_TIRAMIZOO_TEXT_TIMES_SAMEDAY, date("d.m.Y", $after), date("H:i", $after), date("H:i", $before));
			
		} else {
			
			return sprintf(MODULE_SHIPPING_TIRAMIZOO_TEXT_TIMES_DIFFDAY, date("d.m.Y, H:i", $after), date("d.m.Y, H:i", $before));
			
		}
		
	}
	
	function _fix_string($str) {
		
		/**
			since we cannot rely on xtc to provide proper utf-8, we will try to create utf-8 of our own
		*/
		
		if (!$this->_is_utf8($str)) {
			return utf8_encode($str);
		} 
		
		return $str;
		
	}
	
	function _is_utf8($str) {
	    $c=0; $b=0;
	    $bits=0;
	    $len=strlen($str);
	    for($i=0; $i<$len; $i++){
	        $c=ord($str[$i]);
	        if($c > 128){
	            if(($c >= 254)) return false;
	            elseif($c >= 252) $bits=6;
	            elseif($c >= 248) $bits=5;
	            elseif($c >= 240) $bits=4;
	            elseif($c >= 224) $bits=3;
	            elseif($c >= 192) $bits=2;
	            else return false;
	            if(($i+$bits) > $len) return false;
	            while($bits > 1){
	                $i++;
	                $b=ord($str[$i]);
	                if($b < 128 || $b > 191) return false;
	                $bits--;
	            }
	        }
	    }
	    return true;
	}

}

if (!class_exists("tiramizoo_api")) {

	class tiramizoo_api {
	
		private $api_url = 'https://api.tiramizoo.com/v1';
	
		public function tiramizoo_api($api_userid, $api_key) {}
		
		public function request($method, $data = array(), &$result = false) {
		
			$c = curl_init();

			curl_setopt($c, CURLOPT_URL, $this->api_url.'/'.$method.'?api_key='.MODULE_SHIPPING_TIRAMIZOO_APIKEY);
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, preg_replace_callback('/(\\\u[0-9a-f]{4})/', array($this, "json_unescape"), json_encode($data)));

			curl_setopt($c, CURLOPT_HTTPHEADER, array(
				"Content-Type: application/json",
				"Accept: application/json"
			));

			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

			$result = curl_exec($c);
			$status = curl_getinfo($c, CURLINFO_HTTP_CODE);

			curl_close($c);

			switch ($status) {
				case 200:
				case 201:
					$result = json_decode($result,true);
					return true;
				break;
				default:
					return false;
				break;
			}
	
		}	
	
   	private function json_unescape($m) {
	
	      return json_decode('"'.$m[1].'"');
	
      }
      
	}
	
}

if (!class_exists("payment_proxy")) {

	class payment_proxy {
		
		/*
			an instance of this class replaces the original payment class for one purpose:
			to execute code when the order is completed. it should act as a proxy for all
			methods and properties of the original payment class. however: if your payment
			method is unexpectedly broken, you may check this.
		*/
	
		var $code, $title, $description, $enabled;
	
		function payment_proxy() {
		
			$this->code = $GLOBALS["payment_backup"]->code;
		   $this->title = $GLOBALS["payment_backup"]->title;
		   $this->description = $GLOBALS["payment_backup"]->description;
		   $this->sort_order = $GLOBALS["payment_backup"]->sort_order;
		   $this->enabled = $GLOBALS["payment_backup"]->enabled;
			$this->order_status = $GLOBALS["payment_backup"]->order_status;

		}
	
		function update_status() { 
		
			$GLOBALS["payment_backup"]->update_status(); 
		   $this->enabled = $GLOBALS["payment_backup"]->enabled;
		
		}
   
	   function javascript_validation() {
			return $GLOBALS["payment_backup"]->javascript_validation(); 
	   }
   
	   function selection() {
			return $GLOBALS["payment_backup"]->selection(); 	
		}

		function pre_confirmation_check() {
	 		return $GLOBALS["payment_backup"]->pre_confirmation_check(); 	
		}
	
		function confirmation() {
	 		return $GLOBALS["payment_backup"]->confirmation(); 	
		}

		function process_button() {
	 		return $GLOBALS["payment_backup"]->process_button(); 	
		}

		function before_process() {
	 		return $GLOBALS["payment_backup"]->before_process(); 	
		}

		function after_process() {

			/* code injection */
			if (preg_match('/^tiramizoo/', $GLOBALS["shipping"]["id"])) {
				$GLOBALS["tiramizoo"]->submit();
			}

	 		return $GLOBALS["payment_backup"]->after_process(); 
	
		}

		function get_error() {
	 		return $GLOBALS["payment_backup"]->get_error(); 	
		}
	
		function check() {
	 		return $GLOBALS["payment_backup"]->check(); 	
		}
	
		function install() {
	 		$GLOBALS["payment_backup"]->install(); 	
		}
	
		function remove() {
	 		$GLOBALS["payment_backup"]->remove(); 	
		}
	
		function keys() {
	 		return $GLOBALS["payment_backup"]->keys(); 	
		}
	
	}
	
}

?>