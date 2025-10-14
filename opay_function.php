<?php
// 引入套件
include('plugin/opay/sdk/Opay.Payment.Integration.php');

function opay_function($data){
    try {
        $testMode = true;
        $obj = new OpayAllInOne();
    
        //服務參數
        $obj->ServiceURL  = "https://payment-stage.opay.tw/Cashier/AioCheckOut/V5";         //服務位置
        $obj->HashKey     = ($testMode) ? '5294y06JbISpM5x9' : $data["HashKey"];
        $obj->HashIV      = ($testMode) ? 'v77hoKGq4kWxNNIS' : $data["HashIV"]; 
        $obj->MerchantID  = ($testMode) ? '2000132' : $data["MerchantID"];      
        // $obj->EncryptType = OpayEncryptType::ENC_SHA256;    
        $obj->EncryptType = 1;   
            
        //基本參數(請依系統規劃自行調整)
        $MerchantTradeNo = "Test".time();

        // 判斷付款方式
        $opayPayType = '';
        if($data['paytype'] == '2'){
            $opayPayType = OpayPaymentMethod::ATM;
        }elseif($data['paytype'] == '3'){
            $opayPayType = OpayPaymentMethod::CVS;
        }elseif($data['paytype'] == '5'){
            $opayPayType = OpayPaymentMethod::Credit;
        }
    
        $obj->Send['ReturnURL']         = $data['ReturnURL']; //付款完成通知回傳的網址
        $obj->Send['MerchantTradeNo']   = $data['MerchantTradeNo'];                                       //訂單編號
        $obj->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');                                    //交易時間
        $obj->Send['TotalAmount']       = $data['TotalAmount'];                                                   //交易金額
        $obj->Send['TradeDesc']         = "贊助中心" ;                                       //交易描述
        $obj->Send['ChoosePayment']     = $opayPayType;    
    
        //訂單的商品資料
        array_push($obj->Send['Items'], array('Name' => random_products($_SESSION["serverid"]), 'Price' => $data['TotalAmount'],
        'Currency' => "元", 'Quantity' => (int) "1", 'URL' => "dedwed"));
    
        //ATM 延伸參數(可依系統需求選擇是否代入)
        $obj->SendExtend['ExpireDate'] = 3 ;     //繳費期限 (預設3天，最長60天，最短1天)
        $obj->SendExtend['PaymentInfoURL'] = ""; //伺服器端回傳付款相關資訊。
    
        //產生訂單(auto submit至OPay)
        $html = $obj->CheckOut();
        echo $html;//付款方式:ATM//CheckMacValue加密類型，請固定填入1，使用SHA256加密
    
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
?>