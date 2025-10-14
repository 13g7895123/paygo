<?php

include("include.php");

include("../phpclass/SimpleImage.php");

check_login();





$target_dir = "../assets/images/custombg/";


// _r 表示測試$_REQUEST是否有值
if(_r("st") == "readcustombg") {

	if(empty($an = _r("an"))) die("");

	$pdo = openpdo(); 	       	

	$chkq = $pdo->prepare("select custombg from servers where auton=:an");            

	$chkq->bindValue(':an', $an, PDO::PARAM_STR);	

	$chkq->execute();

	if($q = $chkq->fetch()) {

		echo $q["custombg"];

	}

	exit;

}



if(_r("st") == "clearcustombg") {

	if(empty($an = _r("an"))) die("");

	$pdo = openpdo(); 	       	

	$chkq = $pdo->prepare("select custombg from servers where auton=:an");            

	$chkq->bindValue(':an', $an, PDO::PARAM_STR);	

	$chkq->execute();

	if($q = $chkq->fetch()) {

		if(!empty($q["custombg"])) unlink($target_dir.$q["custombg"]);

		$chkq1 = $pdo->prepare("update servers set custombg = NULL where auton=:an");

		$chkq1->bindValue(':an', $an, PDO::PARAM_STR);

		$chkq1->execute();

	}

	echo '1';

	exit;

}

function photo_reset_reg($f = '', $ext = '') {

	if(empty($f) || empty($ext)) {

		return '圖片讀取錯誤。'.$f.'-'.$ext;

	}	

  if ($_FILES["file"]["size"] > 10000000) {

    return '檔案大小超過限制 - 10M。';        

  }

	

	$check = getimagesize($f);



  if($check !== false) { // 如果是照片    

    if($ext != "jpg" && $ext != "png" && $ext != "jpeg" && $ext != "gif" ) {        

      return '檔案只能是 jpg, png, jpeg, gif 。';        

    }



    $image = new \claviska\SimpleImage();

    $image->fromFile($f);

    $image->autoOrient();

    return true;

  } else { // 如果不是照片



    return '非允許的檔案類型。';

  	

  }

  return true;

}





if(_r("st") === "upload") {

	if(empty($an = _r("an"))) {

	echo "伺服器編號錯誤。";

	exit;

	}

		

	$target_file = basename($_FILES["file"]["name"]);

	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));	

	$newfilename = date("ymdHis")."_".$an."_".rand(1001, 99999).".".$imageFileType;

	$target_file = $target_dir . $newfilename;



  $pcheck = photo_reset_reg($_FILES["file"]["tmp_name"], $imageFileType);

  if($pcheck !== true) {

  	echo $pcheck;

  	exit;

  }

           

          if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {

			$pdo = openpdo();

			$chkq1 = $pdo->prepare("select custombg from servers where auton=:an");

			$chkq1->bindValue(':an', $an, PDO::PARAM_STR);            

            $chkq1->execute();

			if($cq1 = $chkq1->fetch()) {

				if(!empty($cq1["custombg"])) unlink($target_dir.$cq1["custombg"]);

			}



            $chkq = $pdo->prepare("update servers set custombg = :v where auton=:an");            

            $chkq->bindValue(':an', $an, PDO::PARAM_STR);

            $chkq->bindValue(':v', $newfilename, PDO::PARAM_STR);            

            $chkq->execute();

                       

          	echo 'uploadfix';

            exit;            

          } else {

            echo '不明錯誤';

            exit;

          }

	exit;

}

function save_gift_settings($pdo, $server_id) {
    // 獲取派獎設定資料
    $table_name = _r("table_name");
    $account_field = _r("account_field");
    $item_field = _r("item_field");
    $item_name_field = _r("item_name_field");
    $quantity_field = _r("quantity_field");
    $field_names = isset($_REQUEST["field_names"]) ? $_REQUEST["field_names"] : array();
    $field_values = isset($_REQUEST["field_values"]) ? $_REQUEST["field_values"] : array();
    
    // 如果有基本設定資料，處理派獎設定主表
    if(!empty($table_name) || !empty($account_field) || !empty($item_field) || !empty($item_name_field) || !empty($quantity_field)) {
        // 先檢查是否已存在
        $check_query = $pdo->prepare("SELECT id FROM send_gift_settings WHERE server_id = :server_id");
        $check_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
        $check_query->execute();
        
        if($existing = $check_query->fetch()) {
            // 更新現有記錄
            $update_query = $pdo->prepare("
                UPDATE send_gift_settings SET 
                    table_name = :table_name,
                    account_field = :account_field,
                    item_field = :item_field,
                    item_name_field = :item_name_field,
                    quantity_field = :quantity_field
                WHERE id = :id
            ");
            $update_query->bindValue(':id', $existing['id'], PDO::PARAM_INT);
            $update_query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
            $update_query->bindValue(':account_field', $account_field, PDO::PARAM_STR);
            $update_query->bindValue(':item_field', $item_field, PDO::PARAM_STR);
            $update_query->bindValue(':item_name_field', $item_name_field, PDO::PARAM_STR);
            $update_query->bindValue(':quantity_field', $quantity_field, PDO::PARAM_STR);
            $update_query->execute();
        } else {
            // 插入新記錄
            $insert_query = $pdo->prepare("
                INSERT INTO send_gift_settings (server_id, table_name, account_field, item_field, item_name_field, quantity_field) 
                VALUES (:server_id, :table_name, :account_field, :item_field, :item_name_field, :quantity_field)
            ");
            $insert_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
            $insert_query->bindValue(':table_name', $table_name, PDO::PARAM_STR);
            $insert_query->bindValue(':account_field', $account_field, PDO::PARAM_STR);
            $insert_query->bindValue(':item_field', $item_field, PDO::PARAM_STR);
            $insert_query->bindValue(':item_name_field', $item_name_field, PDO::PARAM_STR);
            $insert_query->bindValue(':quantity_field', $quantity_field, PDO::PARAM_STR);
            $insert_query->execute();
        }
    }
    
    // 處理動態欄位 - 先刪除舊的，再插入新的
    $delete_fields_query = $pdo->prepare("DELETE FROM send_gift_fields WHERE server_id = :server_id");
    $delete_fields_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
    $delete_fields_query->execute();
    
    // 插入新的動態欄位
    if(!empty($field_names) && !empty($field_values)) {
        for($i = 0; $i < count($field_names); $i++) {
            $field_name = isset($field_names[$i]) ? trim($field_names[$i]) : '';
            $field_value = isset($field_values[$i]) ? trim($field_values[$i]) : '';
            
            // 只保存有內容的欄位
            if(!empty($field_name) && !empty($field_value)) {
                $insert_field_query = $pdo->prepare("
                    INSERT INTO send_gift_fields (server_id, field_name, field_value, sort_order) 
                    VALUES (:server_id, :field_name, :field_value, :sort_order)
                ");
                $insert_field_query->bindValue(':server_id', $server_id, PDO::PARAM_STR);
                $insert_field_query->bindValue(':field_name', $field_name, PDO::PARAM_STR);
                $insert_field_query->bindValue(':field_value', $field_value, PDO::PARAM_STR);
                $insert_field_query->bindValue(':sort_order', $i, PDO::PARAM_INT);
                $insert_field_query->execute();
            }
        }
    }
}

function save_bank_funds($pdo, $server_id) {
    // 獲取銀行轉帳金流設定
    $pay_bank = _r("pay_bank");
    $gstats_bank = _r("gstats_bank");
    
    // 調試日誌
    error_log("save_bank_funds called with pay_bank: " . $pay_bank . ", server_id: " . $server_id);
    
    if(empty($pay_bank) || $pay_bank == 'no') {
        // 如果沒有選擇銀行金流服務或選擇"無"，刪除該服務類型的設定（保留其他服務）
        if(!empty($pay_bank) && $pay_bank == 'no') {
            // 僅刪除所有該伺服器的銀行金流設定（因為用戶明確選擇"無"）
            // $delete_query = $pdo->prepare("DELETE FROM bank_funds WHERE server_code = :server_code");
            // $delete_query->bindValue(':server_code', $server_id, PDO::PARAM_STR);
            // $delete_query->execute();
        }
        return;
    }
    
    // 根據不同的銀行金流服務處理資料
    $bank_funds_data = [];
    
    switch($pay_bank) {
        case 'ecpay':
            // 綠界金流
            $merchant_id = _r("ecpay_merchant_id_bank");
            $hashkey = _r("ecpay_hashkey_bank");
            $hashiv = _r("ecpay_hashiv_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'ecpay',
                    'merchant_id' => $merchant_id,
                    'hashkey' => $hashkey,
                    'hashiv' => $hashiv,
                    'verify_key' => null
                ];
            }            
            break;
            
        case 'ebpay':  
            // 藍新金流
            $merchant_id = _r("ebpay_merchant_id_bank");
            $hashkey = _r("ebpay_hashkey_bank");
            $hashiv = _r("ebpay_hashiv_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'ebpay',
                    'merchant_id' => $merchant_id,
                    'hashkey' => $hashkey,
                    'hashiv' => $hashiv,
                    'verify_key' => null
                ];
            }
            
        case 'funpoint':
            // 歐買尬金流
            $merchant_id = _r("funpoint_merchant_id_bank");
            $hashkey = _r("funpoint_hashkey_bank");
            $hashiv = _r("funpoint_hashiv_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'funpoint',
                    'merchant_id' => $merchant_id,
                    'hashkey' => $hashkey,
                    'hashiv' => $hashiv,
                    'verify_key' => null
                ];
            }
            break;
            
        case 'gomypay':
            $merchant_id = _r("gomypay_shop_id_bank");
            $verify_key = _r("gomypay_key_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'gomypay',
                    'merchant_id' => $merchant_id,
                    'hashkey' => null,
                    'hashiv' => null,
                    'verify_key' => $verify_key
                ];
            }
            break;
            
        case 'smilepay':
            $merchant_id = _r("smilepay_shop_id_bank");
            $verify_key = _r("smilepay_key_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'smilepay',
                    'merchant_id' => $merchant_id,
                    'hashkey' => null,
                    'hashiv' => null,
                    'verify_key' => $verify_key
                ];
            }
            break;
            
        case 'szfu':
            $merchant_id = _r("szfupay_shop_id_bank");
            $verify_key = _r("szfupay_key_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'szfu',
                    'merchant_id' => $merchant_id,
                    'hashkey' => null,
                    'hashiv' => null,
                    'verify_key' => $verify_key
                ];
            }
            break;
            
        case 'ant':
            $username = _r("ant_username_bank");
            $hashkey = _r("ant_hashkey_bank");
            $hashiv = _r("ant_hashiv_bank");

            if(!empty($username)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'ant',
                    'merchant_id' => $username, // 使用 username 作為 merchant_id
                    'username' => $username, // 新增 username 欄位
                    'hashkey' => $hashkey,
                    'hashiv' => $hashiv,
                    'verify_key' => null
                ];
            }
            break;
            
        case 'pchome':
            $merchant_id = _r("pchome_app_id_bank");
            $verify_key = _r("pchome_secret_code_bank");
            
            if(!empty($merchant_id)) {
                $bank_funds_data[] = [
                    'server_code' => $server_id,
                    'third_party_payment' => 'pchome',
                    'merchant_id' => $merchant_id,
                    'hashkey' => null,
                    'hashiv' => null,
                    'verify_key' => $verify_key
                ];
            }
            break;
    }
    
    // 使用 UPSERT 邏輯：如果記錄存在則更新，不存在則插入
    foreach($bank_funds_data as $data) {
        error_log("Processing bank fund data: " . json_encode($data));
        
        // 先檢查是否已存在
        $check_query = $pdo->prepare("
            SELECT id FROM bank_funds 
            WHERE server_code = :server_code AND third_party_payment = :third_party_payment
        ");
        $check_query->bindValue(':server_code', $data['server_code'], PDO::PARAM_STR);
        $check_query->bindValue(':third_party_payment', $data['third_party_payment'], PDO::PARAM_STR);
        $check_query->execute();
        
        if($existing = $check_query->fetch()) {
            // 更新現有記錄
            error_log("Updating existing record ID: " . $existing['id']);
            $update_query = $pdo->prepare("
                UPDATE bank_funds SET
                    merchant_id = :merchant_id,
                    username = :username,
                    hashkey = :hashkey,
                    hashiv = :hashiv,
                    verify_key = :verify_key
                WHERE id = :id
            ");
            $update_query->bindValue(':id', $existing['id'], PDO::PARAM_INT);
            $update_query->bindValue(':merchant_id', $data['merchant_id'], PDO::PARAM_STR);
            $update_query->bindValue(':username', $data['username'] ? $data['username'] : null, PDO::PARAM_STR);
            $update_query->bindValue(':hashkey', $data['hashkey'], PDO::PARAM_STR);
            $update_query->bindValue(':hashiv', $data['hashiv'], PDO::PARAM_STR);
            $update_query->bindValue(':verify_key', $data['verify_key'], PDO::PARAM_STR);
            $update_query->execute();
        } else {
            // 插入新記錄
            error_log("Inserting new record");
            $insert_query = $pdo->prepare("
                INSERT INTO bank_funds (server_code, third_party_payment, merchant_id, username, hashkey, hashiv, verify_key)
                VALUES (:server_code, :third_party_payment, :merchant_id, :username, :hashkey, :hashiv, :verify_key)
            ");
            $insert_query->bindValue(':server_code', $data['server_code'], PDO::PARAM_STR);
            $insert_query->bindValue(':third_party_payment', $data['third_party_payment'], PDO::PARAM_STR);
            $insert_query->bindValue(':merchant_id', $data['merchant_id'], PDO::PARAM_STR);
            $insert_query->bindValue(':username', $data['username'] ? $data['username'] : null, PDO::PARAM_STR);
            $insert_query->bindValue(':hashkey', $data['hashkey'], PDO::PARAM_STR);
            $insert_query->bindValue(':hashiv', $data['hashiv'], PDO::PARAM_STR);
            $insert_query->bindValue(':verify_key', $data['verify_key'], PDO::PARAM_STR);
            $insert_query->execute();
        }
    }
}

if(_r("st") == 'addsave') {

	$an = $_REQUEST["an"];

	$names = $_REQUEST["names"];

	$id = $_REQUEST["id"];

    $game = $_REQUEST['game'];

	$base_money = $_REQUEST["base_money"];

	$stats = $_REQUEST["stats"];

	$ip  = $_REQUEST["ip"];

	$port = $_REQUEST["port"];

	$dbname = $_REQUEST["dbname"];

	$user = $_REQUEST["user"];	

	$pass = $_REQUEST["pass"];

	$pid = $_REQUEST["pid"];

	$bonusid = $_REQUEST["bonusid"];

	$bonusrate = $_REQUEST["bonusrate"];

	if(empty($bonusrate)) $bonusrate = 0;

	$gp = $_REQUEST["gp"];

	$des = $_REQUEST["des"];

	$pay_cp = $_REQUEST["pay_cp"];

	$pay_cp2 = $_REQUEST["pay_cp2"];

	$pay_bank = $_REQUEST["pay_bank"];

	$gstats_bank = $_REQUEST["gstats_bank"];

	if($names == "") alert("請輸入伺服器名稱。", 0);

	if($id == "") alert("請輸入尾綴代號。", 0);

	if($base_money == "") alert("請輸入最低金額。", 0);

    if(!is_numeric($base_money)) alert("最低金額只能輸入數字。", 0);

	if($stats == "") alert("請輸入狀態。", 0);

	if($stats == "1") $stats = 1;

	else $stats = 0;

	

	if($gstats == "1") $gstats = 1;

	else $gstats = 0;

	if($gstats2 == "1") $gstats2 = 1;

	else $gstats2 = 0;

	if($gstats_bank == "1") $gstats_bank = 1;

	else $gstats_bank = 0;

	/*

	if($ip == "") alert("請輸入資料庫位置。", 0);

	if($port == "") alert("請輸入資料庫端口。", 0);

	if($dbname == "") alert("請輸入資料庫名稱,不能純數字。", 0);

	if($user == "") alert("請輸入資料庫帳號。", 0);

	if($pass == "") alert("請輸入資料庫密碼。", 0);

	*/

	

	if($an == "") {		

	  $pdo = openpdo(); 

	  $query = $pdo->query("SELECT * FROM servers where names='".$names."' or id='".$id."'");

    $query->execute();

    if($datalist = $query->fetch()) {

    	$pdo = null;

    	alert("資料庫中已經有重覆的伺服器名稱或尾綴代號。", 0);

    	die();

    }

	  

	$input = [

	    'des' => $des, 

		'pay_cp' => $pay_cp,

		'pay_cp2' => $pay_cp2,

		'id' => $id,

        'game' => $game,

		'names' => $names,

		'stats' => $stats,

		'db_ip' => $ip,

		'db_port' => $port,

		'db_name' => $dbname,

		'db_user' => $user,

		'db_pass' => $pass,

		'db_pid'=> $pid,

		'db_bonusid'=> $bonusid,

		'db_bonusrate'=> $bonusrate,

		'base_money' => $base_money,

		'HashIV' => _r("HashIV"),

		'HashKey' => _r("HashKey"),

		'MerchantID' => _r("MerchantID"),

		'pchome_app_id' => _r("pchome_app_id"),

	    'pchome_secret_code' => _r("pchome_secret_code"),

		'gomypay_shop_id' => _r("gomypay_shop_id"),

	    'gomypay_key' => _r("gomypay_key"),

		'smilepay_shop_id' => _r("smilepay_shop_id"),

	    'smilepay_key' => _r("smilepay_key"),

		'gstats' => _r("gstats"),

		'HashIV2' => _r("HashIV2"),

		'HashKey2' => _r("HashKey2"),

		'MerchantID2' => _r("MerchantID2"),

		'pchome_app_id2' => _r("pchome_app_id2"),

	    'pchome_secret_code2' => _r("pchome_secret_code2"),

		'gomypay_shop_id2' => _r("gomypay_shop_id2"),

	    'gomypay_key2' => _r("gomypay_key2"),

		'smilepay_shop_id2' => _r("smilepay_shop_id2"),

	    'smilepay_key2' => _r("smilepay_key2"),

		'szfupay_shop_id2' => _r("szfupay_shop_id2"),

	    'szfupay_key2' => _r("szfupay_key2"),

		'gstats2' => _r("gstats2"),

		'paytable' => _r("paytable"),

		'paytable_custom' => _r("paytable_custom"),

		'gp' => $gp,

		'products' => _r("products"),

		'max_credit' => _r("max_credit"),

		'max_store' => _r("max_store"),

		'max_bank' => _r("max_bank"),

		'pay_bank' => $pay_bank,
		'gstats_bank' => $gstats_bank
	];

	$dbclassupdatesql = implode(",", array_keys($input));

	foreach($input as $k => $v ) {

	  $i2arr[] = ':'.$k;

	  $dbclassupdateprep[':'.$k] = $v;

	}

	$i2sql = implode(",", $i2arr);

    $query = $pdo->prepare('INSERT INTO servers ('.$dbclassupdatesql.') VALUES ('.$i2sql.')');    

    $query->execute($dbclassupdateprep);

	// 取得新建立的伺服器ID
	$new_server_id = $pdo->lastInsertId();
	
	// 處理銀行轉帳金流設定
	$new_id = $pdo->lastInsertId();

    // 查詢新伺服器的 auton 值
    $auton_query = $pdo->prepare("SELECT auton FROM servers WHERE id = :id");
    $auton_query->bindValue(':id', $new_id, PDO::PARAM_INT);
    $auton_query->execute();
    $server_data = $auton_query->fetch();
    $new_server_auton = $server_data['auton'];

    // 使用 auton 儲存銀行金流資料和派獎設定
    save_bank_funds($pdo, $new_server_auton);

    // 處理派獎設定
	save_gift_settings($pdo, $new_server_auton);

    alert("伺服器新增完成。", "index.php");

    die();

		

	} else {



	  $pdo = openpdo(); 

	  $input = [

		  'des' => $des,

		  'pay_cp' => $pay_cp,

		  'pay_cp2' => $pay_cp2,

		  'id' => $id,

          'game' => $game,

		  'names' => $names,

		  'stats' => $stats,

		  'db_ip' => $ip,

		  'db_port' => $port,

		  'db_name' => $dbname,

		  'db_user' => $user,

		  'db_pass' => $pass,

		  'db_pid'=> $pid,

		  'db_bonusid'=> $bonusid,

		  'db_bonusrate'=> $bonusrate,

		  'base_money' => $base_money,

		  'HashIV' => _r("HashIV"),

		  'HashKey' => _r("HashKey"),

		  'MerchantID' => _r("MerchantID"),

		  'pchome_app_id' => _r("pchome_app_id"),

		  'pchome_secret_code' => _r("pchome_secret_code"),

		  'gomypay_shop_id' => _r("gomypay_shop_id"),

		  'gomypay_key' => _r("gomypay_key"),

		  'smilepay_shop_id' => _r("smilepay_shop_id"),

		  'smilepay_key' => _r("smilepay_key"),

		  'gstats' => _r("gstats"),

		  'HashIV2' => _r("HashIV2"),

		  'HashKey2' => _r("HashKey2"),

		  'MerchantID2' => _r("MerchantID2"),

		  'pchome_app_id2' => _r("pchome_app_id2"),

		  'pchome_secret_code2' => _r("pchome_secret_code2"),

		  'gomypay_shop_id2' => _r("gomypay_shop_id2"),

		  'gomypay_key2' => _r("gomypay_key2"),

		  'smilepay_shop_id2' => _r("smilepay_shop_id2"),

		  'smilepay_key2' => _r("smilepay_key2"),

		  'szfupay_shop_id2' => _r("szfupay_shop_id2"),

		  'szfupay_key2' => _r("szfupay_key2"),

		  'gstats2' => _r("gstats2"),

		  'paytable' => _r("paytable"),
		  
		  'paytable_custom' => _r("paytable_custom"),

		  'gp' => $gp,

		  'products' => _r("products"),

		  'max_credit' => _r("max_credit"),

		  'max_store' => _r("max_store"),

		  'max_bank' => _r("max_bank"),

		  'pay_bank' => $pay_bank,
		  'gstats_bank' => $gstats_bank
	  ];

	  $dbclassupdateprep = [];

  	  	

	  foreach($input as $k => $v ) {

		$dbclassupdatesql[] = $k.'=:'.$k;

		$dbclassupdateprep[':'.$k] = $v;

	  }

	  

	$dbclassupdateprep[":an"] = $an;

    $query = $pdo->prepare('UPDATE servers SET '.implode(",", $dbclassupdatesql).' where auton=:an');    

    $query->execute($dbclassupdateprep);

	// 處理銀行轉帳金流設定
    save_bank_funds($pdo, $an);

    // 處理派獎設定
    save_gift_settings($pdo, $an);

    alert("伺服器修改完成。", "index.php");

    die();

	}

	

}



if(!empty($an = _r("an"))) {

	$pdo = openpdo(); 

    $query    = $pdo->query("SELECT * FROM servers where auton='".$_REQUEST["an"]."'");

    $query->execute();

    $datalist = $query->fetch();

	// 載入銀行轉帳金流設定
    $bank_query = $pdo->prepare("SELECT * FROM bank_funds WHERE server_code = :server_code");
    $bank_query->bindValue(':server_code', $an, PDO::PARAM_STR);
    $bank_query->execute();
    $bank_funds = $bank_query->fetchAll(PDO::FETCH_ASSOC);

    // print_r($an); die();

	// 將銀行金流資料整理為 JavaScript 可用的格式
    $bank_funds_js = [];
    $first_payment_type = null;
    
    foreach($bank_funds as $fund) {
        $payment_type = $fund['third_party_payment'];
        
        // 記錄第一個找到的金流服務作為預設選擇
        if(empty($first_payment_type)) {
            // $first_payment_type = $payment_type;
        }
        
        $bank_funds_js[$payment_type] = [
            'merchant_id' => $fund['merchant_id'],
            'username' => $fund['username'] ? $fund['username'] : null,
            'hashkey' => $fund['hashkey'],
            'hashiv' => $fund['hashiv'],
            'verify_key' => $fund['verify_key']
        ];
    }

	// 載入派獎設定資料
    $gift_query = $pdo->prepare("SELECT * FROM send_gift_settings WHERE server_id = :server_id");
    $gift_query->bindValue(':server_id', $an, PDO::PARAM_STR);
    $gift_query->execute();
    $gift_settings = $gift_query->fetch(PDO::FETCH_ASSOC);
    
    if($gift_settings) {
        $datalist['table_name'] = $gift_settings['table_name'];
        $datalist['account_field'] = $gift_settings['account_field'];
        $datalist['item_field'] = $gift_settings['item_field'];
        $datalist['item_name_field'] = $gift_settings['item_name_field'];
        $datalist['quantity_field'] = $gift_settings['quantity_field'];
    }
    
    // 載入動態欄位資料
    $fields_query = $pdo->prepare("SELECT * FROM send_gift_fields WHERE server_id = :server_id ORDER BY sort_order");
    $fields_query->bindValue(':server_id', $an, PDO::PARAM_STR);
    $fields_query->execute();
    $dynamic_fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);

	// 載入所有金流服務的資料到表單欄位（每個金流都有獨立的欄位）
    foreach($bank_funds_js as $payment_type => $fund_data) {
        switch($payment_type) {
            case 'ecpay':
                $datalist['ecpay_merchant_id_bank'] = $fund_data['merchant_id'];
                $datalist['ecpay_hashkey_bank'] = $fund_data['hashkey'];
                $datalist['ecpay_hashiv_bank'] = $fund_data['hashiv'];
                break;

            case 'ebpay':
                $datalist['ebpay_merchant_id_bank'] = $fund_data['merchant_id'];
                $datalist['ebpay_hashkey_bank'] = $fund_data['hashkey'];
                $datalist['ebpay_hashiv_bank'] = $fund_data['hashiv'];
                break;

            case 'funpoint':
                $datalist['funpoint_merchant_id_bank'] = $fund_data['merchant_id'];
                $datalist['funpoint_hashkey_bank'] = $fund_data['hashkey'];
                $datalist['funpoint_hashiv_bank'] = $fund_data['hashiv'];
                break;

            case 'gomypay':
                $datalist['gomypay_shop_id_bank'] = $fund_data['merchant_id'];
                $datalist['gomypay_key_bank'] = $fund_data['verify_key'];
                break;

            case 'smilepay':
                $datalist['smilepay_shop_id_bank'] = $fund_data['merchant_id'];
                $datalist['smilepay_key_bank'] = $fund_data['verify_key'];
                break;

            case 'szfu':
                $datalist['szfupay_shop_id_bank'] = $fund_data['merchant_id'];
                $datalist['szfupay_key_bank'] = $fund_data['verify_key'];
                break;

            case 'ant':
                $datalist['ant_username_bank'] = $fund_data['username'] ? $fund_data['username'] : $fund_data['merchant_id'];
                $datalist['ant_hashkey_bank'] = $fund_data['hashkey'];
                $datalist['ant_hashiv_bank'] = $fund_data['hashiv'];
                break;

            case 'pchome':
                $datalist['pchome_app_id_bank'] = $fund_data['merchant_id'];
                $datalist['pchome_secret_code_bank'] = $fund_data['verify_key'];
                break;
        }
    }

    $tt = "修改";

    $tt2 = "?st=addsave";

    if(!$datalist['base_money']) $base_money = 0;

    else $base_money = $datalist['base_money'];

    $sts = $datalist['stats'];

    $gstats = $datalist['gstats'];

	$gstats2 = $datalist['gstats2'];

	$gstats_bank = isset($datalist['gstats_bank']) ? $datalist['gstats_bank'] : 1; // 預設正式環境

	$products = $datalist['products'];

  } else {

    $tt = "新增";

    $tt2 = "?st=addsave";

    $base_money = 0;

    $sts = 1;

    $gstats = 1;

	$gstats2 = 1;

	$gstats_bank = 1;
	
	// 新增模式：初始化空的銀行金流資料
	$bank_funds_js = [];
}

top_html();

?>

<link rel="stylesheet" href="assets/css/jquery.fileupload.css">

<link rel="stylesheet" href="assets/css/jquery.fileupload-ui.css">

<noscript><link rel="stylesheet" href="assets/css/jquery.fileupload-noscript.css"></noscript>

<noscript><link rel="stylesheet" href="assets/css/jquery.fileupload-ui-noscript.css"></noscript>

			<!-- 

				MIDDLE 

			-->

			<section id="middle">

				<div id="content" class="dashboard padding-20">



					<!-- 

						PANEL CLASSES:

							panel-default

							panel-danger

							panel-warning

							panel-info

							panel-success



						INFO: 	panel collapse - stored on user localStorage (handled by app.js _panels() function).

								All pannels should have an unique ID or the panel collapse status will not be stored!

					-->

					<div id="panel-1" class="panel panel-default">

						<div class="panel-heading">

							<span class="title elipsis">

								<strong><a href="index.php">伺服器管理</a></strong> <!-- panel title -->

                <small class="size-12 weight-300 text-mutted hidden-xs"><?=$tt?>伺服器</small>

							</span>



							<!-- right options -->

							<ul class="options pull-right list-inline">								

								<li><a href="#" class="opt panel_fullscreen hidden-xs" data-toggle="tooltip" title="Fullscreen" data-placement="bottom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></a></li>

							</ul>

							<!-- /right options -->



						</div>



						<!-- panel content -->

						<div class="panel-body">

							

							<a href="<?=$_SERVER['HTTP_REFERER']?>" class="btn btn-primary"><i class="fa-solid fa-backward"></i> 上一頁</a>

							<div class="table">

								<form name="form1" method="post" action="server_add.php<?=$tt2?>">

	<table class="table table-bordered">

						  <tbody>

<tr><td style="background:#666;color:white;text-align:center;">伺服器設定</td></tr>

<tr><td>群組：<input name="gp" id="gp" type="text" value="<?=$datalist['gp']?>">&nbsp;&nbsp;<small>(如無留空)</small></td></tr>

<tr><td>排序：<input name="des" id="des" type="number" value="<?=$datalist['des']?>">&nbsp;&nbsp;<small>(只能數字,數字越大排序越高)</small></td></tr>

<tr><td>伺服器名稱：<input name="names" id="names" type="text" value="<?=$datalist['names']?>" required></td></tr>

<tr><td>尾綴代號：<input name="id" id="id" type="text" value="<?=$datalist['id']?>" required>

	<br>

	<small><font color="red">請使用英數，不可使用中文。</font>尾綴代號是用來定義網址後綴名稱，如將本伺服器的尾綴代號設定為 line1<br>前台贊助網址就是 <?=$weburl?>line1<br>設定為 line2，前台贊助網址就是 <?=$weburl?>line2</small></td></tr>

<tr><td>最低金額：<input name="base_money" id="base_money" type="number" value="<?=$base_money?>" required>&nbsp;&nbsp;填入 0 為最少需贊助 100

<tr><td>給第三方的商品：<input name="products" id="products" type="text" value="<?=$products;?>"></td></tr>

<tr><td>狀態：<input type="radio" name="stats" value="1"<?if($sts == 1) echo " checked"?> required> 開啟 &nbsp;&nbsp;<input type="radio" name="stats" value="0"<?if($sts != 1) echo " checked"?>> 停用</td></tr>

<tr><td>

<?php

if(empty($an)) echo '修改時才能上傳底圖';

else {

echo '<div class="col-md-5 col-xs-12 margin-bottom-10">自訂底圖：

<span class="btn btn-info btn-sm fileinput-button"><span>上傳檔案</span><input id="fileuploads" type="file" class="fileupload" name="file"></span>

<div id="progress" class="progress progress-striped" style="display:none"><div class="bar progress-bar progress-bar-lovepy"></div></div>

<a href="javascript:removecustombg();" class="btn btn-danger btn-sm">移除底圖</a>

</div>';

}

?>

<div id="custombgdiv" class="col-md-12 col-xs-12">



</div>



</td></tr>
<tr><td style="background:#666;color:white;text-align:center;">資料庫設定</td></tr>
<tr><td>遊戲選擇：

    <input type="radio" name="game" value="0"<?if($datalist['game'] == '0') echo " checked"?>> 天堂&nbsp;&nbsp;

	<input type="radio" name="game" value="1"<?if($datalist['game'] == '1') echo " checked"?>> RO&nbsp;&nbsp;

    <input type="radio" name="game" value="2"<?if($datalist['game'] == '2') echo " checked"?>> 其他遊戲&nbsp;&nbsp;

</td></tr>

<tr><td>資料庫位置(IP)：<input name="ip" id="ip" type="text" value="<?=$datalist['db_ip']?>">&nbsp;&nbsp;&nbsp;&nbsp;<a href="#t" onclick="test_connect();" class="btn btn-default btn-xs">連線測試</a></td></tr>

<tr><td>資料庫端口(PORT)：<input name="port" id="port" type="text" value="<?=$datalist['db_port']?>"></td></tr>

<tr><td>資料庫名稱(DBNAME)：<input name="dbname" id="dbname" type="text" value="<?=$datalist['db_name']?>">&nbsp;&nbsp;<small>(不能為純數字)</small></td></tr>

<tr><td>資料庫帳號(USER)：<input name="user" id="user" type="text" value="<?=$datalist['db_user']?>"></td></tr>

<tr><td>資料庫密碼(PASS)：<input name="pass" id="pass" type="text" value="<?=$datalist['db_pass']?>">&nbsp;&nbsp;<small>(不能有亂碼字符!@#$%^)</small></td></tr>

<tr><td>資料表名稱(Table)：

    <input type="radio" name="paytable" value="shop_user"<?if($datalist['paytable'] == 'shop_user') echo " checked"?>> shop_user&nbsp;&nbsp;
	<input type="radio" name="paytable" value="ezpay"<?if($datalist['paytable'] == 'ezpay') echo " checked"?>> ezpay&nbsp;&nbsp;

	<!-- 自定義資料表 -->
	<input type="radio" name="paytable" value="custom"<?if($datalist['paytable'] == 'custom') echo " checked"?>> 其他&nbsp;&nbsp;
	<input type="text" name="paytable_custom" id="paytable_custom" value="<?=$datalist['paytable_custom']?>">
</td></tr>

<tr><td>物品代碼(P_ID)：<input name="pid" id="pid" type="text" value="<?=$datalist['db_pid']?>"></td></tr>

<tr><td>紅利幣代碼(BONUS_ID)：<input name="bonusid" id="bonusid" type="text" value="<?=$datalist['db_bonusid']?>"> 倍率：<input name="bonusrate" id="bonusrate" type="number" value="<?=$datalist['db_bonusrate']?>" min="0" max="100"> <small>(0 ~ 100)</small></td></tr>



<tr><td style="background:#6666ff;color:white;text-align:center;">金流設定</td></tr>
<tr><td>銀行轉帳金流服務：<input type="radio" name="pay_bank" value="ecpay"<?if($datalist['pay_bank'] == 'ecpay') echo " checked"?>> 綠界 &nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="ebpay"<?if($datalist['pay_bank'] == 'ebpay') echo " checked"?>> 藍新&nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="gomypay"<?if($datalist['pay_bank'] == 'gomypay') echo " checked"?>> 萬事達&nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="smilepay"<?if($datalist['pay_bank'] == 'smilepay') echo " checked"?>> 速買配&nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="funpoint"<?if($datalist['pay_bank'] == 'funpoint') echo " checked"?>> 歐買尬&nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="szfu"<?if($datalist['pay_bank'] == 'szfu') echo " checked"?>> 數支付&nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="ant"<?if($datalist['pay_bank'] == 'ant') echo " checked"?>> ANT&nbsp;&nbsp;

	<input type="radio" name="pay_bank" value="no"<?if($datalist['pay_bank'] == 'no') echo " checked"?>> 無

</td></tr>

<tr><td class="ecpaydiv_bank">

特店編號：<input name="ecpay_merchant_id_bank" id="ecpay_merchant_id_bank" type="text" value="<?=$datalist['ecpay_merchant_id_bank']?>">&nbsp;&nbsp;

介接 HashKey：<input name="ecpay_hashkey_bank" id="ecpay_hashkey_bank" type="text" value="<?=$datalist['ecpay_hashkey_bank']?>">&nbsp;&nbsp;

介接 HashIV：<input name="ecpay_hashiv_bank" id="ecpay_hashiv_bank" type="text" value="<?=$datalist['ecpay_hashiv_bank']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="ebpaydiv_bank">

特店編號：<input name="ebpay_merchant_id_bank" id="ebpay_merchant_id_bank" type="text" value="<?=$datalist['ebpay_merchant_id_bank']?>">&nbsp;&nbsp;

介接 HashKey：<input name="ebpay_hashkey_bank" id="ebpay_hashkey_bank" type="text" value="<?=$datalist['ebpay_hashkey_bank']?>">&nbsp;&nbsp;

介接 HashIV：<input name="ebpay_hashiv_bank" id="ebpay_hashiv_bank" type="text" value="<?=$datalist['ebpay_hashiv_bank']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="funpointdiv_bank">

特店編號：<input name="funpoint_merchant_id_bank" id="funpoint_merchant_id_bank" type="text" value="<?=$datalist['funpoint_merchant_id_bank']?>">&nbsp;&nbsp;

介接 HashKey：<input name="funpoint_hashkey_bank" id="funpoint_hashkey_bank" type="text" value="<?=$datalist['funpoint_hashkey_bank']?>">&nbsp;&nbsp;

介接 HashIV：<input name="funpoint_hashiv_bank" id="funpoint_hashiv_bank" type="text" value="<?=$datalist['funpoint_hashiv_bank']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="pchomediv_bank">

支付連 APP_ID：<input name="pchome_app_id_bank" id="pchome_app_id_bank" type="text" value="<?=$datalist['pchome_app_id_bank']?>">&nbsp;&nbsp;

支付連 SECRET_CODE：<input name="pchome_secret_code_bank" id="pchome_secret_code_bank" type="text" value="<?=$datalist['pchome_secret_code_bank']?>">

</td></tr>

<tr><td class="gomypaydiv_bank">

	Gomypay 商店代號：<input name="gomypay_shop_id_bank" id="gomypay_shop_id_bank" type="text" value="<?=$datalist['gomypay_shop_id_bank']?>">&nbsp;&nbsp;

    Gomypay 交易驗證碼：<input name="gomypay_key_bank" id="gomypay_key_bank" type="text" value="<?=$datalist['gomypay_key_bank']?>">

</td></tr>

<tr><td class="smilepaydiv_bank">

    速買配 商家代號：<input name="smilepay_shop_id_bank" id="smilepay_shop_id_bank" type="text" value="<?=$datalist['smilepay_shop_id_bank']?>">&nbsp;&nbsp;

    速買配 檢查碼 Verify_key：<input name="smilepay_key_bank" id="smilepay_key_bank" type="text" value="<?=$datalist['smilepay_key_bank']?>">

</td></tr>

<tr><td class="szfupaydiv_bank">

數支付 HashId：<input name="szfupay_key_bank" id="szfupay_key_bank" type="text" value="<?=$datalist['szfupay_key_bank']?>">&nbsp;&nbsp;

數支付 HashKey：<input name="szfupay_shop_id_bank" id="szfupay_shop_id_bank" type="text" value="<?=$datalist['szfupay_shop_id_bank']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="antdiv_bank">

使用者名稱：<input name="ant_username_bank" id="ant_username_bank" type="text" value="<?=$datalist['ant_username_bank']?>">&nbsp;&nbsp;

介接 HashKey：<input name="ant_hashkey_bank" id="ant_hashkey_bank" type="text" value="<?=$datalist['ant_hashkey_bank']?>">&nbsp;&nbsp;

介接 HashIV：<input name="ant_hashiv_bank" id="ant_hashiv_bank" type="text" value="<?=$datalist['ant_hashiv_bank']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="nodiv_bank">
	<label style="color:red; font-weight:800; ">注意：尚未選擇銀行轉帳金流</label>
</td></tr>

<tr><td>
    銀行轉帳金流環境：<input type="radio" name="gstats_bank" value="1"<?if($gstats_bank == '1') echo " checked"?>> 正式環境 &nbsp;&nbsp;
               <input type="radio" name="gstats_bank" value="0"<?if($gstats_bank != '1') echo " checked"?>> 模擬環境
</td></tr>

<tr><td>超商金流服務：<input type="radio" name="pay_cp2" value="ecpay"<?if($datalist['pay_cp2'] == 'ecpay') echo " checked"?>> 綠界 &nbsp;&nbsp;

	<input type="radio" name="pay_cp2" value="ebpay"<?if($datalist['pay_cp2'] == 'ebpay') echo " checked"?>> 藍新&nbsp;&nbsp;

	<input type="radio" name="pay_cp2" value="gomypay"<?if($datalist['pay_cp2'] == 'gomypay') echo " checked"?>> 萬事達&nbsp;&nbsp;

	<input type="radio" name="pay_cp2" value="smilepay"<?if($datalist['pay_cp2'] == 'smilepay') echo " checked"?>> 速買配&nbsp;&nbsp;

	<input type="radio" name="pay_cp2" value="funpoint"<?if($datalist['pay_cp2'] == 'funpoint') echo " checked"?>> 歐買尬&nbsp;&nbsp;

	<input type="radio" name="pay_cp2" value="szfu"<?if($datalist['pay_cp2'] == 'szfu') echo " checked"?>> 數支付

	<input type="radio" name="pay_cp2" value="opay"<?if($datalist['pay_cp2'] == 'opay') echo " checked"?>> 歐付寶

</td></tr>

<tr><td class="normaldiv2">

特店編號：<input name="MerchantID2" id="MerchantID2" type="text" value="<?=$datalist['MerchantID2']?>">&nbsp;&nbsp;

介接 HashKey：<input name="HashKey2" id="HashKey2" type="text" value="<?=$datalist['HashKey2']?>">&nbsp;&nbsp;

介接 HashIV：<input name="HashIV2" id="HashIV2" type="text" value="<?=$datalist['HashIV2']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="pchomediv2">

支付連 APP_ID：<input name="pchome_app_id2 id="pchome_app_id2" type="text" value="<?=$datalist['pchome_app_id2']?>">&nbsp;&nbsp;

支付連 SECRET_CODE：<input name="pchome_secret_code2" id="pchome_secret_code2" type="text" value="<?=$datalist['pchome_secret_code2']?>">

</td></tr>

<tr><td class="gomypaydiv2">

	Gomypay 商店代號：<input name="gomypay_shop_id2" id="gomypay_shop_id2" type="text" value="<?=$datalist['gomypay_shop_id2']?>">&nbsp;&nbsp;

    Gomypay 交易驗證碼：<input name="gomypay_key2" id="gomypay_key2" type="text" value="<?=$datalist['gomypay_key2']?>">

</td></tr>

<tr><td class="smilepaydiv2">

    速買配 商家代號：<input name="smilepay_shop_id2" id="smilepay_shop_id2" type="text" value="<?=$datalist['smilepay_shop_id2']?>">&nbsp;&nbsp;

    速買配 檢查碼 Verify_key：<input name="smilepay_key2" id="smilepay_key2" type="text" value="<?=$datalist['smilepay_key2']?>">

</td></tr>

<tr><td class="szfupaydiv2">

數支付 HashId：<input name="szfupay_key2" id="szfupay_key2" type="text" value="<?=$datalist['szfupay_key2']?>">&nbsp;&nbsp;

數支付 HashKey：<input name="szfupay_shop_id2" id="szfupay_shop_id2" type="text" value="<?=$datalist['szfupay_shop_id2']?>">&nbsp;&nbsp;

</td></tr>

<tr><td>
    其他金流環境：<input type="radio" name="gstats2" value="1"<?if($gstats2 == 1) echo " checked"?>> 正式環境 &nbsp;&nbsp;
               <input type="radio" name="gstats2" value="0"<?if($gstats2 != 1) echo " checked"?>> 模擬環境
</td></tr>
<tr><td>信用卡金流服務：

	<input type="radio" name="pay_cp" value="pchome"<?if($datalist['pay_cp'] == 'pchome') echo " checked"?>> 支付連&nbsp;&nbsp;

	<input type="radio" name="pay_cp" value="ecpay"<?if($datalist['pay_cp'] == 'ecpay') echo " checked"?>> 綠界&nbsp;&nbsp;

	<!-- <input type="radio" name="pay_cp" value="ebpay"<?if($datalist['pay_cp'] == 'ebpay') echo " checked"?>> 藍新&nbsp;&nbsp; -->

	<input type="radio" name="pay_cp" value="gomypay"<?if($datalist['pay_cp'] == 'gomypay') echo " checked"?>> 萬事達&nbsp;&nbsp;

	<!-- <input type="radio" name="pay_cp" value="smilepay"<?if($datalist['pay_cp'] == 'smilepay') echo " checked"?>> 速買配&nbsp;&nbsp; -->

	<input type="radio" name="pay_cp" value="funpoint"<?if($datalist['pay_cp'] == 'funpoint') echo " checked"?>> 歐買尬

	<input type="radio" name="pay_cp" value="opay"<?if($datalist['pay_cp'] == 'opay') echo " checked"?>> 歐付寶

	<input type="radio" name="pay_cp" value="no"<?if($datalist['pay_cp'] == 'no') echo " checked"?>> 無
</td></tr>

<tr><td class="normaldiv">

特店編號：<input name="MerchantID" id="MerchantID" type="text" value="<?=$datalist['MerchantID']?>">&nbsp;&nbsp;

介接 HashKey：<input name="HashKey" id="HashKey" type="text" value="<?=$datalist['HashKey']?>">&nbsp;&nbsp;

介接 HashIV：<input name="HashIV" id="HashIV" type="text" value="<?=$datalist['HashIV']?>">&nbsp;&nbsp;

</td></tr>

<tr><td class="pchomediv">

支付連 APP_ID：<input name="pchome_app_id" id="pchome_app_id" type="text" value="<?=$datalist['pchome_app_id']?>">&nbsp;&nbsp;

支付連 SECRET_CODE：<input name="pchome_secret_code" id="pchome_secret_code" type="text" value="<?=$datalist['pchome_secret_code']?>">

</td></tr>

<tr><td class="gomypaydiv">

	Gomypay 商店代號：<input name="gomypay_shop_id" id="gomypay_shop_id" type="text" value="<?=$datalist['gomypay_shop_id']?>">&nbsp;&nbsp;

    Gomypay 交易驗證碼：<input name="gomypay_key" id="gomypay_key" type="text" value="<?=$datalist['gomypay_key']?>">

</td></tr>

<tr><td class="smilepaydiv">

    速買配 商家代號：<input name="smilepay_shop_id" id="smilepay_shop_id" type="text" value="<?=$datalist['smilepay_shop_id']?>">&nbsp;&nbsp;

    速買配 檢查碼 Verify_key：<input name="smilepay_key" id="smilepay_key" type="text" value="<?=$datalist['smilepay_key']?>">

</td></tr>

<tr><td class="nodiv">
	<label style="color:red; font-weight:800; ">注意：尚未選擇信用卡金流</label>
</td></tr>

<tr><td>

    信用卡環境：<input type="radio" name="gstats" value="1"<?if($gstats == 1) echo " checked"?>> 正式環境 &nbsp;&nbsp;

               <input type="radio" name="gstats" value="0"<?if($gstats != 1) echo " checked"?>> 模擬環境

</td></tr>

<tr>
	<td>
		<label>【最高金額】</label>
		<label>信用卡:</label>
		<input type="number" name="max_credit" id="max_credit" value="<?=$datalist['max_credit'] == 0 ? '' : $datalist['max_credit']?>">
		<label>超商代碼:</label>
		<input type="number" name="max_store" id="max_store" value="<?=$datalist['max_store'] == 0 ? '' : $datalist['max_store']?>">
		<label>銀行轉帳:</label>
		<input type="number" name="max_bank" id="max_bank" value="<?=$datalist['max_bank'] == 0 ? '' : $datalist['max_bank']?>">
	</td>
</tr>

<tr><td style="background:#6666ff;color:white;text-align:center;">派獎設定</td></tr>
<tr><td>
    資料表名稱：<input name="table_name" id="table_name" type="text" value="<?=$datalist['table_name']?>">&nbsp;&nbsp;
    帳號欄位：<input name="account_field" id="account_field" type="text" value="<?=$datalist['account_field']?>">
    道具編號：<input name="item_field" id="item_field" type="text" value="<?=$datalist['item_field']?>">&nbsp;&nbsp;
    道具名稱：<input name="item_name_field" id="item_name_field" type="text" value="<?=$datalist['item_name_field']?>">&nbsp;&nbsp;
    數量欄位：<input name="quantity_field" id="quantity_field" type="text" value="<?=$datalist['quantity_field']?>">
</td></tr>
<tr><td>
    <button type="button" onclick="addField()" style="background-color: #28a745; color: white; border: none; padding: 8px 15px; cursor: pointer; margin-bottom: 10px;">新增欄位</button>
    <div id="dynamic_fields_container" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background-color: #fafafa; display: none;">
        <div id="dynamic_fields">
        </div>
    </div>
</td></tr>

  </tbody>

	</table>

 					  

		  <div align="center"> 

          <?if($_REQUEST["an"] != "") {?>

          <input type="submit" name="Submit" class="btn btn-info btn-sm" value="確定修改">

          <input type="hidden" id="an" name="an" value="<?=$_REQUEST["an"]?>">

		  <?} else {?>

		  <input type="submit" name="Submit" class="btn btn-info btn-sm" value="確定新增">

		  <?}?>

        </div>

</form>

	

</div>



						</div>

						<!-- /panel content -->





					</div>



				</div>

			</section>

			<!-- /MIDDLE -->



<?down_html()?>



<script type="text/javascript" src="assets/js/jquery.fileupload.js"></script>

<script type="text/javascript">

    var bankFundsData = <?=json_encode($bank_funds_js ? $bank_funds_js : [])?>;         // 銀行金流服務資料
    var dynamicFieldsData = <?=json_encode($dynamic_fields ? $dynamic_fields : [])?>;    // 派獎動態欄位資料

$(function() {  

	$("input[name=pay_cp]").on("change", function() {

		pay_check();

	});

	pay_check();



	$("input[name=pay_cp2]").on("change", function() {

		pay_check2();

	});

	pay_check2();

    $("input[name=pay_bank]").on("change", function() {
		pay_check_bank();
	});

	pay_check_bank();

	loadcustombgdiv();

	$(".fileupload").each(function() {



var $this = $(this), $thisid = $this.attr("id"), $progress = $this.closest("div").find(".progress");    	



$ffileu = $this.fileupload({

url: "server_add.php?st=upload&an=<?=$an?>",

type: "POST",

dropZone: $this,

dataType: 'html',        

done: function (e, data) {

	switch(data.jqXHR.responseText) {

		case "uploadfix":

		$progress.find(".progress-bar").css("width", "0px").stop().parent().hide();

		loadcustombgdiv();

					

		break;



		default:

	 $progress.find(".progress-bar").css("width", "0px").stop().parent().hide();

	 alert(data.jqXHR.responseText);

		break;

	}

},       

progressall: function (e, data) {        	

	var progress = parseInt(data.loaded / data.total * 100, 10);            

	$progress.show().find(".progress-bar").css(

		'width',

		progress + '%'

	);

},

add: function(e, data) {        	



	  data.url = "server_add.php?st=upload&an=<?=$an?>";

	

	data.submit();

}



}).prop('disabled', !$.support.fileInput)

.parent().addClass($.support.fileInput ? undefined : 'disabled');



});   

});

function pay_check() {

  v = $("input[name=pay_cp]:checked").val();

  $(".normaldiv").hide();

  $(".gomypaydiv").hide();

  $(".pchomediv").hide();

  $(".smilepaydiv").hide();

  $(".nodiv").hide();

  switch(v) {

	  case "pchome":

          $(".pchomediv").show();

	  break;

	  case "gomypay":

		  $(".gomypaydiv").show();

	  break;

	  case "smilepay":

		  $(".smilepaydiv").show();

	  break;

	  case "no":

		  $(".nodiv").show();

	  break;

	  default:

	  $(".normaldiv").show();	  

	  break;

  } 

  

}

function pay_check2() {

  v = $("input[name=pay_cp2]:checked").val();

  $(".normaldiv2").hide();

  $(".gomypaydiv2").hide();

  $(".pchomediv2").hide();

  $(".smilepaydiv2").hide();
  
  $(".szfupaydiv2").hide();

  switch(v) {

	  case "pchome":

          $(".pchomediv2").show();

	  break;

	  case "gomypay":

		  $(".gomypaydiv2").show();

	  break;

	  case "smilepay":

		  $(".smilepaydiv2").show();

	  break;
	  case "szfu":

		  $(".szfupaydiv2").show();

	  break;

	  default:

	  $(".normaldiv2").show();	  

	  break;

  } 

  

}

function pay_check_bank() {

  v = $("input[name=pay_bank]:checked").val();
  
  console.log('pay_check_bank() called with value:', v);

  // 清空所有欄位
  clearBankFields();

  $(".ecpaydiv_bank").hide();
  $(".ebpaydiv_bank").hide();
  $(".funpointdiv_bank").hide();

  $(".gomypaydiv_bank").hide();

  $(".pchomediv_bank").hide();

  $(".smilepaydiv_bank").hide();
  
  $(".szfupaydiv_bank").hide();
  
  $(".antdiv_bank").hide();

  $(".nodiv_bank").hide(); 
  
  // 載入對應金流服務的資料
  if (v && v !== 'no' && bankFundsData[v]) {
      var fundData = bankFundsData[v];
      console.log('Loading fund data for', v, ':', fundData);
      
      switch(v) {
          case "ecpay":
            $("#ecpay_merchant_id_bank").val(fundData.merchant_id || '');
            $("#ecpay_hashkey_bank").val(fundData.hashkey || '');
            $("#ecpay_hashiv_bank").val(fundData.hashiv || '');
            $(".ecpaydiv_bank").show();
            break;

          case "ebpay":
            $("#ebpay_merchant_id_bank").val(fundData.merchant_id || '');
            $("#ebpay_hashkey_bank").val(fundData.hashkey || '');
            $("#ebpay_hashiv_bank").val(fundData.hashiv || '');
            $(".ebpaydiv_bank").show();
            break;

          case "funpoint":
            $("#funpoint_merchant_id_bank").val(fundData.merchant_id || '');
            $("#funpoint_hashkey_bank").val(fundData.hashkey || '');
            $("#funpoint_hashiv_bank").val(fundData.hashiv || '');
            $(".funpointdiv_bank").show();
            break;
              
          case "gomypay":
              $("#gomypay_shop_id_bank").val(fundData.merchant_id || '');
              $("#gomypay_key_bank").val(fundData.verify_key || '');
              $(".gomypaydiv_bank").show();
              break;
              
          case "smilepay":
              $("#smilepay_shop_id_bank").val(fundData.merchant_id || '');
              $("#smilepay_key_bank").val(fundData.verify_key || '');
              $(".smilepaydiv_bank").show();
              break;
              
          case "szfu":
              $("#szfupay_shop_id_bank").val(fundData.merchant_id || '');
              $("#szfupay_key_bank").val(fundData.verify_key || '');
              $(".szfupaydiv_bank").show();
              break;
              
          case "ant":
              $("#ant_username_bank").val(fundData.username || fundData.merchant_id || '');
              $("#ant_hashkey_bank").val(fundData.hashkey || '');
              $("#ant_hashiv_bank").val(fundData.hashiv || '');
              $(".antdiv_bank").show();
              break;
              
          case "pchome":
              $("#pchome_app_id_bank").val(fundData.merchant_id || '');
              $("#pchome_secret_code_bank").val(fundData.verify_key || '');
              $(".pchomediv_bank").show();
              break;

          case "no":

                $(".nodiv_bank").show();

            break;
      }
  } else {
      // 顯示預設的欄位區域，但不載入資料
      switch(v) {
        case "ecpay":
            $(".ecpaydiv_bank").show();
            break;

          case "ebpay":
            $(".ebpaydiv_bank").show();
            break;

          case "funpoint":
            $(".funpointdiv_bank").show();
            break;

          case "pchome":
            $(".pchomediv_bank").show();
            break;

          case "gomypay":
              $(".gomypaydiv_bank").show();
              break;

          case "smilepay":
              $(".smilepaydiv_bank").show();
              break;
              
          case "szfu":
              $(".szfupaydiv_bank").show();
              break;
              
          case "ant":
              $(".antdiv_bank").show();
              break;
              
          case "no":
              // 無需顯示任何額外欄位
              $(".nodiv_bank").show();
              break;
      }
  }
}

function clearBankFields() {
    // 清空所有銀行金流相關的輸入欄位
    $("#ecpay_merchant_id_bank").val('');
    $("#ecpay_hashkey_bank").val('');
    $("#ecpay_hashiv_bank").val('');
    $("#ebpay_merchant_id_bank").val('');
    $("#ebpay_hashkey_bank").val('');
    $("#ebpay_hashiv_bank").val('');
    $("#funpoint_merchant_id_bank").val('');
    $("#funpoint_hashkey_bank").val('');
    $("#funpoint_hashiv_bank").val('');
    $("#ant_username_bank").val('');
    $("#ant_hashkey_bank").val('');
    $("#ant_hashiv_bank").val('');
    $("#gomypay_shop_id_bank").val('');
    $("#gomypay_key_bank").val('');
    $("#smilepay_shop_id_bank").val('');
    $("#smilepay_key_bank").val('');
    $("#szfupay_shop_id_bank").val('');
    $("#szfupay_key_bank").val('');
    $("#pchome_app_id_bank").val('');
    $("#pchome_secret_code_bank").val('');
}

function loadcustombgdiv() {

 $.ajax({

  method: "POST",

  url: "server_add.php",

  data: { st: "readcustombg", an: "<?=$an?>" }

}).done(function( msg ) {

  if(msg) {

	  $newimg = $("<img></img>");

	  $newimg.attr("src", "../assets/images/custombg/"+msg).attr("width", "auto").attr("height", 150);

	  $("#custombgdiv").html($newimg);

  }

});



}

function removecustombg() {

$.ajax({

  method: "POST",

  url: "server_add.php",

  data: { st: "clearcustombg", an: "<?=$an?>" }

}).done(function( msg ) {

  if(msg == 1) {

	  $("#custombgdiv").html("");

  }

});

}

function test_connect() {

	if(!$("#ip").val()) {

		alert("要進行連線測試必須填寫資料庫位置。");

		$("#ip").focus();

		return false;

	}

	if(!$("#port").val()) {

		alert("要進行連線測試必須填寫資料庫端口。");

		$("#port").focus();

		return false;

	}

	if(!$("#dbname").val()) {

		alert("要進行連線測試必須填寫資料庫名稱。");

		$("#dbname").focus();

		return false;

	}

	if(!$("#user").val()) {

		alert("要進行連線測試必須填寫資料庫帳號。");

		$("#user").focus();

		return false;

	}

	if(!$("#pass").val()) {

		alert("要進行連線測試必須填寫資料庫密碼。");

		$("#pass").focus();

		return false;

	}

	var $w = screen.width/4;

	var $h = screen.height/4;

	var $left = (screen.width/2)-($w/2);

    var $top = (screen.height/2)-($h/2);

    var $paytable = $("input[name=paytable]:checked").val();

	if(!$paytable) $paytable = "shop_user";

	var $testconnectstr = "ip="+$("#ip").val()+"&port="+$("#port").val()+"&dbname="+$("#dbname").val()+"&user="+$("#user").val()+"&pass="+$("#pass").val()+"&tb="+$paytable;



	window.open('server_test_connect.php?'+$testconnectstr,'test_connect','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+$w+', height='+$h+', top='+$top+', left='+$left);

	

	

}

// 以下程式碼會在文件載入完成後執行，並監聽 paytable 的 radio 按鈕變化
$(document).ready(function() {
    // 定義一個函式來根據 paytable 的值啟用或停用 paytable_custom 欄位
    function togglePaytableCustom() {
        // 取得目前被選取的 paytable radio 的值
        var paytableValue = $("input[name='paytable']:checked").val();
        // 如果選取的值不是 custom，則將 paytable_custom 欄位設為 readonly
        if (paytableValue !== "custom") {
            // 設定 paytable_custom 欄位為唯讀（readonly）
            $("#paytable_custom").prop("readonly", true);
        } else {
            // 如果選取的是 custom，則移除 readonly 屬性，允許編輯
            $("#paytable_custom").prop("readonly", false);
        }
    }

    // 初始載入時執行一次，確保狀態正確
    togglePaytableCustom();

    // 當 paytable radio 按鈕變化時，重新檢查並設定 paytable_custom 欄位狀態
    $("input[name='paytable']").on("change", function() {
        togglePaytableCustom();
    });

    // 當 paytable_custom 欄位被點擊時，檢查是否為唯讀狀態
    $("#paytable_custom").on("focus click", function(e) {
        // 如果 paytable_custom 欄位為 readonly，顯示提示訊息
        if ($(this).prop("readonly")) {
            // 顯示提示訊息，告知使用者需先切換選項
            alert("請先將資料表選項切換為「其他」，才能自訂資料表名稱。");
            // 阻止進一步的操作（例如游標進入欄位）
            $(this).blur();
            // 阻止事件的預設行為
            e.preventDefault();
            return false;
        }
    });
});


</script>