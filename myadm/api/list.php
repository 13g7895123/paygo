<?
include("../include.php");

/* 有三支Form會用到 */
$action = isset($_POST['action']) ? $_POST['action'] : '';

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
}

function searching(){
    $data = post_data();    

    $where_sql = ($data['is_delete'] == '1') ? 'indel=1' : 'indel=0';
    if ($data['keyword'] != ''){
        $kword = $data['keyword'];
        $where_sql .= " and (orderid like '%$kword%' or forname like '%$kword%' or serverid like '%$kword%' or gameid like '%$kword%' or charid like '%$kword%')";
    }
    if ($data['date_1'] != "" && $data['date_2'] != "") {
        $where_sql .= " and (times between '".$data['date_1']." 00:00' and '".$data['date_2']." 23:59')";
    }
    if($data['rstat'] != "") {
        $rstat = $data['rstat'];
        $where_sql .= " and (stats = '$rstat')";
    }

    $pdo = openpdo();
    $offset = $data['offset'];
    $limit_row = 20;
    $sql_str = "SELECT count(auton) as t FROM servers_log where ".$where_sql."";
    $numsrow = $pdo->query($sql_str)
        ->fetch()["t"];
    $pagestr = pages($numsrow, $offset, $limit_row);

    $sql_str = "SELECT * FROM servers_log where $where_sql order by auton desc LIMIT $limit_row OFFSET $offset";
    $query = $pdo->query($sql_str);
    $query->execute();
    $table_html = '';
    if(!$datalist = $query->fetchAll()) {
        $table_html .= "<tr><td colspan=7>暫無資料</td></tr>";
    }else{
        foreach ($datalist as $datainfo) {		
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
                $mockPay = "";
                break;
                
                case 2:
                $stats = '<span class="label label-danger">付款失敗</span>';
                $mockPay = '<a href="javascript:mockPay(\''.$datainfo["auton"].'\');" class="btn btn-warning btn-xs">模擬付款</a>';
                break;

                case 3:
                    $stats = '<span class="label label-info">模擬付款完成</span>';
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
    }

    $result = array(
        'success' => True,
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

function delete_recycle(){
    $pdo = openpdo();
    $sql_str = "SELECT * FROM servers_log where indel=1 order by auton desc";
    $query = $pdo->query($sql_str);
    $query->execute();
    $table_html = '';
    if(!$datalist = $query->fetchAll()) {
        $table_html .= "<tr><td colspan=7>暫無資料</td></tr>";
    }else{
        foreach ($datalist as $datainfo) {		
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

        $result = array(
            'success' => True,
            'sql_str' => $sql_str,
            'table_html' => $table_html,
            'datalist' => $datalist,
        );
    
        echo json_encode($result);
    }
}

function post_data(){
    foreach ($_POST as $key => $value){
        if (isset($_POST[$key])){
            $data[$key] = $value;
        }
    }
    return $data;
}

?>