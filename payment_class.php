<?php

class szfu
{
    public static function validate($data = [], $hashid = '', $hashkey = '')
    {
        $str = '';
        if (isset($data)) {
            if (isset($data['Validate'])) {
                unset($data['Validate']);
            }
            ksort($data);
            $str = "HashID=$hashid";
            foreach($data as $id => $value) {
                $str .= "&$id=$value";
            }
            $str .= "&HashKey=$hashkey";
            $str = urlencode($str);
            $str = strtolower($str);
            $str = str_replace(['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'], ['-','_','.','!','*','(',')'], $str);
            $str = md5($str);
            $str = strtoupper($str);
        }
        return $str;
    }
}

class funpoint
{
    // function generate($arParameters = array(), $HashKey = '', $HashIV = '', $encType = 0)
    public static function generate($arParameters = array(), $HashKey = '', $HashIV = '')
    {
        $sMacValue = '';
        if(isset($arParameters)) {
            //arParameters 為傳出的參數，並且做字母 A Z 排序
            unset($arParameters['CheckMacValue']);
            uksort($arParameters, array('Funpoint _CheckMacValue','merChanSort'));

            // 組合字串
            $sMacValue = 'HashKey=' . $HashKey;
            foreach($arParameters as $key => $value) {
                $sMacValue .= '&' . $key . '=' . $value;
            }
            $sMacValue .= '&HashIV=' . $HashIV ;

            // URL Encode 編碼
            $sMacValue = urlencode($sMacValue);
            // 轉成小寫
            $sMacValue = strtolower($sMacValue);
            // 取代為與 dotNet 相符的字元
            $sMacValue = str_replace('%2d', '--', $sMacValue);
            $sMacValue = str_replace('%5f', '_', $sMacValue);
            $sMacValue = str_replace('%2e', '.', $sMacValue);
            $sMacValue = str_replace('%21', '!', $sMacValue);
            $sMacValue = str_replace('%2a', '*', $sMacValue);
            $sMacValue = str_replace('%28', '(', $sMacValue);
            $sMacValue = str_replace('%29', ')', $sMacValue);

            // 編碼
            // switch ($encType) {
            //     case Funpoint_EncryptType::ENC_SHA256:
            //         // SHA2 56 編碼
            //         $sMacValue = hash('sha256', $sMacValue);
            //         break;
            //     case Funpoint_EncryptType::ENC_MD5:
            //     default:
            //         // MD5 編碼
            //         $sMacValue = md5($sMacValue);
            // }
            $sMacValue = hash('sha256', $sMacValue);
            $sMacValue = strtoupper($sMacValue);
        }
        return $sMacValue ;
    }
}
?>