<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport"content="width=device-width"/>

<?
error_reporting(E_ALL & ~E_NOTICE);
if(!isset($_SESSION)) session_start();
include_once("../function/config.php");
include_once("../function/functions_weixin.php");
include_once("../function/functions_weixin_template_msg.php");
include_once("LocalConfig.php");

include_once("../function/Script.php");

/*
$data->Brand = "三恒";
$data->AppName = "products_sanheng";
$fp = fopen("ConfigInfo.inf", "w");
fwrite($fp, json_encode($data));
fclose($fp);
*/

if($Database->Connect()==false)
{
	echo "db connect false!";
	return;
}
mysql_select_db($DatabaseName);
//LogInFile("visit","Visit.txt");//统计访问量

$MsgLink="http://lanxiao.ghostinmetal.com/WeixinPayService/page_template_msg.php";//发送消息的接口
$operate=$_GET['operate'];
switch($operate)
{
case "PresentTemplate":
	if(strlen($_SESSION[openid])>0)
	{
		$CurPath="http://".$_SERVER[HTTP_HOST].dirname($_SERVER["PHP_SELF"])."/";//当前的链接目录
		$CurUrl="产品咨询 http://".$_SERVER[HTTP_HOST]."{$_SERVER["PHP_SELF"]}?modal={$_GET[modal]}";//当前的链接文件目录
		echo $CurUrl;echo "<br>";
		PushMsg($CurUrl,"text",$_SESSION[openid],"");
		//跳转广播
		BroadcastMsg("客户点击了咨询按钮",5,$MsgLink,"http://{$_SERVER[HTTP_HOST]}/SysCustomerService/MainPage.php");//通知客服收到消息了
		$sql="update states set state='unread' where item='{$_SESSION[adminID]}' and event='read_state'";
		//LogInFile($sql,"log_sql.txt");
		mysql_query($sql);
		echo "<script>
			window.parent.JumpToServiceWnd();
			</script>";
	}
	//print_r($_SESSION);
	return;
}

$PageSize=6;
$keyword=$_GET['keyword'];
if(strlen($_GET['PageOffset'])==0) $PageOffset=0;
else $PageOffset=$_GET['PageOffset'];

//控制缩略图偏移量
$PosXScale=1.0;
$PosYScale=0.1;
//**************
$CurModalInf=GetItemA("products","modal",$_GET[modal]);
$detail=$CurModalInf['detail'];//型号信息
$detail=str_replace("\r\n","<br>",$detail);


$FileName=basename($_SERVER["PHP_SELF"]);
$config=file_get_contents("ConfigInfo.inf");
$brand=GetXMLParam($config,"brand");//获取本地设置
$SysName=GetXMLParam($config,"AppName");
//print_r($_SESSION);echo "<br>";

//session_unset();
//session_destroy();
$PageWidth="100%";
if($_SESSION[PageModal]=="computer")
{
	$PageWidth="600px";
}

?>
<title><?echo $_GET[modal];?>
</title>
<link rel="stylesheet" href="../function/industry.css"/>
<link rel="stylesheet" href="../function/weui/dist/style/weui.css"/>
<link rel="stylesheet" href="../function/weui/dist/style/weui.min.css"/>
</head>
<body class="BodyNoSpace">
<?
include_once("chunks/chunk_title.php");

?>

<div class="weui-form-preview__hd">
    <div class="weui-form-preview__item">
        <label class="weui-form-preview__label"><?echo "{$_GET[modal]}";?></label>
        <em class="weui-form-preview__value">￥<?echo $CurModalInf[cost_card];?>元</em>
    </div>
</div>
<?
//echo GetPrice($_SESSION[adminID],$_GET[modal]);echo "<br>";
$PicsSql="select * from product_items where modal='{$_GET[modal]}' order by time";
include_once("chunks/chunk_modal_pic_scroll.php");
include_once("chunks/chunk_modal_pic_tile.php");
?>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="Industry">
<tr>
<td height="25" colspan="6" align="left"><span><? echo $detail;?></span></td>
</tr>
<tr>
<td height="50" colspan="6" align="center">&nbsp;
</td>
</tr>
</table>
<?

$CurUrl="http://".$_SERVER[HTTP_HOST]."{$_SERVER["PHP_SELF"]}";//返回的链接
//echo $CurUrl;echo "<br>";
$SendLink="{$MsgLink}?modal={$_GET[modal]}&MsgContent=您当前咨询的产品是{$_GET[modal]}&MsgType=客户选择确认单";
$SendLink.="&openid={$_SESSION[openid]}&url={$CurUrl}";
?>
<div class="FloatBottom Industry" id="BottomBar" width="100%" align="center">
    <table width="100%" border="0" cellpadding="0" cellspacing="2">
    <tr>
    <td width="52%"><a onclick="return SendTemplateMsg(this);" href="<?echo $SendLink;?>" class="weui_btn weui_btn_primary">咨询客服</a></td>
    <td width="48%"><a href="page_pay_money.php?modal=<?echo $_GET[modal];?>" class="weui_btn weui_btn_primary">立刻购买</a></td>
    </tr>
    </table>
</div>
<iframe width="100%" height="0px" id="SendFrame" src="about:blank"></iframe>
</body>
</html>
<script>
function SendTemplateMsg(obj)
{
	
	//return false;
	var url=obj.href;
	try
	{
		var wnd=document.getElementById("SendFrame");
		wnd.src=url;
	}catch(e)
	{
		//alert(e);
		alert("页面初始化中，稍后！");
		return false;
	}
	return false;
}
function SendTemplateMsgComplate()//由子窗口调用
{
	//alert("您的选择已发送给客服，正在跳转到客服界面");
	PresentTemplateMsgForWaiter("<?echo $_GET[modal];?>");
		//alert("消息已发送");
	//WeixinJSBridge.call("closeWindow");
}
function JumpToServiceWnd()//由子窗口调用,记录客户咨询的信息
{
	alert("您的选择已发送给客服，正在跳转到客服界面");
	//PresentTemplateMsgForWaiter("<?echo $_GET[modal];?>");
		//alert("消息已发送");
	WeixinJSBridge.call("closeWindow");
}
function PresentTemplateMsgForWaiter(ProductModal)
{
	var url="<?echo $FileName;?>?operate=PresentTemplate&modal="+ProductModal;
	//alert(url);
	try
	{
		//alert(url);return false;
		var wnd=document.getElementById("SendFrame");
		wnd.src=url;
		//alert("消息已发送");
		//WeixinJSBridge.call("closeWindow");
		//alert("您的选择已发送给客服，正在跳转到客服界面");
		//alert(obj.href);
	}catch(e)
	{
		//alert(e);
		alert("页面初始化中，稍后！");
		return false;
	}
	
	//alert(url);
}
</script>