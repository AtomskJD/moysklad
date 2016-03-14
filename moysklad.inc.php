<?php


function _is_disabled($varName) {
  if ( !variable_get($varName, FALSE) ){
    return TRUE;
  } else FALSE;
}


/**
  TODO: перенести в отдельный модуль и сделать проверку при вызове
*/
function _delivery_type_description ( $delivery_id ) {
  switch ( $delivery_id ) {
    case 'delivery_1':
      $result = 'самовывоз';
      break;
    case 'delivery_2':
      $result = 'доставка по городу';
      break;
    case 'delivery_3':
      $result = 'доставка до ТК';
      break;
    default:
      $result = 'Доставка до ТК';
      break;
  }

  return $result;
}


function _moysklad_test_button( $form, &$form_state ) {

  dpm(_moysklad_get_list('Warehouse', 'name'));
  dpm(_moysklad_get_list('MyCompany', 'director'));
}


function _moysklad_get_connector( $type, $id = 'list', $offset = 0 ) {
  $url = "https://online.moysklad.ru/exchange/rest/ms/xml/".$type."/".$id."?start=".$offset;
  $auth = base64_encode(variable_get('moysklad_login').':'.variable_get('moysklad_pass'));
  $header = array("Authorization: Basic $auth");
  $opts = array( 'http' => array ('method'=>'GET', 'header'=>$header));
  $ctx = stream_context_create($opts);

  $result = file_get_contents($url,false,$ctx);

  return $result;
}


function _moysklad_get_list( $type, $attrName ) {
  $content = simplexml_load_string( _moysklad_get_connector( $type ) );

  $result = array();

  foreach ($content->children() as $key => $value) {
      $result[(string)$value->uuid] = (string)$value->attributes()->$attrName;
  }

  return $result;
}

function _moysklad_get_list2( $type, $nodeName ) {
  $result = array();
  $offset = 0;

  do {
    $content = simplexml_load_string( _moysklad_get_connector( $type, "list", $offset) );
    // dpm((string)$content->attributes()->total);

    foreach ($content->children() as $key => $value) {
      if ($value->$nodeName != '' ) {
        $result[(string)$value->uuid] = (string)$value->$nodeName;
      }
    }

    $offset += 1000;
    // dpm ($offset);

} while ( (int)$content->attributes()->total > (int)$content->attributes()->start );

  return $result;
}



/**
 * connector exception test
 * @param  [type] $type [description]
 * @param  string $id   [description]
 * @return [type]       [description]
 */
function _moysklad_get_connector_test( $type, $id = 'list') {
  $url = "https://online.moysklad.ru/exchange/rest/ms/xml/".$type."/".$id;
  $auth = base64_encode(variable_get('moysklad_login').':'.variable_get('moysklad_pass'));
  $header = array("Authorization: Basic $auth");
  $opts = array( 'http' => array ('method'=>'GET', 'header'=>$header));
  $ctx = stream_context_create($opts);

  $result = file_get_contents($url,false,$ctx);

  return $result;
}
