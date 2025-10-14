<?
include("../include.php");

/* 有三支Form會用到 */
$action = isset($_POST['action']) ? $_POST['action'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : $action;

switch ($action){
    case 'searching':
        searching();
        break;
    case 'delete':
        delete();
        break;
    case 'delete_recycle':
        delete_recycle();
        break;
    case 'test':
        test();
        break;
}

function searching(){
    $pdo = openpdo();
    $postData = post_data();    

    /* 取得server清單 */
    $servers = array();
    $query = $pdo->prepare("SELECT serverid FROM shareuser_server where uid=?");
    $query->execute([$postData['uid']]);
    if ($data = $query->fetchAll()) {
        foreach ($data as $_val) $servers[] = $_val["serverid"];
    }

    $servers2 = array();
    $query = $pdo->prepare("SELECT serverid FROM shareuser_server2 where uid=?");
    $query->execute([$postData['uid']]);
    if ($data = $query->fetchAll()) {
        foreach ($data as $_val) $servers2[] = $_val["serverid"];
    }

    $rstat = $postData["rstat"];
    $kword = $postData["keyword"];

    /* 組成sql */
    $sql = '';
    if (in_array($postData['serverid'], $servers2)){
        $sql = "serverid='{$postData['serverid']}'";
    }elseif (in_array($postData['serverid'], $servers)){
        if ($sql != ''){
            $sql .= " AND ";
        }
        $sql = "shareid='{$postData['uid']}' and serverid='{$postData['serverid']}'";
    }else{
        return False;
    }

    $where_sql = "{$sql} AND ";
    $where_sql .= ($postData['is_delete'] == '1') ? 'indel=1' : 'indel=0';
    if ($postData['keyword'] != ''){
        $kword = $postData['keyword'];
        $where_sql .= " and (orderid like '%$kword%' or forname like '%$kword%' or serverid like '%$kword%' or gameid like '%$kword%' or charid like '%$kword%')";
    }
    if ($postData['date_1'] != "" && $postData['date_2'] != "") {
        $where_sql .= " and (times between '".$postData['date_1']." 00:00' and '".$postData['date_2']." 23:59')";
    }
    if($postData['rstat'] != "") {
        $rstat = $postData['rstat'];
        $where_sql .= " and (stats = '$rstat')";
    }

    $offset = isset($postData['offset']) ? $postData['offset'] : 0;
    $limit_row = 20;
    $pagestr = "";

    // $sql_str = "SELECT * FROM servers_log where $where_sql order by auton desc LIMIT $limit_row OFFSET $offset";
    $sql_str = "SELECT * FROM servers_log where $where_sql order by auton desc";
    $query = $pdo->query($sql_str);
    $query->execute();

    if(!$datalist = $query->fetchAll()) {
        $table_html = "<tr><td colspan=7>暫無資料</td></tr>";
    }else{
        $pagestr = pages(count($datalist), $offset, $limit_row);
        $datalist = array_slice($datalist, $offset, $limit_row);
        $table_html = getDataHtml($datalist);
    }

    $result = array(
        'success' => True,
        'count' => count($datalist),
        'page' => $pagestr,
        'sql_str' => $sql_str,
        'table_html' => $table_html,
        'datalist' => $datalist,        
    );

    echo json_encode($result);
}

function delete(){
    $data = post_data();

    $pdo = openpdo(); 	  
    $query = $pdo->prepare("update servers_log set indel=1 where auton in (".$data["del_server_alln"].")");    
    $query->execute();

    $result = array(
        'success' => True,
        'msg' => '刪除完成'
    );

    echo json_encode($result);
}

function delete_recycle()
{
    $pdo = openpdo();
    $sql_str = "SELECT * FROM servers_log where indel=1 order by auton desc";
    $query = $pdo->query($sql_str);
    $query->execute();
    
    if(!$datalist = $query->fetchAll()) {
        $table_html = "<tr><td colspan=7>暫無資料</td></tr>";
    }else{
        $table_html = getDataHtml($datalist);
    }

    $result = array(
        'success' => True,
        'count' => count($datalist),
        'sql_str' => $sql_str,
        'table_html' => $table_html,
        'datalist' => $datalist,
    );

    echo json_encode($result);
}

function post_data(){
    foreach ($_POST as $key => $value){
        if (isset($_POST[$key])){
            $data[$key] = $value;
        }
    }
    return $data;
}

function getDataHtml($data)
{
    $table_html = '';
    foreach ($data as $datainfo) {		
        $table_html .= "<tr>";
        $table_html .= '<td><input type="checkbox" name="seln" value="'.$datainfo["auton"].'"></td>';
        $table_html .= '<td>'.$datainfo["forname"].'['.$datainfo["serverid"].']</td>';
        $table_html .= '<td>'.pay_cp_name($datainfo["pay_cp"]).'</td>';
        $table_html .= '<td>'.pay_paytype_name($datainfo["paytype"]).'</td>';
    
        $table_html .= "<td><a href='list_v.php?an=".$datainfo["auton"]."'>".$datainfo["gameid"]."</a></td>";
        $table_html .= "<td>".$datainfo["bmoney"]."</td>";
        $table_html .= "<td>".$datainfo["money"]."</td>";
        $table_html .= "<td>".$datainfo["hmoney"]."</td>";    	
        $table_html .= "<td>".$datainfo["rmoney"]."</td>";
        $table_html .= "<td>".$datainfo["times"]."</td>";

        if($datainfo["paytimes"] == "0000-00-00 00:00:00") $paytimes = "";
        else $paytimes = $datainfo["paytimes"];
        
        $table_html .= "<td>".$paytimes."</td>";
        
        switch($datainfo["stats"]) {
            case 0:
                $stats = '<span class="label label-primary">等待付款</span>';
                $mockPay = '<a href="javascript:mockPay(\''.$datainfo["auton"].'\');" class="btn btn-warning btn-xs">模擬付款</a>';
                break;

            case 1:
                $stats = '<span class="label label-success">付款完成</span>';
                if ($datainfo["RtnMsg"] == "模擬付款成功") $stats = '<span class="label label-info">模擬付款完成</span>';
                $mockPay = "";
                break;
            
            case 2:
                $stats = '<span class="label label-danger">付款失敗</span>';
                $mockPay = '<a href="javascript:mockPay(\''.$datainfo["auton"].'\');" class="btn btn-warning btn-xs">模擬付款</a>';
                break;

            case 3:
                if ($datainfo["RtnMsg"] == "模擬付款成功") $stats = '<span class="label label-info">模擬付款完成</span>';
                $mockPay = "";
                break;

            default:
                $stats = "不明";
                $mockPay = "";
                break;
        }
        $table_html .= "<td>".$datainfo["shareid"]."</td>";
        $table_html .= "<td>".$stats."</td>";
        $table_html .= "<td>".$mockPay."</td>";
        $table_html .= "</tr>";
    }
    return $table_html;
}

function test()
{
    // $pdo = openpdo(); 	  
    // $query = $pdo->prepare("update servers_log set indel=0 where auton in (10599)");    
    // $query->execute();

    // $result = array(
    //     'success' => True,
    //     'msg' => '刪除完成'
    // );

    // echo json_encode($result);
}

?>