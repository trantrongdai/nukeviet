<?php

/**
 * @Project NUKEVIET 3.0
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2010 VINADES.,JSC. All rights reserved
 * @Createdate 3-6-2010 0:14
 */

if (!defined('NV_IS_MOD_SHOPS'))
    die('Stop!!!');

if (empty($catid))
{
    $redirect = "<meta http-equiv=\"Refresh\" content=\"3;URL=" . nv_url_rewrite(NV_BASE_SITEURL . "index.php?" . NV_LANG_VARIABLE . "=" . NV_LANG_DATA . "&amp;" . NV_NAME_VARIABLE . "=" . $module_name, true) . "\" />";
    nv_info_die($lang_global['error_404_title'], $lang_global['error_404_title'], $lang_global['error_404_content'] . $redirect);
}

$page_title = $global_array_cat[$catid]['title'];
$key_words = $global_array_cat[$catid]['keywords'];
$description = $global_array_cat[$catid]['description'];
$data_content = array();

$count = 0;
$link = NV_BASE_SITEURL . "index.php?" . NV_LANG_VARIABLE . "=" . NV_LANG_DATA . "&amp;" . NV_NAME_VARIABLE . "=" . $module_name . "&amp;" . NV_OP_VARIABLE . "=";
$base_url = NV_BASE_SITEURL . "index.php?" . NV_LANG_VARIABLE . "=" . NV_LANG_DATA . "&amp;" . NV_NAME_VARIABLE . "=" . $module_name . "&amp;" . NV_OP_VARIABLE . "=" . $global_array_cat[$catid]['alias'] . "";

if ($global_array_cat[$catid]['viewcat'] == "view_home_cat" and $global_array_cat[$catid]['numsubcat'] > 0)
{
    $data_content = array();
    $array_subcatid = explode(",", $global_array_cat[$catid]['subcatid']);
    foreach ($array_subcatid as $catid_i)
    {
        $array_info_i = $global_array_cat[$catid_i];

        $array_cat = array();
        $array_cat = GetCatidInParent($catid_i);
        $s = "SELECT SQL_CALC_FOUND_ROWS id, publtime, " . NV_LANG_DATA . "_title," . NV_LANG_DATA . "_alias, " . NV_LANG_DATA . "_hometext, " . NV_LANG_DATA . "_address, homeimgalt, homeimgthumb, product_price,product_discounts, money_unit,showprice  FROM `" . $db_config['prefix'] . "_" . $module_data . "_rows` WHERE listcatid IN (" . implode(",", $array_cat) . ") AND inhome=1 AND status=1 AND publtime < " . NV_CURRENTTIME . " AND (exptime=0 OR exptime>" . NV_CURRENTTIME . ") ORDER BY ID DESC LIMIT 0," . $array_info_i['numlinks'] . "";
        $re = $db->sql_query($s);

        $result = $db->sql_query("SELECT FOUND_ROWS()");
        list($num_pro) = $db->sql_fetchrow($result);

        $link = NV_BASE_SITEURL . "index.php?" . NV_LANG_VARIABLE . "=" . NV_LANG_DATA . "&amp;" . NV_NAME_VARIABLE . "=" . $module_name . "&amp;" . NV_OP_VARIABLE . "=";
        $data_pro = array();
        while (list($id, $publtime, $title, $alias, $hometext, $address, $homeimgalt, $homeimgthumb, $product_price, $product_discounts, $money_unit, $showprice) = $db->sql_fetchrow($re))
        {
            $thumb = explode("|", $homeimgthumb);
            if (!empty($thumb[0]) && !nv_is_url($thumb[0]))
            {
                $thumb[0] = NV_BASE_SITEURL . NV_UPLOADS_DIR . "/" . $module_name . "/" . $thumb[0];
            }
            else
            {
                $thumb[0] = NV_BASE_SITEURL . "themes/" . $module_info['template'] . "/images/" . $module_file . "/no-image.jpg";
            }
            $data_pro[] = array("id" => $id, "publtime" => $publtime, "title" => $title, "alias" => $alias, "hometext" => $hometext, "address" => $address, "homeimgalt" => $homeimgalt, "homeimgthumb" => $thumb[0], "product_price" => $product_price, "product_discounts" => $product_discounts, "money_unit" => $money_unit, "showprice" => $showprice, "link_pro" => $link . $global_array_cat[$catid_i]['alias'] . "/" . $alias . "-" . $id, "link_order" => $link . "setcart&amp;id=" . $id);
        }
        $data_content[] = array("catid" => $catid_i, "title" => $array_info_i['title'], "link" => $array_info_i['link'], 'data' => $data_pro, 'num_pro' => $num_pro, "num_link" => $array_info_i['numlinks']);
    }
    $contents = call_user_func('view_home_cat', $data_content);
}
else
{
    $sql = "SELECT SQL_CALC_FOUND_ROWS id,listcatid, publtime, " . NV_LANG_DATA . "_title, " . NV_LANG_DATA . "_alias, " . NV_LANG_DATA . "_hometext, " . NV_LANG_DATA . "_address, homeimgalt, homeimgthumb,product_price,product_discounts,money_unit,showprice  FROM `" . $db_config['prefix'] . "_" . $module_data . "_rows` WHERE ";
    if ($global_array_cat[$catid]['numsubcat'] == 0)
    {
        $sql .= " listcatid =" . $catid . "";
    }
    else
    {
        $array_cat = array();
        $array_cat = GetCatidInParent($catid);
        $sql .= " listcatid IN (" . implode(",", $array_cat) . ")";
    }
    $sql .= " AND status=1 AND publtime < " . NV_CURRENTTIME . " AND (exptime=0 OR exptime>" . NV_CURRENTTIME . ") ORDER BY ID DESC LIMIT " . ($page - 1) * $per_page . "," . $per_page;

    $result = $db->sql_query($sql);
    $result_page = $db->sql_query("SELECT FOUND_ROWS()");
    list($all_page) = $db->sql_fetchrow($result_page);

    $data_content = GetDataIn($result, $catid);
    $data_content['count'] = $all_page;
    $pages = nv_alias_page($page_title, $base_url, $all_page, $per_page, $page);
    $contents = call_user_func($global_array_cat[$catid]['viewcat'], $data_content, $pages);
    if ($page > 1)
    {
        $page_title .= ' ' . NV_TITLEBAR_DEFIS . ' ' . $lang_global['page'] . ' ' . $page;
    }
}

include (NV_ROOTDIR . "/includes/header.php");
echo nv_site_theme($contents);
include (NV_ROOTDIR . "/includes/footer.php");
?>