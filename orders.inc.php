<?php
/**
 * @return test order BODY
 */
function _moysklad_test_user_order() {
  $orderXML = new SimpleXMLElement("<customerOrder></customerOrder>");

    $orderXML->addAttribute('vatIncluded', 'true');
    $orderXML->addAttribute('applicable', 'true');
    $orderXML->addAttribute('sourceStoreUuid', variable_get('default_warehouse'));
    $orderXML->addAttribute('payerVat', 'false');
    $orderXML->addAttribute('sourceAgentUuid', 'd55bd47e-6e3d-11e5-90a2-8ecb0000685c');
    $orderXML->addAttribute('targetAgentUuid', variable_get('default_mycompany'));


    foreach ($variable as $key => $value) {
      # code...
    }
    $orderPosition = $orderXML->addChild('customerOrderPosition');

      $orderPosition->addAttribute('vat', 0);
      $orderPosition->addAttribute('goodUuid', '72c8ac4e-6e3e-11e5-7a40-e8970000687e');
      $orderPosition->addAttribute('quantity', '4');
      $orderPosition->addAttribute('discount', 0);

      $orderPrice = $orderPosition->addChild('basePrice');
        $orderPrice->addAttribute('sumInCurrency', '22200');
        $orderPrice->addAttribute('sum', '22200');

      $orderReserve = $orderPosition->addChild('reserve', 0.0);

      file_put_contents("order-XX.xml", $orderXML->asXML());

      return $orderXML->asXML();
}


function _moysklad_order_send ( $body ) {

  $sock = fsockopen("ssl://online.moysklad.ru", 443, $errno, $errstr, 30);

  if (!$sock) die("$errstr ($errno)\n");

  fputs($sock, "PUT /exchange/rest/ms/xml/CustomerOrder HTTP/1.1\r\n");
  fputs($sock, "Host: online.moysklad.ru\r\n");
  fputs($sock, "Authorization: Basic " . base64_encode( variable_get('moysklad_login').':'.variable_get('moysklad_pass') ) . "\r\n");
  fputs($sock, "Content-Type: application/xml \r\n");
  fputs($sock, "Accept: */*\r\n");
  fputs($sock, "Content-Length: ".strlen($body)."\r\n");
  fputs($sock, "Connection: close\r\n\r\n");
  fputs($sock, "$body");

  while ($str = trim(fgets($sock, 4096)));

  $body = "";

  while (!feof($sock))
      $body.= fgets($sock, 4096);

  fclose($sock);

  return($body);
}

function _moysklad_company_send ( $body ) {

  $sock = fsockopen("ssl://online.moysklad.ru", 443, $errno, $errstr, 30);

  if (!$sock) die("$errstr ($errno)\n");

  fputs($sock, "PUT /exchange/rest/ms/xml/Company HTTP/1.1\r\n");
  fputs($sock, "Host: online.moysklad.ru\r\n");
  fputs($sock, "Authorization: Basic " . base64_encode( variable_get('moysklad_login').':'.variable_get('moysklad_pass') ) . "\r\n");
  fputs($sock, "Content-Type: application/xml \r\n");
  fputs($sock, "Accept: */*\r\n");
  fputs($sock, "Content-Length: ".strlen($body)."\r\n");
  fputs($sock, "Connection: close\r\n\r\n");
  fputs($sock, "$body");

  while ($str = trim(fgets($sock, 4096)));

  $body = "";

  while (!feof($sock))
      $body.= fgets($sock, 4096);

  fclose($sock);

  return($body);
}


/**
 * Creatind xml request
 * Формируем xml запрос для отсылки через api моегосклада
 *
 * @param  $auuid     sourceAgentUuid string(36)
 * @param  $items
 * @param  $coupons   купоны с заказом
 * @return order BODY in XML
 */
function _moysklad_user_order_to_xml( $auuid, $items, $coupons, $description ) {
  $orderXML = new SimpleXMLElement("<customerOrder></customerOrder>");

    $orderXML->addAttribute('vatIncluded', 'true');
    $orderXML->addAttribute('applicable', 'true');
    $orderXML->addAttribute('sourceStoreUuid', variable_get('default_warehouse'));
    $orderXML->addAttribute('payerVat', 'false');
    $orderXML->addAttribute('sourceAgentUuid', $auuid );
    $orderXML->addAttribute('targetAgentUuid', variable_get('default_mycompany'));

    $desc = $orderXML->addChild('description', $description);

    foreach ($items as $item ) {

      $discount = 0;
      if ($coupons && function_exists('uc_coupon_find')) {
        reset($coupons);
        $coupon = uc_coupon_find(key($coupons));
        // $discount_by_coupon = $coupon[$item->nid]->discount;
        // $discount = $discount_by_coupon / $item->qty;

        $discount = $coupon->value;
      }

      $orderPosition = $orderXML->addChild('customerOrderPosition');

        $orderPosition->addAttribute('vat', 0);
        $orderPosition->addAttribute('goodUuid', _moysklad_model_to_guuid( $item->model ));
        $orderPosition->addAttribute('quantity', $item->qty );
        $orderPosition->addAttribute('discount', $discount);

        $orderPrice = $orderPosition->addChild('basePrice');
          $orderPrice->addAttribute('sumInCurrency', ($item->price) * 100 );
          $orderPrice->addAttribute('sum', ($item->price) * 100 );

        $orderReserve = $orderPosition->addChild('reserve', $item->qty );

    }
      file_put_contents("order-XX.xml", $orderXML->asXML());

      return $orderXML->asXML();
}





/**
 * Creatind XML request with NEW user data
 * @param  array      $contacts   пользовательские данные из формы!!!!
 * @param  string     $updatedBy  логин менеджена который регистрирует нового клиента
 * @return XMLstring              Company POST BODY in XML
 */
function _moysklad_user_to_xml( $contacts, $updatedBy ) {

  /**
   * filterin and test case
   */

  // TODO : turn on when testing ends
  if (empty($contacts['email'])) {
    // return false;
  }
  // trim(strip_tags())
  $_name = trim(strip_tags($contacts['last_name'] . " " . $contacts['first_name'] . " " . $contacts['company']));
  $_phone = str_replace("-", "", trim(strip_tags($contacts['phone'])));
  $_mail = $contacts['email'];
  $_address = trim(strip_tags($contacts['city'] . " " . $contacts['street1'] . " " . $contacts['street2']));


  $companyXML = new SimpleXMLElement("<company></company>");

  $companyXML->addAttribute("updatedBy", $updatedBy );
  $companyXML->addAttribute("name", $_name );
  $companyXML->addAttribute("discount", "0.0");
  $companyXML->addAttribute("autoDiscount", "0.0");
  $companyXML->addAttribute("discountCardNumber", "");
  $companyXML->addAttribute("discountCorrection", "0.0");
  $companyXML->addAttribute("archived", "false");
  $companyXML->addAttribute("payerVat", "true");
  $companyXML->addAttribute("companyType", "URLI");

    // $companyXML->addChild("accountUuid", "eada1897-4a4f-11e4-7a07-673c000015d0");
    // $companyXML->addChild("accountId", "eada1897-4a4f-11e4-7a07-673c000015d0");

    $companyContacts = $companyXML->addChild("contact");

    $companyContacts->addAttribute("address", $_address);
    $companyContacts->addAttribute("phones", $_phone );
    $companyContacts->addAttribute("faxes", "");
    $companyContacts->addAttribute("mobiles", "");
    $companyContacts->addAttribute("email", $_mail );

    $companyRequisite = $companyXML->addChild("requisite");

    $companyRequisite->addAttribute("actualAddress", $_address );
    $companyRequisite->addAttribute("legalTitle", "");
    $companyRequisite->addAttribute("legalAddress", "");
    $companyRequisite->addAttribute("inn", "");
    $companyRequisite->addAttribute("kpp", "");
    $companyRequisite->addAttribute("okpo", "");
    $companyRequisite->addAttribute("ogrn", "");
    $companyRequisite->addAttribute("ogrnip", "");
    $companyRequisite->addAttribute("nomerSvidetelstva", "");

    // $companyBank = $companyXML->addChild("bankAccount");

    // $companyBank->addAttribute("accountNumber", "");
    // $companyBank->addAttribute("bankLocation", "");
    // $companyBank->addAttribute("bic", "");
    // $companyBank->addAttribute("isDefault", "");
    // $companyBank->addAttribute("updatedBy", $updatedBy);

  file_put_contents("company-XX.xml", $companyXML->asXML());
  return $companyXML->asXML();
}



/**
 * @param  user mail to link site users to cache table uuid
 * @return actual sourceAgentUuid - auuid
 */
function _moysklad_email_to_auuid( $mail ) {
  $auuid = db_select('uc_moysklad_users_chache', 'u')
    ->fields('u', array('uuid', 'mail'))
    ->condition('u.mail', $mail )
    ->execute()
    ->fetchAssoc();

  return ($auuid['uuid']);
}


/**
 * Поиск уникального идентификатора товара по названию
 * @param  string $title   название товара
 * @return STRING          буквенно-числовой идентификатор моегосклада
 */
function _moysklad_title_to_guuid( $title ) {
  $goods = db_select('uc_moysklad_stock_chache', 'sc')
    ->fields('sc', array('uuid', 'name'))
    ->condition('sc.name', $title)
    ->execute()
    ->fetchAssoc();

    return $goods['uuid'];

}



/**
 * Поиск идентификатора по SKU(model) товара в заказе
 * @param  STRING   $model   sku товара
 * @return STRING            Идентификатор товара
 */
function _moysklad_model_to_guuid( $model ) {
  $goods = db_select('uc_moysklad_stock_chache', 'sc')
    ->fields('sc', array('uuid', 'code'))
    ->condition('sc.code', $model)
    ->execute()
    ->fetchAssoc();

  $guuid = $goods['uuid'];

  return $guuid;
}



function _moysklad_check_auuid() {
  if(_moysklad_get_connector("Company", variable_get('default_auuid', '')) ){
    drupal_set_message( t('default user uuid set') );
  } else drupal_set_message(t('default user uuid Incorrect'), 'error');
}


function moysklad_uc_checkout_complete($order, $account) {
  // dpm($order);
  // dpm($account);
  // dpm(array_keys(get_defined_vars()));
  $comment = uc_order_comments_load($order->order_id);
  $description = "Оформлен заказ номер: " . $order->order_id . "\n";

  // немного хардкода
  if(function_exists('uc_extra_fields_pane_value_load') && function_exists('_delivery_type_description')){
    $dd = uc_extra_fields_pane_value_load($order->order_id, 12, 1);
    $description .= "Предпочтительный способ доставки: " . _delivery_type_description($dd->value) . "\n";
  }
  $description .= "Адрес доставки: " . $order->delivery_city . " " . $order->delivery_street1 . " " . $order->delivery_street2 . "\n";
  $description .= "Комментарий клиента к заказу: " . $comment[0]->message;

  // если нет возможности получить auuid из почты пользователя, например новый
  // анонимный пользователь, то присваиваем дефолтное значение и пытаемся создать
  // нового пользователя в моем складе
  $mail = $order->primary_email;
  if (!$auuid = _moysklad_email_to_auuid( $mail )) {

    $auuid = variable_get('default_auuid', '');

    $body = _moysklad_user_to_xml(array(
      "first_name"  => $order->delivery_first_name,
      "last_name" => $order->delivery_last_name,
      "company" => $order->delivery_company,
      "phone" => $order->delivery_phone,
      "email" => $order->primary_email,
      "street1" => $order->delivery_street1,
      "street2" => $order->delivery_street2,
      "city"  => $order->delivery_city
      ), variable_get('moysklad_login'));

    $respond = _moysklad_company_send($body) or die ("Мой склад не отвечает");

    $respondXML = simplexml_load_string($respond);

    // если пользователь создался используем его auuid
    // и обновляем кеш пользователей с моего склада
    if ($respondXML->uuid) {
      $auuid = $respondXML->uuid;
      moysklad_users_cache();
      drupal_set_message(t('New user created successfully.'));
      drupal_set_message(t('User cache was renewed.'));
    } else {
      drupal_set_message(t('Can`t create new user, use default user auuid instead'), 'error');
    }
  }

  $items = $order->products;
  $coupons = false;

  if (isset($order->data['coupons'])) {
    $coupons = $order->data['coupons'];
  }

  $body = _moysklad_user_order_to_xml( $auuid, $items, $coupons, $description );

  _moysklad_order_send( $body );
}
