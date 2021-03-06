<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2012 VINADES.,JSC. All rights reserved
 * @Createdate 2-9-2010 14:43
 */

if( ! defined( 'NV_IS_FILE_LANG' ) ) die( 'Stop!!!' );

$select_options = array();

$contents = '';

$xtpl = new XTemplate( 'edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file );
$xtpl->assign( 'LANG', $lang_module );
$xtpl->assign( 'GLANG', $lang_global );

if( $nv_Request->isset_request( 'idfile,savedata', 'post' ) and $nv_Request->get_string( 'savedata', 'post' ) == md5( session_id() ) )
{
	$numberfile = 0;

	$idfile = $nv_Request->get_int( 'idfile', 'post', 0 );
	$dirlang = $nv_Request->get_title( 'dirlang', 'post', '' );

	$lang_translator = $nv_Request->get_array( 'pozauthor', 'post', array() );
	$lang_translator_save = array();

	$langtype = isset( $lang_translator['langtype'] ) ? strip_tags( $lang_translator['langtype'] ) : "lang_module";

	$lang_translator_save['author'] = isset( $lang_translator['author'] ) ? nv_htmlspecialchars( strip_tags( $lang_translator['author'] ) ) : "VINADES.,JSC (contact@vinades.vn)";
	$lang_translator_save['createdate'] = isset( $lang_translator['createdate'] ) ? nv_htmlspecialchars( strip_tags( $lang_translator['createdate'] ) ) : date( "d/m/Y, H:i" );
	$lang_translator_save['copyright'] = isset( $lang_translator['copyright'] ) ? nv_htmlspecialchars( strip_tags( $lang_translator['copyright'] ) ) : "@Copyright (C) 2012 VINADES.,JSC. All rights reserved";
	$lang_translator_save['info'] = isset( $lang_translator['info'] ) ? nv_htmlspecialchars( strip_tags( $lang_translator['info'] ) ) : "";
	$lang_translator_save['langtype'] = $langtype;

	$author = var_export( $lang_translator_save, true );

	$db->sql_query( "UPDATE `" . NV_LANGUAGE_GLOBALTABLE . "_file` SET `author_" . $dirlang . "`='" . $author . "' WHERE `idfile`=" . $idfile . "" );

	list( $module ) = $db->sql_fetchrow( $db->sql_query( "SELECT `module` FROM `" . NV_LANGUAGE_GLOBALTABLE . "_file` WHERE `idfile` ='" . $idfile . "'" ) );

	nv_insert_logs( NV_LANG_DATA, $module_name, $lang_module['nv_admin_edit'] . ' -> ' . $language_array[$dirlang]['name'], $module . " : idfile = " . $idfile, $admin_info['userid'] );

	$pozlang = $nv_Request->get_array( 'pozlang', 'post', array() );

	if( ! empty( $pozlang ) )
	{
		foreach( $pozlang as $id => $lang_value )
		{
			$id = intval( $id );
			$lang_value = trim( strip_tags( $lang_value, NV_ALLOWED_HTML_LANG ) );
			$db->sql_query( "UPDATE `" . NV_LANGUAGE_GLOBALTABLE . "` SET `lang_" . $dirlang . "`='" . mysql_real_escape_string( $lang_value ) . "' WHERE `id`='" . $id . "'" );
		}
	}

	$pozlangkey = $nv_Request->get_array( 'pozlangkey', 'post', array() );
	$pozlangval = $nv_Request->get_array( 'pozlangval', 'post', array() );

	$sizeof = sizeof( $pozlangkey );
	for( $i = 1; $i <= $sizeof; ++$i )
	{
		$lang_key = strip_tags( $pozlangkey[$i] );
		$lang_value = strip_tags( $pozlangval[$i], NV_ALLOWED_HTML_LANG );

		if( $lang_key != '' and $lang_value != '' )
		{
			$lang_value = nv_nl2br( $lang_value );
			$lang_value = str_replace( '<br />', '<br />', $lang_value );
			$sql = "INSERT INTO `" . NV_LANGUAGE_GLOBALTABLE . "` (`id`, `idfile`, `lang_key`, `lang_" . $dirlang . "`) VALUES (NULL, '" . $idfile . "', '" . mysql_real_escape_string( $lang_key ) . "', '" . mysql_real_escape_string( $lang_value ) . "')";
			$db->sql_query( $sql );
		}
	}

	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=interface&dirlang=' . $dirlang . '' );
	die();
}

$dirlang = $nv_Request->get_title( 'dirlang', 'get', '' );
$page_title = $lang_module['nv_admin_edit'] . ': ' . $language_array[$dirlang]['name'];

if( $nv_Request->isset_request( 'idfile,checksess', 'get' ) and $nv_Request->get_string( 'checksess', 'get' ) == md5( $nv_Request->get_int( 'idfile', 'get' ) . session_id() ) )
{
	$idfile = $nv_Request->get_int( 'idfile', 'get' );

	list( $idfile, $module, $admin_file, $langtype, $author_lang ) = $db->sql_fetchrow( $db->sql_query( "SELECT `idfile`, `module`, `admin_file`, `langtype`, `author_" . $dirlang . "` FROM `" . NV_LANGUAGE_GLOBALTABLE . "_file` WHERE `idfile` ='" . $idfile . "'" ) );

	if( ! empty( $dirlang ) and ! empty( $module ) )
	{
		if( empty( $author_lang ) )
		{
			$array_translator = array();
			$array_translator['author'] = '';
			$array_translator['createdate'] = '';
			$array_translator['copyright'] = '';
			$array_translator['info'] = '';
			$array_translator['langtype'] = '';
		}
		else
		{
			eval( '$array_translator = ' . $author_lang . ';' );
		}

		$xtpl->assign( 'ALLOWED_HTML_LANG', ALLOWED_HTML_LANG );
		$xtpl->assign( 'NV_NAME_VARIABLE', NV_NAME_VARIABLE );
		$xtpl->assign( 'NV_OP_VARIABLE', NV_OP_VARIABLE );
		$xtpl->assign( 'NV_BASE_ADMINURL', NV_BASE_ADMINURL );

		$xtpl->assign( 'MODULE_NAME', $module_name );
		$xtpl->assign( 'OP', $op );
		$xtpl->assign( 'LANGTYPE', $array_translator['langtype'] );

		$i = 1;
		foreach( $array_translator as $lang_key => $lang_value )
		{
			if( $lang_key != "langtype" )
			{
				$xtpl->assign( 'ARRAY_TRANSLATOR', array(
					'lang_key' => $lang_key,
					'value' => nv_htmlspecialchars( $lang_value )
				) );

				$xtpl->parse( 'main.array_translator' );
			}
		}

		for( $a = 1; $a <= 2; ++$a )
		{
			$xtpl->assign( 'ARRAY_BODY', $a );

			$xtpl->parse( 'main.array_body' );
		}

		$sql = "SELECT `id`, `lang_key`, `lang_" . $dirlang . "` FROM `" . NV_LANGUAGE_GLOBALTABLE . "` WHERE `idfile`='" . $idfile . "' ORDER BY `id` ASC";
		$result = $db->sql_query( $sql );

		while( list( $id, $lang_key, $lang_value ) = $db->sql_fetchrow( $result ) )
		{
			$xtpl->assign( 'ARRAY_DATA', array(
				'key' => $a++,
				'lang_key' => $lang_key,
				'value' => nv_htmlspecialchars( $lang_value ),
				'id' => $id
			) );

			$xtpl->parse( 'main.array_data' );
		}

		$xtpl->assign( 'IDFILE', $idfile );
		$xtpl->assign( 'DIRLANG', $dirlang );
		$xtpl->assign( 'SAVEDATA', md5( session_id() ) );

		$xtpl->parse( 'main' );
		$contents .= $xtpl->text( 'main' );
	}
}

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme( $contents );
include NV_ROOTDIR . '/includes/footer.php';

?>