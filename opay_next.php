<?php

include("myadm/include.php");
include_once('./web_class.php');
include_once('./opay_function.php');

// 檢測SECCION資料
if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0); 

//  POST 資料至跳板
$data['foran'] = $_SESSION["foran"];
$data['serverid'] = $_SESSION["serverid"];
$data['lastan'] = $_SESSION["lastan"];

$pdo = openpdo(); 	

$query = $pdo->prepare("SELECT * FROM servers_log where auton=?");
$query->execute(array($data['lastan']));
if(!$datalist = $query->fetch()) alert("不明錯誤-8000207。", 0);
if($datalist["stats"] != 0) alert("金流狀態有誤-8000208。", 0);

// $datalist 訂單相關資料
$paytype = $datalist["paytype"];

$sq = $pdo->prepare("SELECT * FROM servers where auton=?");
$sq->execute(array($_SESSION["foran"]));
if(!$sqd = $sq->fetch()) alert("不明錯誤-8000204。", 0);

$opayData = array(
    'HashKey' => $sqd["HashKey"],
    'HashIV' => $sqd["HashIV"],
    'MerchantID' => $sqd["MerchantID"],
    'ReturnURL' => $protocol . $_SERVER['HTTP_HOST'] . '/' . $sqd["id"],
    'MerchantTradeNo' => $datalist["orderid"],
    'TotalAmount' => $datalist["money"],
    'paytype' => $paytype,
);

opay_function($opayData);

?>