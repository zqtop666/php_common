<?php
function zq_er()
{
    print_r(error_get_last());
}

function zq_error()
{
    ini_set('display_errors', 1);            //错误信息
    ini_set('display_startup_errors', 1);    //php启动错误信息
    error_reporting(-1);
}

/**
 * @param $isparentWindow string是否父窗口true或false
 * @param $message string内容
 * @param $ext string扩展JS
 * @param $reloadintval string重载页面毫秒之后
 */
function showError($isparentWindow, $message = '', $ext = '', $reloadintval = '')
{
    echo '<script type="text/javascript">';
    if ($isparentWindow) {
        echo "parent.showError('$message');";
    } else {
        echo "showError('$message');";
    }
    echo "$ext;";
    if ($reloadintval && $isparentWindow) {
        echo "setTimeout(function(){parent.location.reload();},$reloadintval);";
    }
    if ($reloadintval && !$isparentWindow) {
        echo "setTimeout(function(){location.reload();},$reloadintval);";
    }
    echo '</script>';
    exit();
}

/**
 * @param $isparentWindow string是否父窗口true或false
 * @param $msg string内容
 * @param $mode string提升模式，right，notice，error，这几个mod如果没有被定义默认使用alert，也就是错误提示，显示一个X再加一个确定按钮
 * @param $title string标题
 * @param $func string回调函数，没有传null
 * @param $cover string是否遮罩true或false
 * @param $ext string扩展JS
 */
function showDialog($isparentWindow, $msg, $mode, $title = '', $func = 'null', $cover = 'true', $ext = '')
{
    echo '<script type="text/javascript">';
    $mode = $mode ? $mode : "right";
    if ($isparentWindow) {
        echo "parent.showDialog('$msg','$mode','$title',$func,$cover);";
    } else {
        echo "showDialog('$msg','$mode','$title',$func,$cover);";
    }
    echo "$ext;";
    echo '</script>';
    exit();
}

// 定义当前请求的系统常量
define('NOW_TIME', $_SERVER['REQUEST_TIME']);
define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST['ajax']) || !empty($_GET['ajax'])) ? true : false);
define('IS_PJAX', array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX']);
define('HTTP_HOST', $_SERVER['HTTP_HOST']);
