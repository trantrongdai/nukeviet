<?php

/**
 * @Project NUKEVIET 3.0
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2010 VINADES.,JSC. All rights reserved
 * @Createdate 2-9-2010 14:43
 */

if ( ! defined( 'NV_IS_FILE_ADMIN' ) ) die( 'Stop!!!' );

$page_title = $lang_module['setup_payment'];

/*load config template payment port in data*/
$array_setting_payment = array();
$sql = "SELECT * FROM `" . $db_config['prefix'] . "_" . $module_data . "_payment` ORDER BY `weight` ASC";
$result = $db->sql_query( $sql );
$all_page = $db->sql_numrows( $result );
while ( $row = $db->sql_fetchrow( $result ) )
{
    $array_setting_payment[$row['payment']] = $row;
}

/*load config template payment port in file*/
$check_config_payment = "/^([a-zA-Z0-9\-\_]+)\.config\.ini$/";
$payment_funcs = nv_scandir( NV_ROOTDIR . '/modules/' . $module_file . '/payment', $check_config_payment );
if ( ! empty( $payment_funcs ) )
{
    $payment_funcs = preg_replace( $check_config_payment, "\\1", $payment_funcs );
}
$array_setting_payment_key = array_keys( $array_setting_payment );
$array_payment_other = array();
foreach ( $payment_funcs as $payment )
{
    $xml = simplexml_load_file( NV_ROOTDIR . '/modules/' . $module_file . '/payment/' . $payment . '.config.ini' );
    if ( $xml !== false )
    {
        $xmlconfig = $xml->xpath( 'config' );
        $config = $xmlconfig[0];
        $array_config = array();
        $array_config_title = array();
        foreach ( $config as $key => $value )
        {
            $config_lang = $value->attributes();
            if ( isset( $config_lang[NV_LANG_INTERFACE] ) )
            {
                $lang = ( string )$config_lang[NV_LANG_INTERFACE];
            }
            else
            {
                $lang = $key;
            }
            $array_config[$key] = trim( $value );
            $array_config_title[$key] = $lang;
        }
        $array_payment_other[$payment] = array( 
            'payment' => $payment, 'paymentname' => trim( $xml->name ), 'domain' => trim( $xml->domain ), 'images_button' => trim( $xml->images_button ), 'config' => $array_config, 'titlekey' => $array_config_title 
        );
        unset( $config, $xmlconfig, $xml );
    }
}
/*end load*/

/*get data edit*/
$data_pay = array();
$payment = $nv_Request->get_string( 'payment', 'get', '' );
if ( ! empty( $payment ) )
{
	//get data have not in database
    if ( ! in_array( $payment, $array_setting_payment_key ) )
    {
        if ( ! empty( $array_payment_other[$payment] ) )
        {
            list( $weight ) = $db->sql_fetchrow( $db->sql_query( "SELECT max(`weight`) FROM `" . $db_config['prefix'] . "_" . $module_data . "_payment`" ) );
            $weight = intval( $weight ) + 1;
            $sql = "REPLACE INTO `" . $db_config['prefix'] . "_" . $module_data . "_payment` (`payment`, `paymentname`, `domain`, `active`, `weight`, `config`,`images_button`) VALUES (" . $db->dbescape_string( $payment ) . ", " . $db->dbescape_string( $array_payment_other[$payment]['paymentname'] ) . ", " . $db->dbescape_string( $array_payment_other[$payment]['domain'] ) . ", '0', '" . $weight . "', '" . nv_base64_encode( serialize( $array_payment_other[$payment]['config'] ) ) . "', " . $db->dbescape_string( $array_payment_other[$payment]['images_button'] ) . ")";
            $db->sql_query( $sql );
            $data_pay = $array_payment_other[$payment];
        }
    }
    //get data have in database
    $sql = "SELECT * FROM `" . $db_config['prefix'] . "_" . $module_data . "_payment` WHERE payment=" . $db->dbescape( $payment );
    $result = $db->sql_query( $sql );
    $data_pay = $db->sql_fetchrow( $result );
}

/*post setting data*/
if ( $nv_Request->isset_request( 'saveconfigpaymentedit', 'post' ) )
{
    $payment = filter_text_input( 'payment', 'post', '', 0 );
    $paymentname = filter_text_input( 'paymentname', 'post', '', 0 );
    $domain = filter_text_input( 'domain', 'post', '', 0 );
    $images_button = filter_text_input( 'images_button', 'post', '', 0 );
    $active = $nv_Request->get_int( 'active', 'post', 0 );
    $array_config = $nv_Request->get_array( 'config', 'post', array() );
    
    if ( ! nv_is_url( $images_button ) and file_exists( NV_DOCUMENT_ROOT . $images_button ) )
    {
        $lu = strlen( NV_BASE_SITEURL . NV_UPLOADS_DIR . "/" . $module_name . "/" );
        $images_button = substr( $images_button, $lu );
    }
    elseif ( ! nv_is_url( $images_button ) )
    {
        $images_button = "";
    }
    
    $sql = "UPDATE `" . $db_config['prefix'] . "_" . $module_data . "_payment` SET `paymentname` = " . $db->dbescape_string( $paymentname ) . ", `domain` = " . $db->dbescape_string( $domain ) . ", `active`=" . $active . ", `config` = '" . nv_base64_encode( serialize( $array_config ) ) . "',`images_button`=" . $db->dbescape_string( $images_button ) . " WHERE `payment` = " . $db->dbescape_string( $payment ) . " LIMIT 1";
    $db->sql_query( $sql );
    nv_insert_logs( NV_LANG_DATA, $module_name, 'log_edit_product', "edit " . $paymentname, $admin_info['userid'] );
    nv_del_moduleCache( $module_name );
    Header( "Location: " . NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op );
}

/*view data*/
$xtpl = new XTemplate( "payport.tpl", NV_ROOTDIR . "/themes/" . $global_config['module_theme'] . "/modules/" . $module_file );
$xtpl->assign( 'LANG', $lang_module );
$a = 0;
if ( ! empty( $array_setting_payment ) && empty( $data_pay ))
{
    foreach ( $array_setting_payment as $value )
    {
        $value['link_edit'] = NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op . "&amp;payment=" . $value['payment'];
        $value['class'] = ( $a % 2 == 0 ) ? ' class="second"' : '';
        $value['active'] = ( $value['active'] == "1" ) ? "checked=\"checked\"" : "";
        $value['slect_weight'] = drawselect_number( $value['payment'], 1, $all_page + 1, $value['weight'], "nv_chang_pays('" . $value['payment'] . "',this,url_change_weight,url_back);" );
        $xtpl->assign( 'DATA_PM', $value );
        $xtpl->parse( 'main.listpay.paymentloop' );
        ++$a;
    }
    $xtpl->assign( 'url_back', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op );
    $xtpl->assign( 'url_change', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=changepay" );
    $xtpl->assign( 'url_active', NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=actpay" );
    $xtpl->parse( 'main.listpay' );
}

if ( ! empty( $array_payment_other ) && empty( $data_pay ))
{
    $a = 1;
    foreach ( $array_payment_other as $pay => $value )
    {
        if ( ! in_array( $pay, $array_setting_payment_key ) )
        {
            $value['link_edit'] = NV_BASE_ADMINURL . "index.php?" . NV_NAME_VARIABLE . "=" . $module_name . "&" . NV_OP_VARIABLE . "=" . $op . "&amp;payment=" . $value['payment'];
            $value['class'] = ( $a % 2 == 0 ) ? ' class="second"' : '';
            $value['STT'] = $a;
            $xtpl->assign( 'ODATA_PM', $value );
            $xtpl->parse( 'main.olistpay.opaymentloop' );
            ++$a;
        }
    }
    if ($a>1) $xtpl->parse( 'main.olistpay' );
}
if ( ! empty( $data_pay ) )
{
	$xtpl->assign( 'EDITPAYMENT', sprintf( $lang_module['editpayment'], $data_pay['payment'] ) );
    $array_config = unserialize( nv_base64_decode( $data_pay['config'] ) );
    
    $arkey_title = array();
    if ( ! empty( $array_payment_other[$data_pay['payment']]['titlekey'] ) )
    {
        $arkey_title = $array_payment_other[$data_pay['payment']]['titlekey'];
    }
    foreach ( $array_config as $key => $value )
    {
        if ( isset( $arkey_title[$key] ) )
        {
            $lang = ( string )$arkey_title[$key];
        }
        else
        {
            $lang = $key;
        }
        $value = $array_config[$key];
        $xtpl->assign( 'CONFIG_LANG', $lang );
        $xtpl->assign( 'CONFIG_NAME', $key );
        $xtpl->assign( 'CONFIG_VALUE', $value );
        $xtpl->parse( 'main.paymentedit.config' );
    }
    $data_pay['active'] = ( $data_pay['active'] == "1" ) ? "checked=\"checked\"" : "";
    $xtpl->assign( 'DATA', $data_pay );
    $xtpl->parse( 'main.paymentedit' );
}

$xtpl->parse( 'main' );
$contents = $xtpl->text( 'main' );
include ( NV_ROOTDIR . "/includes/header.php" );
echo nv_admin_theme( $contents );
include ( NV_ROOTDIR . "/includes/footer.php" );
?>