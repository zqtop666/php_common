<?php

/*
 * 部署方法：
 * 1、在模板编译方法中埋zq_page($from)  (记得判断方法exists)，
 * 2、在模板引擎方法中加入：(defined('ZQ_PAGE') && ZQ_PAGE) 为真时强行调用模板编译方法。
 */

define('ZQ_PAGE', 0);
define('ZQ_DBG', 0);

if (!function_exists("zq_start")) {
    function zq_start($name)
    {
        xdebug_start_trace(IA_ROOT . "/$name");
    }
}

if (!function_exists("zq_stop")) {
    function zq_stop()
    {
        xdebug_stop_trace();
    }
}

if (!function_exists("zq_return")) {
    function zq_return($res)
    {
        return $res;
    }
}

if (!function_exists("zq_sqlerror")) {
    function zq_sqlerror($errno, $errstr, $errfile, $errline)
    {
        if (substr($errstr, 0, 3) === "SQL") {
            echo $errstr;
            die;
        }
    }
}

if (!function_exists("zq_var")) {
    function zq_var($source)
    {
        echo("<zqv style='display:none;'>" . var_export($source, true) . "</zqv>\n");
    }
}

if (!function_exists("zq_page")) {
    function zq_page($source)
    {
        if (defined('ZQ_DBG') && ZQ_DBG) {
            $str = "<zqs style='display:none;'>" . $source . "</zqs>";
            $str .= "<zqs style='display:none;'>" . var_export(get_included_files(), 'true') . "</zqs>";
            //$str = str_replace("\n", "\\", $str);
            $jquery = "<script src='http://code.jquery.com/jquery-1.11.1.min.js'></script><script src='http://libs.baidu.com/jquery/1.11.1/jquery.min.js'></script>";
            $script = "<script>jQuery(function(){ jQuery('body').prepend(jQuery(\"{$str}\")); });</script>";
            return $str;
        } else {
            return "";
        }
    }
}

if (!function_exists("zq_alert")) {
    function zq_alert($a)
    {
        echo "<script>alert('{$a}');</script>";
    }
}

if (!function_exists("zq_console")) {
    function zq_console($a)
    {
        echo "<script>console.info('{$a}');</script>";
    }
}
