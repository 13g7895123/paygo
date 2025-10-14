<?php
  include("include.php");
  if($_SESSION["adminid"] == "") die("過期請重新登入。");
  $ip = $_REQUEST["ip"];
  $port = $_REQUEST["port"];
  $dbname = $_REQUEST["dbname"];
  $user = $_REQUEST["user"];
  $pass = $_REQUEST["pass"];
  $tb = $_REQUEST["tb"];
  
  if(!$ip || !$port || !$dbname || !$user || !$pass) die("資料庫連線資料錯誤。");

  echo "<p>MYSQL 資料庫連線測試</p>";
  echo "<p>開始進行資料庫連線。</p><p>use ".$user." / ".$pass." connect to ".$ip.":".$port." - ".$dbname."</p>";
  
  try
{    
    $gpdo = opengamepdo($ip, $port, $dbname, $user, $pass);
    
    echo "<p>資料庫 $dbname 連線成功。</p>";
}
catch(PDOException $e)
{    
    die("<p style='color:red'>資料庫連線失敗。<br>".$e->getMessage()."</p>");
}
try {
    echo "<p>開始尋找資料表 $tb。</p>";
    $gquery = $gpdo->query("SELECT * FROM ".$tb);
    if ($gquery->execute()) {
        echo "<p>資料表 $tb 已經找到。</p>";
    }
}    catch(PDOException $e)
    {    
        die("<p style='color:red'>資料表尋找失敗。<br>".$e->getMessage()."</p>");
    }
?>