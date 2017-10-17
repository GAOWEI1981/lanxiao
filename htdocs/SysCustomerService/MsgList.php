<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport"content="width=device-width"/>
<link href="../function/InterfaceStyle.css" rel="stylesheet" type="text/css" />

<?php
//header("Content-Type:text/html;charset=utf-8");
ob_start();
error_reporting(E_ALL & ~E_NOTICE);
if(!isset($_SESSION)) session_start();
include_once("../function/config.php");
include_once("../function/WeiXinLib.php");
include_once("../function/JSSDK.php");
include_once("../function/InterfaceStyle.css");
include_once("../function/Script.php");
include_once("LocalConfig.php");
if($Database->Connect()==false)
{
	echo "db connect false!";
	return;
}
mysql_select_db($DatabaseName);
$ItemCount=20;
$PageOffset=$_GET[PageOffset];
if(strlen($PageOffset)==0) $PageOffset=0;
//print_r($_SESSION);
$FileName=basename($_SERVER["PHP_SELF"]);
$id=$_GET[id];
//echo $id;
$operate=$_GET[operate];

switch($operate)
{
case "";
	$TokenPath=GetAccessTokenFilePath();
	$user=GetUserInfo($id,"account.json",$TokenPath);
	$ClientIcon=$user->headimgurl;
	$ClientIcon=substr($ClientIcon,0,strlen($ClientIcon)-1)."46";//头像 0、46、64、96、132
	$LocalUserInfo=GetItemA("signup_users","openid",$id);
	$UserName=$LocalUserInfo[name];
	if(strlen($UserName)==0) $UserName=$user->nickname;
	$UserName=mb_strimwidth($UserName, 0, 10, '...', 'utf8');
	//print_r($user);
	break;
case "GetList":
	$sql="update states set state='read' where event='read_state' and item='{$id}' and owner='{$_SESSION[adminID]}'";
	//LogInFile($sql,"log_sql.txt");
	mysql_query($sql);
	
	
	
	
	
	
	
	$AllSql="select * from public_msgs where (from_user='{$id}' or to_user='{$id}') order by time desc,id desc limit {$PageOffset},{$ItemCount}";
	$sql="select a.*,b.name from({$AllSql}) a left JOIN signup_users b on(a.id_waiter=b.openid)";
	//$sql="select a.* from ({$sql}) a order by a.time";
	
	//echo $sql;echo "<br>";
	$result=mysql_query(str_replace("SQL_CALC_FOUND_ROWS","",$sql));
	//获得总行数
	$r=mysql_query($AllSql);
	$s="SELECT FOUND_ROWS()";
	$r=mysql_query($s);
	$r=mysql_fetch_array($r);
	$ItemCount=$r[0];
	echo "<PersonalItemCount>{$ItemCount}</PersonalItemCount>";
	
	//**********
	$items=array();
	$TitleGet=0;//如果链接太多就只显示一个标题
	while($row=mysql_fetch_array($result))
	{
		/*if(strpos($row[content],"http:")>=0 && $TitleGet<3)
		{
			$content=file_get_contents($row[content]);
			$row[WebTitle]=GetXMLParam($content,"title");
			$TitleGet++;
		}*/
		$row[StrTime]=date("Y-m-d H:i:s",$row[time]);
		$items[]=$row;
	}
	$content=json_encode($items);
	//最后一次消息时间
	//LogInFile("eeeeeeeeeeeeeeeeeeeeeeee","log.txt");
	//LogInFile($content,"log.txt");
	$sql="select * from signup_users where openid='{$id}'";
	$result=mysql_query($sql);
	$row=mysql_fetch_array($result);
	//***************
	//LogInFile("<PageOffset>{$PageOffset}</PageOffset><LastMsgTime>{$row[last_msg_time]}</LastMsgTime>","lll.txt");
	echo "<response>{$content}</response><PageOffset>{$PageOffset}</PageOffset><LastMsgTime>{$row[last_msg_time]}</LastMsgTime>";
	return;
}
?>
<title><?echo $UserName;?></title>
</head>
<body class="BodyNoSpace">
<label style="display:none" id="MsgTotalCount"></label>
<table width="100%" border="1" cellspacing="0">
<?
for($i=$ItemCount-1;$i>=0;$i--)
{
	?>
    <tr>
      <td>
    <table width="100%" border="0" cellspacing="0" id="<?echo $i;?>">
    <tr>
    <td width="6%" rowspan="2" align="center" id="MulSel" ><input type="checkbox" width="20px"/></td>
    <td width="15%" rowspan="2" align="center"><label style="display:none;" id="data"></label>
    <img id="UserIcon"/></td>
    <td width="67%" id="MsgBox">
    <label id="DataIndex" style="display:none;"><?echo $i;?></label>
    <label style="word-break:break-all; word-wrap:break-all;" id="MsgText">&nbsp;</label>
    <a target="_blank" id="ImgLink" href="" onclick="return ChickImgLink(this);"><img id="MsgImg"/></a>
    <a target="_blank" id="MsgLink" href="" onclick="return ChickMsgLink(this);"></a>
    <a href=""  id="VoiceLink"></a></td>
     <td width="12%" rowspan="2" align="center"><img id="WaiterIcon"/><br><lable id="WaiterName"></lable></td>
    </tr>
    <tr>
      <td align="right" id="time">&nbsp;</td>
    </tr>
    </table></td></tr>
   <?
}
include_once("Personal_Interface.js");
?>
</table>
</body>
</html>
<script>
function ChickImgLink(obj)
{
	var url=obj.href;//加上偏移，方便返回的时候返回原位置
	url="JumpPage.php?PersonalPageOffset="+PageOffset+"&url="+escape(url);
	obj.href=url;
	//alert(url);
	//return false;
	return true;
}
function ChickMsgLink(obj)
{
	var url=obj.href;//加上偏移，方便返回的时候返回原位置
	url="JumpPage.php?PersonalPageOffset="+PageOffset+"&url="+escape(url);
	obj.href=url;
	//alert(url);
	//return false;
	return true;
}
</script>

