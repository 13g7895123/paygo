<?
include("../include.php");

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action){
    case 'searching':
        searching();
        break;
}

function searching(){
    $data = post_data(); 
    $date_1 = $data['date_1'];  
    $date_2 = $data['date_2'];  
    $server = $data['server'];
    $server_sql = (!empty($server)) ? " and serverid='$server'" : "";

    // echo $server_sql; die();

    $month_data = [];
    $last_month_data = [];
    $day_data = [];
    $tmp_day_data = [];
    $year = date("Y");
    $last_year = date("Y", strtotime("-1 year"));
    $pdo = openpdo();

    for ($i = 1; $i <= 12; $i++){
        $month_total = 0;
        
        $sql_str = "SELECT rmoney, RtnMsg FROM servers_log where stats=1 and rmoney > 0 and indel=0 and RtnCode=1 and YEAR(times)='$year' and MONTH(times)='$i'$server_sql";    
        $query = $pdo->query($sql_str)->fetchAll();

        if ($query){
            foreach ($query as $_val){
                if ($_val['RtnMsg'] != '模擬付款成功'){
                    $month_total += $_val['rmoney'];
                }
            }
            $month_data[$i] = $month_total;
        }else{
            $month_data[$i] = 0;
        }

        $last_month_total = 0;
        $sql_str = "SELECT rmoney, RtnMsg FROM servers_log where stats=1 and rmoney > 0 and indel=0 and RtnCode=1 and YEAR(times)='$last_year' and MONTH(times)='$i'$server_sql";    
        $query = $pdo->query($sql_str)->fetchAll();

        if ($query){
            foreach ($query as $_val){
                if ($_val['RtnMsg'] != '模擬付款成功'){
                    $last_month_total += $_val['rmoney'];
                }
            }
            $last_month_data[$i] = $last_month_total;
        }else{
            $last_month_data[$i] = 0;
        }
    }

    $sql_str = "SELECT DATE(times) as date, SUM(rmoney) as total, RtnMsg FROM servers_log where stats=1 and indel=0 and RtnCode=1$server_sql and (times between '$date_1 00:00' and '$date_2 23:59') GROUP BY DATE(times)";    
    $query = $pdo->query($sql_str)->fetchAll();

    if ($query){
        foreach ($query as $_val){
            if ($_val['RtnMsg'] != '模擬付款成功'){
                $tmp_day_data[$_val['date']] = $_val['total'];
            }
        }
    }

    $show_date = $date_1;
    $max_date = date('Y-m-d', strtotime($date_2 . ' +1 days'));
    while ($show_date != $max_date){
        $day_data[$show_date] = (!isset($tmp_day_data[$show_date])) ? 0 : $tmp_day_data[$show_date];
        $show_date = date('Y-m-d', strtotime($show_date . ' +1 days'));
    }
    
    $result = array(
        'success' => True,
        'month' => $month_data,
        'last_month' => $last_month_data,
        'day' => $day_data,
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