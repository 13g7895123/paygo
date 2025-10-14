<?php

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

    // 顯示支付資訊
    /*
    * @param string $param1 
    * @param string $param2
    * @param string $param3 
    *
    * @return string
    */
    // public static function payment_inf_render($type, $BankCode, $vAccount, $ExpireDate){
    public static function payment_inf_render($type, $param1, $param2, $param3){
        
        if ($type == 0){    // 銀行支付
            $BankCode = $param1;
            $vAccount = $param2;
            $ExpireDate = $param3;
            $html = "<div style='font-size:26px;color:white;'>銀行代碼：".$BankCode."&nbsp;&nbsp;繳費帳號：<span class='payment_code'value='$vAccount' style='color: white;'>".$vAccount."</span><img class='img_copy' src='./assets/images/copy.png' width='30' height='30' style='background-color: white; padding:5px;  border-radius: 50%; cursor: pointer; margin-left: 5px'><br>請在繳費期限 ".$ExpireDate." 前繳款</div>";
        }elseif ($type == 1){
            $store_text = $param1;
            $PaymentNo = $param2;
            $ExpireDate = $param3;
            $html = $store_text."代碼：<span class='payment_code'value='$PaymentNo' style='color: white;'>".$PaymentNo."</span><img class='img_copy' src='./assets/images/copy.png' width='30' height='30' style='background-color: white; padding:5px;  border-radius: 50%; cursor: pointer; margin-left: 5px'><br><div style='font-size:26px;color:white;'>請在繳費期限 ".$ExpireDate." 前繳款</div>";
        }
        return $html;
    }
}
?>