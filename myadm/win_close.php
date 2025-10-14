<?
if($_REQUEST["m"] != "") {
 $m = $_REQUEST["m"];
} else {
 $m = "資料輸入成功";
}
?>
<html>
<head>
<meta content="text/html; charset=utf-8">
<script language="JavaScript" type="text/JavaScript">
window.opener.location.reload();
<!--
function closeWin(thetime) {
    setTimeout("window.close()", thetime);
}
//-->
</script>
</head>
<body onLoad="closeWin('1000')">
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center"><font color="#FF0000" size="3"><strong> <?=$m?>......</strong></font></td>
  </tr>
</table>
</body>
</html>
