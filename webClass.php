<?php

// 待確認是否沒有在使用(由web_class替代)

class web
{
    public static function err_responce($msg)
    {
        $return['success'] = false;
        $return['msg'] = $msg;
        echo json_encode($return);
        die;
    }

    public static function curl_api($url, $set){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($set));
        if (substr($url, 0, 8) == 'https://') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        $str = curl_exec($ch);
        if ($errno = curl_errno($ch)){
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $str;
    }
}
?>