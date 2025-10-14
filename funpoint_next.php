<?php

	// 引入相關資訊
    include("myadm/include.php");
	include_once('./web_class.php');

	// 檢測SECCION資料
	if($_SESSION["foran"] == "") alert("伺服器資料錯誤-8000201。", 0);
	if($_SESSION["serverid"] == "") alert("伺服器資料錯誤-8000202。", 0);
	if($_SESSION["lastan"] == "") alert("伺服器資料錯誤-8000203。", 0);

    //  POST 資料至跳板
    $data['foran'] = $_SESSION["foran"];
    $data['serverid'] = $_SESSION["serverid"];
    $data['lastan'] = $_SESSION["lastan"];
    $data['token'] = strtoupper(hash('sha256', strtolower(generateRandomString(8) . 'myszfutoken')));

    // 上傳token
    $pdo = openpdo(); 
    $update_token = $pdo->prepare("update servers_log set token=? where auton=?");
    $update_token->execute(array($data['token'], $data["lastan"]));
?>
<!-- 不使用curl是因為會被block -->
<body>
    <form id='funpoint_to_payok' method="post" action="https://gohost.tw/payment_background_funpoint.php">
        <input type="hidden" name="foran" value="<?=$data["foran"]?>">
        <input type="hidden" name="serverid" value="<?=$data["serverid"]?>">
        <input type="hidden" name="lastan" value="<?=$data['lastan']?>">
        <input type="hidden" name="token" value="<?=$data['token']?>">
        <input type="hidden" name="domain" value="paygo">
    </form>
    <script type="text/javascript">
        document.getElementById('funpoint_to_payok').submit();
    </script>
</body>