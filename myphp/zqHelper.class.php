<?php

namespace myphp;
/**
 * 助手类
 * @author www.shouce.ren
 *
 */
class zqHelper
{
    /**
     * 当前微妙数
     * @return number
     */
    public static function common_microtime_float()
    {
        list ($usec, $sec) = explode(" ", microtime());
        return (( float )$usec + ( float )$sec);
    }

    /**
     * 遍历文件夹
     * @param string $dir
     * @param boolean $all true表示递归遍历
     * @return array
     */
    public static function IO_scanfDir($dir = '', $all = false, &$ret = array())
    {
        if (false !== ($handle = opendir($dir))) {
            while (false !== ($file = readdir($handle))) {
                if (!in_array($file, array('.', '..', '.git', '.gitignore', '.svn', '.htaccess', '.buildpath', '.project'))) {
                    $cur_path = $dir . '/' . $file;
                    if (is_dir($cur_path)) {
                        $ret['dirs'][] = $cur_path;
                        $all && self::scanfDir($cur_path, $all, $ret);
                    } else {
                        $ret ['files'] [] = $cur_path;
                    }
                }
            }
            closedir($handle);
        }
        return $ret;
    }

    /**
     * 判断字符串是utf-8 还是gb2312
     * @param unknown $str
     * @param string $default
     * @return string
     */
    public static function str_utf8_gb2312($str, $default = 'gb2312')
    {
        $str = preg_replace("/[\x01-\x7F]+/", "", $str);
        if (empty($str)) {
            return $default;
        }
        $preg = array(
            "gb2312" => "/^([\xA1-\xF7][\xA0-\xFE])+$/", //正则判断是否是gb2312
            "utf-8" => "/^[\x{4E00}-\x{9FA5}]+$/u",      //正则判断是否是汉字(utf8编码的条件了)，这个范围实际上已经包含了繁体中文字了
        );
        if ($default == 'gb2312') {
            $option = 'utf-8';
        } else {
            $option = 'gb2312';
        }
        if (!preg_match($preg[$default], $str)) {
            return $option;
        }
        $str = @iconv($default, $option, $str);
        //不能转成 $option, 说明原来的不是 $default
        if (empty($str)) {
            return $option;
        }
        return $default;
    }

    /**
     * utf-8和gb2312自动转化
     * @param unknown $string
     * @param string $outEncoding
     * @return unknown|string
     */
    public static function str_safeEncoding($string, $outEncoding = 'UTF-8')
    {
        $encoding = "UTF-8";
        for ($i = 0; $i < strlen($string); $i++) {
            if (ord($string{$i}) < 128) {
                continue;
            }
            if ((ord($string{$i}) & 224) == 224) {
                // 第一个字节判断通过
                $char = $string{++$i};
                if ((ord($char) & 128) == 128) {
                    // 第二个字节判断通过
                    $char = $string{++$i};
                    if ((ord($char) & 128) == 128) {
                        $encoding = "UTF-8";
                        break;
                    }
                }
            }
            if ((ord($string{$i}) & 192) == 192) {
                // 第一个字节判断通过
                $char = $string{++$i};
                if ((ord($char) & 128) == 128) {
                    // 第二个字节判断通过
                    $encoding = "GB2312";
                    break;
                }
            }
        }
        if (strtoupper($encoding) == strtoupper($outEncoding)) {
            return $string;
        } else {
            return @iconv($encoding, $outEncoding, $string);
        }
    }

    /**
     * 格式化单位
     */
    public static function common_byteFormat($size, $dec = 2)
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, $dec) . " " . $a[$pos];
    }

    /**
     * 下载远程图片
     * @param string $url 图片的绝对url
     * @param string $filepath 文件的完整路径（例如/www/images/test） ，此函数会自动根据图片url和http头信息确定图片的后缀名
     * @param string $filename 要保存的文件名(不含扩展名)
     * @return mixed 下载成功返回一个描述图片信息的数组，下载失败则返回false
     */
    public static function net_downloadImage($url, $filepath, $filename)
    {
        //服务器返回的头信息
        $responseHeaders = array();
        //原始图片名
        $originalfilename = '';
        //图片的后缀名
        $ext = '';
        $ch = curl_init($url);
        //设置curl_exec返回的值包含Http头
        curl_setopt($ch, CURLOPT_HEADER, 1);
        //设置curl_exec返回的值包含Http内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //设置抓取跳转（http 301，302）后的页面
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //设置最多的HTTP重定向的数量
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        //服务器返回的数据（包括http头信息和内容）
        $html = curl_exec($ch);
        //获取此次抓取的相关信息
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        if ($html !== false) {
            //分离response的header和body，由于服务器可能使用了302跳转，所以此处需要将字符串分离为 2+跳转次数 个子串
            $httpArr = explode("\r\n\r\n", $html, 2 + $httpinfo['redirect_count']);
            //倒数第二段是服务器最后一次response的http头
            $header = $httpArr[count($httpArr) - 2];
            //倒数第一段是服务器最后一次response的内容
            $body = $httpArr[count($httpArr) - 1];
            $header .= "\r\n";
            //获取最后一次response的header信息
            preg_match_all('/([a-z0-9-_]+):\s*([^\r\n]+)\r\n/i', $header, $matches);
            if (!empty($matches) && count($matches) == 3 && !empty($matches[1]) && !empty($matches[1])) {
                for ($i = 0; $i < count($matches[1]); $i++) {
                    if (array_key_exists($i, $matches[2])) {
                        $responseHeaders[$matches[1][$i]] = $matches[2][$i];
                    }
                }
            }
            //获取图片后缀名
            if (0 < preg_match('{(?:[^\/\\\\]+)\.(jpg|jpeg|gif|png|bmp)$}i', $url, $matches)) {
                $originalfilename = $matches[0];
                $ext = $matches[1];
            } else {
                if (array_key_exists('Content-Type', $responseHeaders)) {
                    if (0 < preg_match('{image/(\w+)}i', $responseHeaders['Content-Type'], $extmatches)) {
                        $ext = $extmatches[1];
                    }
                }
            }
            //保存文件
            if (!empty($ext)) {
                //如果目录不存在，则先要创建目录
                if (!is_dir($filepath)) {
                    mkdir($filepath, 0777, true);
                }
                $filepath .= '/' . $filename . ".$ext";
                $local_file = fopen($filepath, 'w');
                if (false !== $local_file) {
                    if (false !== fwrite($local_file, $body)) {
                        fclose($local_file);
                        $sizeinfo = getimagesize($filepath);
                        return array('filepath' => realpath($filepath), 'width' => $sizeinfo[0], 'height' => $sizeinfo[1], 'orginalfilename' => $originalfilename, 'filename' => pathinfo($filepath, PATHINFO_BASENAME));
                    }
                }
            }
        }
        return false;
    }


    /**
     * 取得输入目录所包含的所有目录和文件
     * 以关联数组形式返回
     * author: flynetcn
     */
    public static function IO_deepScanDir($dir)
    {
        $fileArr = array();
        $dirArr = array();
        $dir = rtrim($dir, '//');
        if (is_dir($dir)) {
            $dirHandle = opendir($dir);
            while (false !== ($fileName = readdir($dirHandle))) {
                $subFile = $dir . DIRECTORY_SEPARATOR . $fileName;
                if (is_file($subFile)) {
                    $fileArr[] = $subFile;
                } elseif (is_dir($subFile) && str_replace('.', '', $fileName) != '') {
                    $dirArr[] = $subFile;
                    $arr = self::deepScanDir($subFile);
                    $dirArr = array_merge($dirArr, $arr['dir']);
                    $fileArr = array_merge($fileArr, $arr['file']);
                }
            }
            closedir($dirHandle);
        }
        return array('dir' => $dirArr, 'file' => $fileArr);
    }

    /**
     * 取得输入目录所包含的所有文件
     * 以数组形式返回
     * author: flynetcn
     */
    public static function IO_get_dir_files($dir)
    {
        if (is_file($dir)) {
            return array($dir);
        }
        $files = array();
        if (is_dir($dir) && ($dir_p = opendir($dir))) {
            $ds = DIRECTORY_SEPARATOR;
            while (($filename = readdir($dir_p)) !== false) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                $filetype = filetype($dir . $ds . $filename);
                if ($filetype == 'dir') {
                    $files = array_merge($files, self::get_dir_files($dir . $ds . $filename));
                } elseif ($filetype == 'file') {
                    $files[] = $dir . $ds . $filename;
                }
            }
            closedir($dir_p);
        }
        return $files;
    }

    /**
     * 删除文件夹及其文件夹下所有文件
     */
    public static function IO_deldir($dir)
    {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    self::deldir($fullpath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹：
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 清理session
     */
    public static function common_unSession()
    {
        if (session_start()) {
            session_destroy();
        }
    }

    /**
     * 获得真实IP地址
     * @return string
     */
    public static function common_realIp()
    {
        static $realip = null;
        if ($realip !== null) {
            return $realip;
        }
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realip = $ip;
                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $realip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $realip = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }
        preg_match('/[\d\.]{7,15}/', $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
        return $realip;
    }
    function date_dgmdate($timestamp, $format = 'u', $timeoffset = '9999', $uformat = 'Y-n-j')
    {
        $TIMESTAMP = time();
        $TIMESTAMP = time();
        $range = 365;//人性化时间范围，365天
        $format == 'u' && false && $format = 'dt'; //dt是标准时间,u是人性化时间
        static $dformat, $tformat, $dtformat, $offset, $lang;
        if ($dformat === null) {
            $dformat = 'Y-n-j';
            $tformat = 'H:i';
            $dtformat = $dformat . ' ' . $tformat;
            $offset = '8'; //8 //timeoffset
            $sysoffset = '8'; // timeoffset
            $offset = $offset == 9999 ? ($sysoffset ? $sysoffset : 0) : $offset; //8
            $lang = array(
                'before' => '前',
                'day' => '天',
                'yday' => '昨天',
                'byday' => '前天',
                'hour' => '小时',
                'half' => '半',
                'min' => '分钟',
                'sec' => '秒',
                'now' => '刚刚',
            );

        }
        $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
        $timestamp += $timeoffset * 3600;
        $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
        if ($format == 'u') {
            $todaytimestamp = $TIMESTAMP - ($TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
            $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
            $time = $TIMESTAMP + $timeoffset * 3600 - $timestamp;
            if ($timestamp >= $todaytimestamp) {
                if ($time > 3600) {
                    $return = intval($time / 3600) . '&nbsp;' . $lang['hour'] . $lang['before'];
                } elseif ($time > 1800) {
                    $return = $lang['half'] . $lang['hour'] . $lang['before'];
                } elseif ($time > 60) {
                    $return = intval($time / 60) . '&nbsp;' . $lang['min'] . $lang['before'];
                } elseif ($time > 0) {
                    $return = $time . '&nbsp;' . $lang['sec'] . $lang['before'];
                } elseif ($time == 0) {
                    $return = $lang['now'];
                } else {
                    $return = $s;
                }
                if ($time >= 0) {
                    $return = '<text title="' . $s . '">' . $return . '</text>';
                }
            } elseif (($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < $range) {
                if ($days == 0) {
                    $return = $lang['yday'] . '&nbsp;' . gmdate($tformat, $timestamp);
                } elseif ($days == 1) {
                    $return = $lang['byday'] . '&nbsp;' . gmdate($tformat, $timestamp);
                } else {
                    $return = ($days + 1) . '&nbsp;' . $lang['day'] . $lang['before'];
                }
                if (true) {
                    $return = '<text title="' . $s . '">' . $return . '</text>';
                }
            } else {
                $return = $s;
            }
            return $return;
        } else {
            return gmdate($format, $timestamp);
        }
    }

    /**
     * 创建目录
     * @param string $dir
     */
    public static function IO_createDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777);
        }
    }

    /**
     * 创建文件（默认为空）
     * @param unknown_type $filename
     */
    public static function IO_createFile($filename)
    {
        if (!is_file($filename)) {
            touch($filename);
        }
    }

    /**
     * 删除文件
     * @param string $filename
     */
    public static function IO_delFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * 删除目录
     * @param string $path
     */
    public static function IO_delDirOne($path)
    {
        if (is_dir($path)) {
            rmdir($path);
        }
    }

    /**
     * 删除目录及地下的全部文件
     * @param string $dir
     * @return bool
     */
    public static function IO_delDirOfAll($dir)
    {
        //先删除目录下的文件：
        if (is_dir($dir)) {
            $dh = opendir($dir);
            while (!!$file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        unlink($fullpath);
                    } else {
                        self::delDirOfAll($fullpath);
                    }
                }
            }
            closedir($dh);
            //删除当前文件夹：
            if (rmdir($dir)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 图片等比例缩放
     * @param resource $im 新建图片资源(imagecreatefromjpeg/imagecreatefrompng/imagecreatefromgif)
     * @param int $maxwidth 生成图像宽
     * @param int $maxheight 生成图像高
     * @param string $name 生成图像名称
     * @param string $filetype文件类型 (.jpg/.gif/.png)
     */
    public static function common_resizeImage($im, $maxwidth, $maxheight, $name, $filetype)
    {
        $pic_width = imagesx($im);
        $pic_height = imagesy($im);
        if (($maxwidth && $pic_width > $maxwidth) || ($maxheight && $pic_height > $maxheight)) {
            if ($maxwidth && $pic_width > $maxwidth) {
                $widthratio = $maxwidth / $pic_width;
                $resizewidth_tag = true;
            }
            if ($maxheight && $pic_height > $maxheight) {
                $heightratio = $maxheight / $pic_height;
                $resizeheight_tag = true;
            }
            if ($resizewidth_tag && $resizeheight_tag) {
                if ($widthratio < $heightratio) {
                    $ratio = $widthratio;
                } else {
                    $ratio = $heightratio;
                }
            }
            if ($resizewidth_tag && !$resizeheight_tag) {
                $ratio = $widthratio;
            }
            if ($resizeheight_tag && !$resizewidth_tag) {
                $ratio = $heightratio;
            }
            $newwidth = $pic_width * $ratio;
            $newheight = $pic_height * $ratio;
            if (function_exists("imagecopyresampled")) {
                $newim = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $pic_width, $pic_height);
            } else {
                $newim = imagecreate($newwidth, $newheight);
                imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $pic_width, $pic_height);
            }
            $name = $name . $filetype;
            imagejpeg($newim, $name);
            imagedestroy($newim);
        } else {
            $name = $name . $filetype;
            imagejpeg($im, $name);
        }
    }

    /**
     * 下载文件
     * @param string $file_path 绝对路径
     */
    public static function net_downFile($file_path)
    {
        //判断文件是否存在
        $file_path = iconv('utf-8', 'gb2312', $file_path); //对可能出现的中文名称进行转码
        if (!file_exists($file_path)) {
            exit('文件不存在！');
        }
        $file_name = basename($file_path); //获取文件名称
        $file_size = filesize($file_path); //获取文件大小
        $fp = fopen($file_path, 'r'); //以只读的方式打开文件
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: {$file_size}");
        header("Content-Disposition: attachment;filename={$file_name}");
        $buffer = 1024;
        $file_count = 0;
        //判断文件是否结束
        while (!feof($fp) && ($file_size - $file_count > 0)) {
            $file_data = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_data;
        }
        fclose($fp); //关闭文件
    }

    public static function common_getCwdOL()
    {
        $total = $_SERVER[PHP_SELF];
        $file = explode("/", $total);
        $file = $file[sizeof($file) - 1];
        return substr($total, 0, strlen($total) - strlen($file) - 1);
    }

    public static function common_getSiteUrl()
    {
        $host = $_SERVER[SERVER_NAME];
        $port = ($_SERVER[SERVER_PORT] == "80") ? "" : ":$_SERVER[SERVER_PORT]";
        return "http://" . $host . $port . Helper::getcwdOL();
    }

    public static function common_getCurUrl()
    {
        $host = $_SERVER[SERVER_NAME];
        $port = ($_SERVER[SERVER_PORT] == "80") ? "" : ":$_SERVER[SERVER_PORT]";
        $uri = $_SERVER[REQUEST_URI];
        return "http://" . $host . $port . $uri;
    }

    /**
     * 浏览器友好的变量输出
     * @param mixed $var 变量
     * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
     * @param string $label 标签 默认为空
     * @param boolean $strict 是否严谨 默认为true
     * @return void|string
     */
    public static function debug_dump($var, $echo = true, $label = null, $strict = true)
    {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        } else
            return $output;
    }

    public static function common_setglobal($key, $value, $group = null)
    {
        global $_G;
        $key = explode('/', $group === null ? $key : $group . '/' . $key);
        $p = &$_G;
        foreach ($key as $k) {
            if (!isset($p[$k]) || !is_array($p[$k])) {
                $p[$k] = array();
            }
            $p = &$p[$k];
        }
        $p = $value;
        return true;
    }

    public static function common_getglobal($key, $group = null)
    {
        global $_G;
        $key = explode('/', $group === null ? $key : $group . '/' . $key);
        $v = &$_G;
        foreach ($key as $k) {
            if (!isset($v[$k])) {
                return null;
            }
            $v = &$v[$k];
        }
        return $v;
    }

    static public function net_curlGet($url, $timeout = 5, $header = '')
    {
        $header1 = "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.12) Gecko/20181026 Firefox/3.6.12\r\n";
        $header1 .= "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
        $header1 .= "Accept-language: zh-cn,zh;q=0.5\r\n";
        $header1 .= "Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
        $header = empty($header) ? $header1 : $header;
        $ch = curl_init();

        if (stripos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, C('curl_http_version'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    static public function net_curlPost($url, $post_data = array(), $timeout = 5, $header = '', $data_type = '')
    {
        $header = empty($header) ? '' : $header;

        if ($data_type == 'json') {
            $post_string = json_encode($post_data);
        } else if (is_array($post_data)) {
            $post_string = http_build_query($post_data, '', '&');
        } else {
            $post_string = $post_data;
        }

        $ch = curl_init();

        if (stripos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, C('curl_http_version'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function debug_myDebug($var, $type = 'print')
    {
        $var = var_export($var, true);
        if ($type === 'print')
            echo "<zqdebug style='display:none;'>$var</zqdebug>";
        else if ($type === 'file')
            file_put_contents('zqdebug_' . time() . '.txt', $var);
    }

    /**
     * @param $twoArray
     * @param $columnKey
     * @param $val
     * @return false|int|string 二维数组查找
     */
    public static function common_twoArraySearch($twoArray, $columnKey, $val)
    {
        return array_search($val, array_column($twoArray, $columnKey));
    }

    /****
     * @param $str
     * @return array
     */
    public static function str_utf8StringToArray($str){
        $result = array();
        $len = strlen($str);
        $i = 0;
        while($i < $len){
            $chr = ord($str[$i]);
            if($chr == 9 || $chr == 10 || (32 <= $chr && $chr <= 126)) {
                $result[] = substr($str,$i,1);
                $i +=1;
            }elseif(192 <= $chr && $chr <= 223){
                $result[] = substr($str,$i,2);
                $i +=2;
            }elseif(224 <= $chr && $chr <= 239){
                $result[] = substr($str,$i,3);
                $i +=3;
            }elseif(240 <= $chr && $chr <= 247){
                $result[] = substr($str,$i,4);
                $i +=4;
            }elseif(248 <= $chr && $chr <= 251){
                $result[] = substr($str,$i,5);
                $i +=5;
            }elseif(252 <= $chr && $chr <= 253){
                $result[] = substr($str,$i,6);
                $i +=6;
            }
        }
        return $result;
    }
    function str_getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
}

class zqHelperOldPhp
{

    /**
     * 返回二维数组中某个键名的所有值
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function array_key_values($array = array(), $key = '')
    {
        $ret = array();
        foreach ((array)$array as $k => $v) {
            $ret[$k] = $v[$key];
        }
        return $ret;
    }

    /**
     * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
     * @param string $file 文件/目录
     * @return boolean
     */
    public static function is_writeable($file)
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        }
        return $writeable;
    }

}
