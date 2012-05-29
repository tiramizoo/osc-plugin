File: admin/categories.php
==========================

After:

    'products_weight' => (float)tep_db_prepare_input($HTTP_POST_VARS['products_weight']),

Insert:

    'products_height' => tep_db_prepare_input($HTTP_POST_VARS['products_height']),
    'products_length' => tep_db_prepare_input($HTTP_POST_VARS['products_length']),
    'products_width' => tep_db_prepare_input($HTTP_POST_VARS['products_width']),
    'products_instant' => tep_db_prepare_input($HTTP_POST_VARS['products_instant']),

----

Lines:

    $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
    $product = tep_db_fetch_array($product_query);

Replace with:

    $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_length, products_width, products_height, products_instant, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
    $product = tep_db_fetch_array($product_query);
 
----

Line: 

    tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), " . (empty($product['products_date_available']) ? "null" : "'" . tep_db_input($product['products_date_available']) . "'") . ", '" . tep_db_input($product['products_weight']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "')");

Replace with:

    tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_date_added, products_date_available, products_weight, products_length, products_width, products_instant, products_height, products_status, products_tax_class_id, manufacturers_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), " . (empty($product['products_date_available']) ? "null" : "'" . tep_db_input($product['products_date_available']) . "'") . ", '" . tep_db_input($product['products_weight']) . "', '" . tep_db_input($product['products_length']) . "', '" . tep_db_input($product['products_width']) . "','" . tep_db_input($product['products_height']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "')");

----

After:

    'products_weight' => '',

Insert:

    'products_length' => '',
    'products_width' => '',
    'products_height' => '',
    'products_instant' => '',

----

Line:

    $product_query = tep_db_query("select pd.products_name, pd.products_description, pd.products_url, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");

Replace with:

    $product_query = tep_db_query("select pd.products_name, pd.products_description, pd.products_url, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, products_length, products_width, products_height, products_instant, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");

----

After: 

    <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_weight', $pInfo->products_weight); ?></td>
    </tr>

Insert:

    <tr>
    <td class="main"><?php echo TEXT_PRODUCTS_INSTANT; ?></td>
    <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_checkbox_field('products_instant', $pInfo->products_instant, false, 'y'); ?></td>
    </tr>
    <tr>
    <td class="main"><?php echo TEXT_PRODUCTS_LENGTH; ?></td>
    <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_length', $pInfo->products_length); ?></td>
    </tr>
    <tr>
    <td class="main"><?php echo TEXT_PRODUCTS_WIDTH; ?></td>
    <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_width', $pInfo->products_width); ?></td>
    </tr>
    <tr>
    <td class="main"><?php echo TEXT_PRODUCTS_HEIGHT; ?></td>
    <td class="main"><?php echo tep_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . tep_draw_input_field('products_height', $pInfo->products_height); ?></td>
    </tr>

---

Line:

    $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_description, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'");

Replace with:

    $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_description, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_length, p.products_width, p.products_height, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'");

File: admin/includes/classes/shopping_cart.php
==============================================

Line:

    $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_price, p.products_weight, p.products_tax_class_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id='" . (int)tep_get_prid($products_id) . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");

Replace with:

    $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_length, p.products_width, p.products_height, p.products_instant, p.products_tax_class_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$products_id . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");

----

After:

    'weight' => $products['products_weight'],

Insert:

    'length' => $products['products_length'],
    'width' => $products['products_width'],
    'height' => $products['products_height'],
    'instant' => $products['products_instant'],

File: admin/includes/languages/english.php
==========================================

Insert:

    define('TEXT_PRODUCTS_HEIGHT', 'Height:');
    define('TEXT_PRODUCTS_LENGTH', 'Length:');
    define('TEXT_PRODUCTS_WIDTH', 'Width:');
    define('TEXT_PRODUCTS_INSTANT', 'Delivery by tiramizoo?');

File: includes/classes/shopping_cart.php
========================================

Line:

    $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_tax_class_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$products_id . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");

Replace with:

    $products_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_tax_class_id, p.products_length, p.products_width, p.products_height, p.products_instant from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$products_id . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");

----

After:

    'weight' => $products['products_weight'],

Insert:

    'length' => $products['products_length'],
    'width' => $products['products_width'],
    'height' => $products['products_height'],
    'instant' => $products['products_instant'],

File: includes/classes/order.php
================================

After:

    'weight' => $products[$i]['weight'],

Insert:

    'width' => $products[$i]['width'],
    'length' => $products[$i]['length'],
    'height' => $products[$i]['height'],
    'instant' => $products[$i]['instant'],
