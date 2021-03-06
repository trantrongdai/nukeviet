<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2012 VINADES.,JSC. All rights reserved
 * @Createdate 3/12/2010, 13:16
 */

if( ! defined( 'NV_IS_FILE_SEOTOOLS' ) ) die( 'Stop!!!' );

if( $global_config['idsite'] )
{
	$file_metatags = NV_ROOTDIR . '/' . NV_DATADIR . '/site_' . $global_config['idsite'] . '_metatags.xml';
}
else
{
	$file_metatags = NV_ROOTDIR . '/' . NV_DATADIR . '/metatags.xml';
}

$metatags = array();
$metatags['meta'] = array();
$ignore = array( 'content-type', 'generator', 'description', 'keywords' );
$vas = array( '{CONTENT-LANGUAGE} (' . $lang_global['Content_Language'] . ')', '{LANGUAGE} (' . $lang_global['LanguageName'] . ')', '{SITE_NAME} (' . $global_config['site_name'] . ')', '{SITE_EMAIL} (' . $global_config['site_email'] . ')' );

if( $nv_Request->isset_request( 'submit', 'post' ) )
{
	$metaGroupsName = $nv_Request->get_array( 'metaGroupsName', 'post' );
	$metaGroupsValue = $nv_Request->get_array( 'metaGroupsValue', 'post' );
	$metaContents = $nv_Request->get_array( 'metaContents', 'post' );

	foreach( $metaGroupsName as $key => $name )
	{
		if( $name == 'http-equiv' or $name == 'name' or $name == 'property' )
		{
			$value = trim( strip_tags( $metaGroupsValue[$key] ) );
			$content = trim( strip_tags( $metaContents[$key] ) );
			$newArray = array(
				'group' => $name,
				'value' => $value,
				'content' => $content
			);
			if( preg_match( "/^[a-zA-Z0-9\-\_\.\:]+$/", $value ) and ! in_array( $value, $ignore ) and preg_match( "/^([^\'\"]+)$/", $content ) and ! in_array( $newArray, $metatags['meta'] ) )
			{
				$metatags['meta'][] = $newArray;
			}
		}
	}

	if( file_exists( $file_metatags ) ) nv_deletefile( $file_metatags );

	if( ! empty( $metatags['meta'] ) )
	{
		include NV_ROOTDIR . '/includes/class/array2xml.class.php' ;
		$array2XML = new Array2XML();
		$array2XML->saveXML( $metatags, 'metatags', $file_metatags, $global_config['site_charset'] );
	}
	$metaTagsOgp = (int)$nv_Request->get_bool('metaTagsOgp', 'post');
	$db->sql_query( "REPLACE INTO `" . NV_CONFIG_GLOBALTABLE . "` (`lang`, `module`, `config_name`, `config_value`) VALUES('sys', 'site', 'metaTagsOgp', '" . $metaTagsOgp . "')" );
	nv_delete_all_cache( false );
	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '&rand=' . nv_genpass() );
	exit();
}
else
{
	if( ! file_exists( $file_metatags ) )
	{
		$file_metatags = NV_ROOTDIR . '/' . NV_DATADIR . '/metatags.xml';
	}
	$mt = simplexml_load_file( $file_metatags );
	$mt = nv_object2array( $mt );
	if( $mt['meta_item'] )
	{
		if( isset( $mt['meta_item'][0] ) ) $metatags['meta'] = $mt['meta_item'];
		else $metatags['meta'][] = $mt['meta_item'];
	}
}

$page_title = $lang_module['metaTagsConfig'];

$xtpl = new XTemplate( 'metatags.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file );
$xtpl->assign( 'LANG', $lang_module );
$xtpl->assign( 'GLANG', $lang_global );
$xtpl->assign( 'NOTE', sprintf( $lang_module['metaTagsNote'], implode( ', ', $ignore ) ) );
$xtpl->assign( 'VARS', $lang_module['metaTagsVar'] . ': ' . implode( ', ', $vas ) );
$xtpl->assign( 'NV_BASE_ADMINURL', NV_BASE_ADMINURL );
$xtpl->assign( 'NV_NAME_VARIABLE', NV_NAME_VARIABLE );
$xtpl->assign( 'MODULE_NAME', $module_name );
$xtpl->assign( 'NV_OP_VARIABLE', NV_OP_VARIABLE );
$xtpl->assign( 'OP', $op );
if( ! empty( $metatags['meta'] ) )
{
	$number = 0;
	foreach( $metatags['meta'] as $value )
	{
		$value['h_selected'] = $value['group'] == 'http-equiv' ? ' selected="selected"' : '';
		$value['n_selected'] = $value['group'] == 'name' ? ' selected="selected"' : '';
		$value['p_selected'] = $value['group'] == 'property' ? ' selected="selected"' : '';
		$xtpl->assign( 'DATA', $value );
		$xtpl->parse( 'main.loop' );
	}
}

for( $i = 0; $i < 3; ++$i )
{
	$data = array(
		'content' => '',
		'value' => '',
		'h_selected' => '',
		'n_selected' => ''
	);
	$xtpl->assign( 'DATA', $data );
	$xtpl->parse( 'main.loop' );
}
$xtpl->assign( 'METATAGSOGPCHECKED', ( $global_config['metaTagsOgp'] ) ? ' checked="checked" ' : '' );
$xtpl->parse( 'main' );
$contents = $xtpl->text( 'main' );

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme( $contents );
include NV_ROOTDIR . '/includes/footer.php';

?>