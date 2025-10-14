<?
include("../include.php");

/* 有三支Form會用到 */
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action){
    case 'searching':
        searching();
        break;
    case 'open':
        open();
        break;
    case 'close':
        close();
        break;
    case 'delete':
        delete();
        break;
    case 'recycle':
        recycle();
        break;
    case 'cancel_delete':
        cancel_delete();
        break;
    case 'check_delete':
        check_delete();
        break;
}

function searching(){
    $data = post_data();
    $offset = $data['offset'];
    $limit_row = 20;

    $pdo = openpdo();
    $sql_str = 'SELECT count(auton) as t FROM shareuser';
    $numsrow = $pdo->query($sql_str)->fetch()['t'];

    $pagestr = pages($numsrow, $offset, $limit_row);    /* 分頁HTML */

    /* 舊的搜尋方式，管理數 & 贊助成功有更新新的方法 */
    $sql_str = "SELECT *, (select count(auton) from shareuser_server where a.uid = uid) as total, (select count(auton) from shareuser_server2 where a.uid = uid) as total2, (select sum(rmoney) from servers_log where a.uid = shareid and stats=1) as sponsor FROM shareuser as a WHERE in_delete='0' order by auton desc limit ".$offset.", ".$limit_row;
    // echo $sql_str; die();
    $query = $pdo->query($sql_str);
    $query->execute();
    $datalist = $query->fetchAll();  

    foreach ($datalist as $_key => $_val){
        /* 分享數重新搜尋 */
        $uid = $_val['uid'];
        $sql_str = "SELECT * FROM shareuser_server WHERE uid = '$uid'";
        $query = $pdo->query($sql_str);
        $query->execute();
        $shareuser_server2 = $query->fetchAll();
        
        $share_count = 0;
        $server_list = array();     // 分享的伺服器
        foreach ($shareuser_server2 as $ss_val){
            $server_id = $ss_val['serverid'];
            $sql_str = "SELECT * FROM servers WHERE id = '$server_id'";
            $query = $pdo->query($sql_str);
            $query->execute();
            $servers = $query->fetch();

            if (!empty($servers)){  // 有存在目前的伺服器清單才計算管理數
                $share_count += 1;
                array_push($server_list, $servers['id']);
            }
        }
        $datalist[$_key]['share_count'] = $share_count;
        /* End 分享數重新搜尋 */

        /* 管理數重新搜尋 */
        $uid = $_val['uid'];
        $sql_str = "SELECT * FROM shareuser_server2 WHERE uid = '$uid'";
        $query = $pdo->query($sql_str);
        $query->execute();
        $shareuser_server2 = $query->fetchAll();
        
        $manage_count = 0;
        $server_list = array();     // 管理的伺服器
        $serverData = array();
        foreach ($shareuser_server2 as $ss_val){
            $server_id = $ss_val['serverid'];
            $sql_str = "SELECT * FROM servers WHERE id = '$server_id'";
            $query = $pdo->query($sql_str);
            $query->execute();
            $servers = $query->fetch();

            if (!empty($servers)){  // 有存在目前的伺服器清單才計算管理數
                $manage_count += 1;
                array_push($server_list, $servers['id']);
                array_push($serverData, ['code' => $servers['id'], 'name' => $servers['names']]);
            }
        }
        $datalist[$_key]['manage_count'] = $manage_count;
        $datalist[$_key]['manage_server'] = $serverData;
        /* End 管理數重新搜尋 */

        /* 贊助成功重新搜尋 */
        $sponsor = 0;
        foreach ($server_list as $sl_val){
            $sql_str = "SELECT SUM(rmoney) as amount FROM servers_log WHERE serverid = '$sl_val' AND stats = 1";
            // echo $sql_str; die();
            $query = $pdo->query($sql_str);
            $query->execute();
            $servers_log = $query->fetch();
            $sponsor += $servers_log['amount'];
        }
        /* End 贊助成功重新搜尋 */

        $datalist[$_key]['sponsor'] = $sponsor;
        // $datalist[$_key]['sponsor'] = ($_val['sponsor']) ? $_val['sponsor'] : 0;
        unset($datalist[$_key]['upd']);
    }

    // 加入服主管理的伺服器
    

    $sql_str = "SELECT * FROM shareuser_server2 WHERE uid = 'skytest'";
    $query = $pdo->query($sql_str);
    $query->execute();
    $data = $query->fetchAll();

    $result = array(
        'success' => True,
        'page' => $pagestr,
        'data' => $datalist,
        'test' => $data
    );

    echo json_encode($result);
}

function recycle(){
    $data = post_data();
    $offset = $data['offset'];
    $limit_row = 20;

    $pdo = openpdo();
    $sql_str = 'SELECT count(auton) as t FROM shareuser WHERE in_delete=1';
    $numsrow = $pdo->query($sql_str)->fetch()['t'];

    $pagestr = pages($numsrow, $offset, $limit_row);    /* 分頁HTML */

    $sql_str = "SELECT *, (select count(auton) from shareuser_server where a.uid = uid) as total, (select count(auton) from shareuser_server2 where a.uid = uid) as total2, (select sum(rmoney) from servers_log where a.uid = shareid and stats=1) as sponsor FROM shareuser as a WHERE in_delete='1' order by auton desc limit ".$offset.", ".$limit_row;
    $query = $pdo->query($sql_str);
    $query->execute();
    $datalist = $query->fetchAll();  

    foreach ($datalist as $_key => $val){
        $datalist[$_key]['sponsor'] = ($val['sponsor']) ? $val['sponsor'] : 0;
        unset($datalist[$_key]['upd']);
    }

    $result = array(
        'success' => True,
        'page' => $pagestr,
        'data' => $datalist
    );

    echo json_encode($result);
}

function open(){
    $pdo = openpdo();
    $query = $pdo->prepare("update shareuser set stats = 0 where auton=:an");    
    $query->execute(array(':an' => $_POST["id"]));

    $result = array(
        'success' => True,
        'msg' => '該帳號已開啟'
    );

    echo json_encode($result);
}

function close(){
    $pdo = openpdo();
    $query = $pdo->prepare("update shareuser set stats = -1 where auton=:an");    
    $query->execute(array(':an' => $_POST["id"]));

    $result = array(
        'success' => True,
        'msg' => '該帳號已關閉'
    );

    echo json_encode($result);
}

function delete(){
    $now_time = date("Y-m-d H:i:s");
    $pdo = openpdo();
    $query = $pdo->prepare("update shareuser set in_delete = 1, delete_time = :now_time where auton=:an");    
    $query->execute(array(':an' => $_POST["id"], ':now_time' => $now_time));

    $result = array(
        'success' => True,
        'msg' => '該帳號已刪除'
    );

    echo json_encode($result);
}

function cancel_delete(){
    $pdo = openpdo();
    $query = $pdo->prepare("update shareuser set in_delete = 0 where auton=:an");    
    $query->execute(array(':an' => $_POST["id"]));

    $result = array(
        'success' => True,
        'msg' => '該帳號已復原'
    );

    echo json_encode($result);
}

function check_delete(){
    $pdo = openpdo();
    $sql_str = "SELECT * FROM shareuser WHERE in_delete=1";
    $data = $pdo->query($sql_str)->fetchAll();

    foreach($data as $_key => $_val){
        $del_time = $_val['delete_time'];
        $time_diff = time_diff($del_time);
        if ($time_diff > 30){
            $query = $pdo->prepare("DELETE FROM shareuser WHERE auton=:an");    
            $query->execute(array(':an' => $_val["auton"]));
            echo $time_diff;
        }
    }
}

function time_diff($time_str){
    $timestamp = strtotime($time_str);
    $current_time = time();
    $diff_day = ($current_time - $timestamp) / (60 * 60 * 24);
    return $diff_day;
}

function post_data(){
    foreach ($_POST as $key => $value){
        if (isset($_POST[$key])){
            $data[$key] = $value;
        }
    }
    return $data;
}