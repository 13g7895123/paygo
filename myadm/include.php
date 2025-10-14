<?php

error_reporting(E_ERROR | E_PARSE);

if(!isset($_SESSION)) {	

	session_start();

}

date_default_timezone_set("Asia/Taipei");

$fake_weburl = "https://new.niudun.world/";

$transpay = $fake_weburl . "payment.php";

// $weburl = "https://donating.tw/";

$weburl = "https://paygo.tw/";

$web_dburl = "localhost";

$web_dbport = "3306";

$web_dbname = "paygo";

$web_dbuser = "paygo";

$web_dbpasswd = "pLCBkCPJKDcdbaNL";



$products = '維護費,主機租借費,資料處理費,線路費';



function generateRandomString($length) {

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $charactersLength = strlen($characters);

    $randomString = '';

    for ($i = 0; $i < $length; $i++) {

        $randomString .= $characters[rand(0, $charactersLength - 1)];

    }

    return $randomString;

}



function openpdo()

{

    global $web_dburl, $web_dbname, $web_dbuser, $web_dbpasswd, $web_dbport;

    try {

        $dsn = "mysql:host=".$web_dburl.";dbname=".$web_dbname.";port=".$web_dbport.";charset=utf8";

        $opt = array(

        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

    );

        return new PDO($dsn, $web_dbuser, $web_dbpasswd, $opt);

    } catch (PDOException $e) {

        die("<meta charset='utf-8' /><p style='color:red'>資料庫連線失敗。".$e->getMessage()."</p>");

    }

}



function opengamepdo($host, $port, $dbname, $user, $pass)

{

    if (!$host || !$port || !$dbname || !$user || !$pass) {

        die("connect database config error.");

    }

      

    try {

        $dsn = "mysql:host=".$host.";port=".$port.";dbname=".$dbname.";charset=utf8";

        $opt = array(

        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

    );

        return new PDO($dsn, $user, $pass, $opt);

    } catch (PDOException $e) {

        die("<p style='color:red'>資料庫連線失敗。".$e->getMessage()."</p>");

    }

}



function check_login()

{

    if ($_SESSION["adminid"] == "") {

        $purl = $_SERVER['REQUEST_URI'];

        $purl = "login?return=".urlencode($purl);

        echo "<meta http-equiv=refresh content=0;url=".$purl.">";

        die();

    }

}

function check_login_share()

{
    // echo _s("adminid");
    // echo _s("shareid");
    // die();

    if (empty(_s("adminid")) && empty(_s("shareid"))) {

        $purl = $_SERVER['REQUEST_URI'];

        $purl = "login?return=".urlencode($purl);

        return alert("", $purl);

        die();

    }

}

function random_products($serverId = null) {

    global $products;  

    if ($serverId != '' && $serverId !== null) {
        $pdo = openpdo();
        $query = $pdo->prepare("SELECT products FROM servers WHERE id = ?");
        $query->execute(array($serverId));
        $result = $query->fetch();

        if ($result && $result['products'] != '') {
            $products = $result['products'];
        }
    }

    $parr = explode(",", $products);

    return $parr[rand(0, count($parr) - 1)];

}

function top_html()
{

    ?>

<!doctype html>

<html lang="en-US">



<head>

	<meta charset="utf-8" />

	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />

	<title>後台管理</title>

	<meta name="description" content="" />

	<meta name="Author" content="" />



	<!-- mobile settings -->

	<!--	<meta name="viewport" content="width=device-width, maximum-scale=1, initial-scale=1, user-scalable=0" />-->



	<!-- WEB FONTS -->

	<link

		href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700,800&amp;subset=latin,latin-ext,cyrillic,cyrillic-ext"

		rel="stylesheet" type="text/css" />



	<!-- CORE CSS -->

	<link href="assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />

	<link href="assets/plugins/jquery/jquery-ui.min.css" rel="stylesheet" type="text/css" />



	<!-- THEME CSS -->

	<link href="assets/css/essentials.css?v=1" rel="stylesheet" type="text/css" />

	<link href="assets/css/layout.css" rel="stylesheet" type="text/css" />

	<link href="assets/css/color_scheme/green.css" rel="stylesheet" type="text/css" id="color_scheme" />
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" type="text/css"" />



</head>

<!--
		.boxed = boxed version
	-->
<body>
	<!-- WRAPPER -->
	<div id="wrapper" class="clearfix">
		<!-- 
				ASIDE 
				Keep it outside of #wrapper (responsive purpose)
			-->
		<aside id="aside">
			<!--
					Always open:
					<li class="active alays-open">
					LABELS:

						<span class="label label-danger pull-right">1</span>

						<span class="label label-default pull-right">1</span>

						<span class="label label-warning pull-right">1</span>

						<span class="label label-success pull-right">1</span>

						<span class="label label-info pull-right">1</span>

				-->
			<nav id="sideNav">
				<!-- MAIN MENU -->
				<ul class="nav nav-list">
					<? if (!empty(_s("adminid"))) { ?>
					<li>
						<!-- dashboard -->
						<a class="dashboard" href="index">
							<!-- warning - url used by default by ajax (if eneabled) -->
							<i class="main-icon fa fa-dashboard"></i> <span>伺服器管理</span>
                        </a>
					</li>
					<li>
						<a href="list">
                            <i class="main-icon fa-solid fa-clipboard"></i>
							<span>贊助紀錄</span>
						</a>
					</li>
					<li>
						<a href="counts">
                            <i class="main-icon fa-solid fa-chart-simple"></i>
							<span>贊助統計</span>
						</a>
					</li>
                    <li>
						<a href="send_gift">
                            <i class="main-icon fa-solid fa-gift"></i>
							<span>手動派獎</span>
						</a>
					</li>
                    <li>
						<a href="gift_records">
                            <i class="main-icon fa-solid fa-clipboard-list"></i>
							<span>派獎記錄</span>
						</a>
					</li>
				</ul>
				<!-- SECOND MAIN LIST -->
				<ul class="nav nav-list">
					<li>
						<a href="userlist">
							<i class="main-icon fa fa-users"></i>
							<span>管理者設定</span>
						</a>
					</li>
					<li>
						<a href="share_userlist">
							<i class="main-icon fa fa-user"></i>
							<span>子帳號管理</span>
						</a>
					</li>
					<li>
						<a href="#b" onclick="history.go(-1)">
							<i class="main-icon fa fa-arrow-left"></i>
							<span>回上一頁</span>
						</a>
					</li>
				</ul>
				<?
                    }
                if (!empty(_s("shareid"))) {
                    if (_s("sharecount2") > 0) {
                ?>
                    <li>
                        <a class="dashboard" href="share_index">
                            <i class="main-icon fa fa-dashboard"></i> <span>伺服器管理</span>
                        </a>
                    </li>
				<? } ?>
                <li>
                    <a href="counts">
                        <i class="main-icon fa-solid fa-chart-simple"></i>
                        <span>贊助統計</span>
                    </a>
                </li>

                <? if (_s("sharecount2") > 0) { ?>
                <li>
                    <a href="send_gift">
                        <i class="main-icon fa-solid fa-gift"></i>
                        <span>手動派獎</span>
                    </a>
                </li>
                <li>
                    <a href="gift_records">
                        <i class="main-icon fa-solid fa-clipboard-list"></i>
                        <span>派獎記錄</span>
                    </a>
                </li>
                <? } ?>

                <? if (_s("sharecount") > 0) { ?>
				<li>
					<a href="share_link">
						<i class="main-icon fa fa-star"></i>
						<span>分享詳情</span>
					</a>
				</li>
		<?
        }
    } ?>
				<!-- SECOND MAIN LIST -->
				<ul class="nav nav-list">
					<li>
						<a href="login?st=logout">
							<i class="main-icon fa fa-power-off"></i>
							<span>登出</span>
						</a>
					</li>
				</ul>
			</nav>
			<span id="asidebg">
				<!-- aside fixed background -->
			</span>
		</aside>
		<!-- /ASIDE -->

		<!-- HEADER -->

		<header id="header">
			<!-- Mobile Button -->
			<button id="mobileMenuBtn"></button>
			<!-- Logo -->
			<span class="logo pull-left">
				Game Sponsor CRM
			</span>
		</header>

		<div class="pull-right margin-right-30 margin-top-10"></div>
		<!-- /HEADER -->

<?php
}



function down_html()

{

    ?>

	</div>



	<!-- JAVASCRIPT FILES -->

	<script type="text/javascript">

		var plugin_path = 'assets/plugins/';

	</script>

	<script type="text/javascript" src="assets/plugins/jquery/jquery-2.2.3.min.js"></script>

	<script type="text/javascript" src="assets/plugins/jquery/jquery-ui.min.js"></script>



	<script type="text/javascript" src="assets/js/app.js?v=1.4"></script>

	<script type="text/javascript">

		function Mars_popup(theURL, winName, features) { //【火星人】Version 1.0

			window.open(theURL, winName, features);

		}



		function Mars_popup2(theURL, winName, features) { //【火星人】Version 1.0

			if (window.confirm("確定刪除") == true) {

				window.open(theURL, winName, features);

			} else {

				alert("重新選擇");

			}

		}



		function Mars_popup3(theURL, ms, features) { //【火星人】Version 1.0

			if (window.confirm(ms) == true) {

				window.open(theURL, 'c', features);

			} else {

				alert("重新選擇");

			}

		}



function mockPay($an) {

  var $w = screen.width/4;

  var $h = screen.height/4;

  var $left = (screen.width/2)-($w/2);

  var $top = (screen.height/2)-($h/2);

	window.open('mockpay?an='+$an,'mockpay','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+$w+', height='+$h+', top='+$top+', left='+$left);

}

	</script>

</body>



</html>

<?php

$db=null;

}



function alert($msg, $redirect = 0)

{

    echo "<SCRIPT Language=javascript>";

    echo "window.alert('".$msg."');";

    echo "</SCRIPT>";

    echo "<script language=\"javascript\">";

    if ($redirect == "0") {

        echo "history.go(-1);";

    } elseif ($redirect == "close") {

        echo "window.close();";

    } elseif ($redirect == "reload") {

        echo "window.opener.location.reload();";

    } elseif ($redirect == "reloadclose") {

        echo "window.opener.location.reload();window.close();";

    } else {

        echo "location.href='".$redirect."';";

    }

    echo "</script>";

    die();

    return;

}



function win_alert($msg)

{

    echo "<meta http-equiv=refresh content=0;url=win_close.php?m=".$msg.">";

    die();

}







function chtime($str)

{

    if ($chstr = strtotime($str)) {

        return date("Y-m-d H:i", $chstr);

    } else {

        return '';

    }

}



function chtimed($str)

{

    if ($chstr = strtotime($str)) {

        return date("Y-m-d", $chstr);

    } else {

        return '';

    }

}





function chtimet($str)

{

    if ($chstr = strtotime($str)) {

        return date("H:i", $chstr);

    } else {

        return '';

    }

}

function _r($str)

{

    $val = !empty($_REQUEST[$str]) ? $_REQUEST[$str] : null;



    return $val;

}

function _s($str)

{

    $val = !empty($_SESSION[$str]) ? $_SESSION[$str] : null;



    return $val;

}



function _p($str)

{

    $val = !empty($_POST[$str]) ? $_POST[$str] : null;



    return $val;

}

function get_real_ip()

{

    $ipaddress = '';

    if (isset($_SERVER['HTTP_CLIENT_IP'])) {

        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];

    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];

    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {

        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];

    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {

        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];

    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {

        $ipaddress = $_SERVER['HTTP_FORWARDED'];

    } elseif (isset($_SERVER['REMOTE_ADDR'])) {

        $ipaddress = $_SERVER['REMOTE_ADDR'];

    } else {

        $ipaddress = 'UNKNOWN';

    }

    return $ipaddress;

}



function remove_from_query_string($exclude)

{

    $queryarray = $_REQUEST;

    unset($queryarray['PHPSESSID']);

    if (isset($exclude)) {

        foreach (explode(",", $exclude) as $el) unset($queryarray[$el]);

    }

    if (count($queryarray) > 0) $query_string = "&" . http_build_query($queryarray);

    else $query_string = "";

    return $query_string;

}



function pages($total_rows, $offset, $limit_row)

{

    $current_page = ($offset/$limit_row) + 1;

    $total_pages = ceil($total_rows/$limit_row);

    $url_str = remove_from_query_string("offset");



    $str2 .= '<div class="text-center"><nav id="pagination_nav">';

    $str2 .= '<ul class="pagination">';



    if (($offset - $limit_row) >= 0) {

        $prev_offset = $offset - $limit_row;

        $str2 .= '<li>';

        $str2 .= "<a href=\"$PHP_SELF?offset=$prev_offset$url_str\" aria-label=\"Previous\">";

        $str2 .= '<span aria-hidden="true">&laquo;</span>';

        $str2 .= '</a>';

        $str2 .= '</li>';

    } else {

        $str2 .= '<li class="disabled"><a href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';

    }

    for ($i =1; $i <= $total_pages; $i++) {

        $of = $i * $limit_row - $limit_row;

        if ($i == $current_page) {

            $str2.= "<li class=\"active\"><a href=\"#\">$i</li>";

        } else {

            $str2.= "<li><a href=\"$PHP_SELF?offset=$of$url_str\">$i</a></li>";

        }

    }

    if (($offset + $limit_row) < $total_rows) {

        $next_offset = $offset + $limit_row;

        $str2 .= "<li>";

        $str2 .= "<a href=\"$PHP_SELF?offset=$next_offset$url_str\" aria-label=\"Next\">";

        $str2 .= "<span aria-hidden=\"true\">&raquo;</span>";

        $str2 .= "</a>";

        $str2 .= "</li>";

    } else {

        $str2 .= '<li class="disabled"><a href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';

    }

    $str2 .= "</ul>";

    $str2 .= "</nav></div>";





    return $str2;

}

function pay_cp_name($n)

{

    switch ($n) {

        case "ecpay":

          return "綠界";

        break;

        case "ebpay":

          return "藍新";

        break;

        case "pchome":

            return "支付連";

        break;

        case "gomypay":

            return "萬事達";

        break;

        case "smilepay":

            return "速買配";

        break;

        case "funpoint":

            return "歐買尬";

        break;

        case "szfu":

            return "數支付";

        break;

        case "no":

            return "無";

        break;

        default:

          return "不明";

        break;

    }

}

function pay_paytype_name($n)

{

    switch ($n) {

    case 1:

        return "超商繳費";

    break;

    case 2:

        return "ATM";

    break;

    case 3:

        return "超商代碼";

    break;

    case 30:

        return "超商代碼-全家";

    break;

    case 31:

        return "超商代碼-OK";

    break;

    case 32:

        return "超商代碼-萊爾富";

    break;

    case 33:

        return "超商代碼-7-11";

    break;

    case 4:

    return "7-11 ibon 代碼";

    break;

    case 5:

    return "線上刷卡";

    break;

    case 6:

    return "網路ATM";

    break;

    default:

    return "不明";

    break;

    }

}

 /**

     * @param $url 请求网址

     * @param bool $params 请求参数

     * @param int $ispost 请求方式

     * @param int $https https协议

     * @return bool|mixed

     */

    function curl($url, $params = false, $ispost = 0, $https = 0, $header = false)

    {

        $httpInfo = array();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($header) {

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        }

        

        if ($https) {

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在

        }

        if ($ispost) {

            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            curl_setopt($ch, CURLOPT_URL, $url);

        } else {

            if ($params) {

                if (is_array($params)) {

                    $params = http_build_query($params);

                }

                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);

            } else {

                curl_setopt($ch, CURLOPT_URL, $url);

            }

        }



        $response = curl_exec($ch);



        if ($response === false) {

            //echo "cURL Error: " . curl_error($ch);

            return false;

        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));

        curl_close($ch);

        return $response;

    }

?>