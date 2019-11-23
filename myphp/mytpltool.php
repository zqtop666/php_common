<?php
/*
* 部署方法：
* 1、在模板编译方法中埋 $content.=zq_page($from)  (记得判断方法exists)，
* 2、在模板引擎方法中加入：(defined('ZQ_PAGE') && ZQ_PAGE) 为真时强行调用模板编译方法。
*/
define('ZQ_PAGE', 0);

if (!function_exists("zq_var")) {
    function zq_var($source)
    {
        echo("<zqv style='display:none;'>" . var_export($source, true) . "</zqv>\n");
    }
}

if (!function_exists("zq_page")) {
    function zq_page($source)
    {
        if (defined('ZQ_PAGE') && ZQ_PAGE) {
            $str = "<zqs style='display:none;position:absolute;height:0;overflow:hidden;'>" . $source . "</zqs>";
            $str .= "<zqs style='display:none;position:absolute;height:0;overflow:hidden;'>" . var_export(get_included_files(), 'true') . "</zqs>";
            $str = str_replace("\n", "\\", $str);
            $jquery = "<script src='http://code.jquery.com/jquery-1.11.1.min.js'></script><script src='http://libs.baidu.com/jquery/1.11.1/jquery.min.js'></script>";
            $script = "<script>$(function(){ $('body').prepend($(\"{$str}\")); });</script>";
            return $jquery . $script;
        }
    }
}
