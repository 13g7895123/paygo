<?php
include("myadm/include.php");
$endstr = '1|OK';
$MerchantID = $_REQUEST["MerchantID"];
$MerchantTradeNo = $_REQUEST["MerchantTradeNo"];
$RtnCode = $_REQUEST["RtnCode"];
$RtnMsg = $_REQUEST["RtnMsg"];
$rCheckMacValue = $_REQUEST["CheckMacValue"];
$TradeAmt = $_REQUEST["TradeAmt"];
$PaymentDate = $_REQUEST["PaymentDate"];
$PaymentTypeChargeFee = $_REQUEST["PaymentTypeChargeFee"];
$pdo = openpdo();

try {
    $pdo->beginTransaction();
    $query    = $pdo->prepare("SELECT * FROM servers_log where orderid=? for update");
    $query->execute(array($MerchantTradeNo));
    if (!$datalist = $query->fetch()) {
        die("0");
    }

    $foran = $datalist["foran"];
    if ($datalist["stats"] != 0 && $_POST["mockpay"] != 1) {
        die("0");
    }
    if ($RtnCode == 1) {
        $rstat = ($RtnMsg == '模擬付款成功') ? 3 : 1;
    } else {
        $rstat = 2;
    }
    $qud    = $pdo->prepare("update servers_log set stats=?, hmoney=?, paytimes=?, rmoney=?, rCheckMacValue=?,RtnCode=?,RtnMsg=? where orderid=?");
    $rr = $qud->execute(array($rstat, $PaymentTypeChargeFee, $PaymentDate, $TradeAmt, $rCheckMacValue, $RtnCode, $RtnMsg, $MerchantTradeNo));
    $rstat = ($rstat == 3) ? 1 : $rstat;

    if ($rr == 1 && $rstat == 1) {
        $qquerylog = $pdo->prepare("SELECT * FROM servers_log where orderid=?");
        $qquerylog->execute(array($MerchantTradeNo));
        $ddlog = $qquerylog->fetch();
        $money = $ddlog["money"];
        $bmoney = $ddlog["bmoney"];
        $gameid = $ddlog["gameid"];
        $paytype = $ddlog["paytype"];
  
        $qquery = $pdo->prepare("SELECT * FROM servers where auton=?");
        $qquery->execute(array($foran));
        if (!$dd = $qquery->fetch()) {
            $qud = $pdo->prepare("update servers_log set errmsg='找尋伺服器資料庫時發生錯誤' where orderid=?");
            $qud->execute(array($MerchantTradeNo));
            die("0");
        }
        $ip = $dd["db_ip"];
        $port = $dd["db_port"];
        $dbname = $dd["db_name"];
        $user = $dd["db_user"];
        $pass = $dd["db_pass"];
        $pid = $dd["db_pid"];

        $bonusid = $dd["db_bonusid"];
        $bonusrate = $dd["db_bonusrate"];       
        
        // 取得資料表名稱
        $paytable = $dd["paytable"];
        $paytable = ($paytable == 'custom') ? $dd["paytable_custom"] : $paytable;

        $gamepdo = opengamepdo($ip, $port, $dbname, $user, $pass);

        if($dd["paytable"] == "ezpay") {
            // ezpay 處理贊助金
            $gamei = array(':amount' => $bmoney,':payname' => $gameid);
            $gameq   = $gamepdo->prepare("INSERT INTO ezpay (amount, payname, state) VALUES(:amount,:payname, 1)");
            if(!$rr = $gameq->execute($gamei)) {
              $qud = $pdo->prepare("update servers_log set errmsg='存入贊助幣時發生錯誤' where orderid=?");
              $qud->execute(array($MerchantTradeNo));
              die("0");
            }
            $pdo->commit();
            echo $endstr;
            exit;            
        }

        //處理贊助金
        if ($paytype == 5) {
            $card = 1;
        } else {
            $card = 0;
        }
        $gamei = array(':p_id' => $pid,':p_name' => '贊助幣',':count' => $bmoney,':account' => $gameid,':r_count' => $money, ':card' => $card);
        $gameq   = $gamepdo->prepare("INSERT INTO $paytable (p_id,p_name, count, account,r_count, card) VALUES(:p_id,:p_name,:count,:account,:r_count, :card)");
        if (!$gameq->execute($gamei)) {
            $qud = $pdo->prepare("update servers_log set errmsg='存入贊助幣時發生錯誤' where orderid=?");
            $qud->execute(array($MerchantTradeNo));
            die("0");
        }

        // 處理紅利幣
        if (!empty($bonusid) && $bonusrate > 0) {
            $bonusmoney = $money * ($bonusrate / 100);
            $gamei = array(':p_id' => $bonusid,':p_name' => '紅利幣',':count' => $bonusmoney,':account' => $gameid,':r_count' => $money);
            $gameq   = $gamepdo->prepare("INSERT INTO $paytable (p_id,p_name, count, account,r_count) VALUES(:p_id,:p_name,:count,:account,:r_count)");
            if (!$gameq->execute($gamei)) {
                $qud = $pdo->prepare("update servers_log set errmsg='存入紅利幣時發生錯誤' where orderid=?");
                $qud->execute(array($MerchantTradeNo));
                die("0");
            }
        }
  

        //處理滿額贈禮
        //是否開啟
        $gift1 = 0;
        $qquerylog1 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=1 and pid='stat'");
        $qquerylog1->execute(array($foran));
        if ($ddlog1 = $qquerylog1->fetch()) {
            if ($ddlog1["sizes"] == 1) {
                $gift1 = 1;
            }
        }
  
        if ($gift1 == 1) {  //如果有開啟才動作
            // 抓所有金額
            $qquerylog1 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=1 and not pid='stat' for update");
            $qquerylog1->execute(array($foran));
            if ($ddlog1 = $qquerylog1->fetchALL()) {
                foreach ($ddlog1 as $ddl1) {
                    $m1 = $ddl1["m1"];
                    $m2 = $ddl1["m2"];
                    $pid = $ddl1["pid"];
                    $sizes = $ddl1["sizes"];
                    if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                        $gamepdo_add = $gamepdo->prepare("INSERT INTO $paytable (p_id,p_name, count, account) VALUES(?,?,?,?)");
                        $gamepdo_add->execute(array($pid, '滿額贈禮', $sizes, $gameid));
                    }
                }
            }
        }

        //處理首購禮
        //是否開啟
        $gift2 = 0;
        $qquerylog2 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=2 and pid='stat'");
        $qquerylog2->execute(array($foran));
        if ($ddlog2 = $qquerylog2->fetch()) {
            if ($ddlog2["sizes"] == 1) {
                $gift2 = 1;
            }
        }
  
        if ($gift2 == 1) {  //如果有開啟才動作
            // 確認是不是首購
            $gamepdo_query = $gamepdo->prepare("select count(*) from $paytable where account=? and p_name='贊助幣'");
            $gamepdo_query->execute(array($gameid));
    
            if ($gamepdo_query->fetchColumn() == 1) { // 是首購才動作
                $qquerylog2 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=2 and not pid='stat' for update");
                $qquerylog2->execute(array($foran));
                if ($ddlog2 = $qquerylog2->fetchALL()) {
                    foreach ($ddlog2 as $ddl2) {
                        $m1 = $ddl2["m1"];
                        $m2 = $ddl2["m2"];
                        $pid = $ddl2["pid"];
                        $sizes = $ddl2["sizes"];
                        if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                            $gamepdo_add = $gamepdo->prepare("INSERT INTO $paytable (p_id,p_name, count, account) VALUES(?,?,?,?)");
                            $gamepdo_add->execute(array($pid, '首購禮', $sizes, $gameid));
                        }
                    }
                }
            }
        }
  
        //處理活動首購禮
        //是否開啟
        $gift4 = 0;
        $gift4time = 0;
        $qquerylog4 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=4 and pid='stat'");
        $qquerylog4->execute(array($foran));
        if ($ddlog4 = $qquerylog4->fetch()) {
            if ($ddlog4["sizes"] == 1) {
                $gift4 = 1;
            }
        }
  
        if ($gift4 == 1) {  //如果有開啟才動作
            // 確認是不是在活動時間內
            $qquerylog4t = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=4 and pid in ('time1', 'time2')");
            $qquerylog4t->execute(array($foran));
            if ($ddlog4tt = $qquerylog4t->fetchAll()) {
                foreach ($ddlog4tt as $ddlog4t) {
                    if ($ddlog4t["pid"] == "time1") {
                        $time41 = $ddlog4t["dd"];
                    }
                    if ($ddlog4t["pid"] == "time2") {
                        $time42 = $ddlog4t["dd"];
                    }
                }
            }
            if (strtotime($time41) !== false && strtotime($time42) !== false) {
                if (time() >= strtotime($time41) && time() <= strtotime($time42)) {
                    $gift4time = 1;
                }
            }

            // 活動時間開關
            if ($gift4time === 1) {
                // 確認是不是首購
                $gamepdo_query = $gamepdo->prepare("select count(*) from $paytable where account=? and p_name='贊助幣' and create_time between '$time41' and '$time42'");
                $gamepdo_query->execute(array($gameid));
    
                if ($gamepdo_query->fetchColumn() == 1) { // 是首購才動作
                    $qquerylog4 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=4 and not pid='stat' for update");
                    $qquerylog4->execute(array($foran));
                    if ($ddlog4 = $qquerylog4->fetchALL()) {
                        foreach ($ddlog4 as $ddl4) {
                            $m1 = $ddl4["m1"];
                            $m2 = $ddl4["m2"];
                            $pid = $ddl4["pid"];
                            $sizes = $ddl4["sizes"];
                            if ($money >= $m1 && $money <= $m2 && $sizes > 0) {
                                $gamepdo_add = $gamepdo->prepare("INSERT INTO $paytable (p_id,p_name, count, account) VALUES(?,?,?,?)");
                                $gamepdo_add->execute(array($pid, '活動首購禮', $sizes, $gameid));
                            }
                        }
                    }
                }
            }
        }

        //處理累積儲值
        //是否開啟
        $gift3 = 0;
        $qquerylog3 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=3 and pid='stat'");
        $qquerylog3->execute(array($foran));
        if ($ddlog3 = $qquerylog3->fetch()) {
            if ($ddlog3["sizes"] == 1) {
                $gift3 = 1;
            }
        }
  
        if ($gift3 == 1) {  //如果有開啟才動作
            // 統計累積儲值金額
            $gamepdo_query = $gamepdo->prepare("select sum(r_count) from $paytable where account=? and p_name='贊助幣'");
            $gamepdo_query->execute(array($gameid));
            $total_pay = $gamepdo_query->fetchColumn();
            if ($total_pay > 0) { // 儲值金額大於0才動作
                //所有累積儲值金額
                $qquerylog3 = $pdo->prepare("SELECT * FROM servers_gift where foran=? and types=3 and not pid='stat' for update");
                $qquerylog3->execute(array($foran));
                if ($ddlog3 = $qquerylog3->fetchALL()) {
                    foreach ($ddlog3 as $ddl3) {
                        $m1 = $ddl3["m1"];
                        $pid = $ddl3["pid"];
                        $sizes = $ddl3["sizes"];
                        if ($total_pay >= $m1 && $sizes > 0) {
                            $gamepdo_qq = $gamepdo->prepare("select count(*) from $paytable where account=? and p_name='累積儲值' and r_count=?");
                            $gamepdo_qq->execute(array($gameid, $m1));
                            if (!$gamepdo_qq->fetchColumn()) {
                                $gamepdo_add = $gamepdo->prepare("INSERT INTO $paytable (p_id,p_name, count, account, r_count) VALUES(?,?,?,?,?)");
                                $gamepdo_add->execute(array($pid, '累積儲值', $sizes, $gameid, $m1));
                            }
                        }
                    }
                }
            }
        }
    }
    if ($rr) {
        $pdo->commit();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    //file_put_contents('log.txt', 'aa:'.$e->getMessage()."\n");
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
echo $endstr;
exit;
