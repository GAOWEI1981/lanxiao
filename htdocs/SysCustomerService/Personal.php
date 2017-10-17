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
	print_r($Database);
	echo "db connect false!";
	return;
}
$FileName=basename($_SERVER["PHP_SELF"]);
mysql_select_db($DatabaseName);

$id=$_GET[id];
//echo $id;
$operate=$_GET[operate];
//print_r($_SESSION);
switch($operate)
{
case "";
	break;
case "SendImg":
	//include_once("LocalScript.php");
	$UserID=$_GET['userID'];
	
	//确认消息是谁发出的
	if(strlen($_SESSION['adminID'])>0)
	{
		$waiter=$_SESSION['adminID'];
	}
	else $waiter=$_GET['waiter'];
	//******************
	
	$FileType=$_FILES["file"]["type"];
	$path="";
	if(isset($_FILES["file"]))//如果是post过来的文件
	{
		
			$path="../pic/data/".date("YmdHis").".jpg";
			$va=move_uploaded_file($_FILES["file"]["tmp_name"],$path);//下载文件
	
	}
	else
	{
		$path=$_GET['path'];
	}
	//LogInFile($path,"uuuuu.txt");
	if(strlen($path))
	{
		$t=time();
		$result=SendImage($path,$UserID);
		
		if($result==true)
		{
			$err="发送成功";
			$time=time();
			//$path=substr($path,3,strlen($path)-3);
			$sql="insert into public_msgs (to_user,time,content,msg_type,id_waiter) values('{$UserID}','{$time}','{$path}','image','{$_SESSION[adminID]}')";
			
			mysql_query($sql);
			//更新用户最近消息的列表
			if(strlen($UserID)>0)
			{
				$sql="update signup_users set last_msg='{$path}',last_msg_time='{$time}' where openid='{$UserID}'";
				mysql_query($sql);
			}
		}			
		else $err="发送失败";
	}
	else $err.="发送失败";
	echo "<script>parent.StopUpload('{$err}');</script>";
	return;
case "SendMsg":
	$id=$_GET['userID'];
	$type="text";
	
	$msg=$_POST['content'];
	//确认消息是谁发出的
	if(strlen($_SESSION['adminID'])>0)
	{
		$waiter=$_SESSION['adminID'];
	}
	else $waiter=$_GET['waiter'];

	
	//******************
	
	$msg=ToUTF8($msg);
	$template = '{
		"touser":"'.$id.'",
		"msgtype":"text",
		"text":
		{
			 "content":"'.$msg.'"
		}
	}';
	$item=GetAppInfo();
	$user=$item['user'];
	$appid=$item['appid'];
	$secret=$item['secret'];
	//print_r($item);
	
	//LogInFile("sfeeeee","personal.txt");
	
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	$TokenPath=GetAccessTokenFilePath();
	$token=GetAccessToken($TokenPath,$url);

	 $url=   "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$token}";
	//$dataRes = request_post($url, urldecode($json_template));
	$result = https_post($url,$template);
	echo $url;echo "<br>";
	echo $result;echo "<br>";
	$result=json_decode($result);
	echo "<responce>";
	

	switch($result->errcode)
	{
		case "0":
			if($_POST['save']!="false")//不需要保存的就不保存,主要是自动回复 的消息
			{
				$time=time();
				$sql="insert into public_msgs (to_user,time,content,msg_type,id_waiter) values('{$id}','{$time}','{$msg}','text','{$waiter}')";
				mysql_query($sql);
				//LogInFile($sql,"RepeatTest.txt");
				//更新用户列表
				if(strlen($id)>0)
				{
					$sql="update signup_users set last_msg='{$msg}',last_msg_time='{$time}' where openid='{$id}'";
					mysql_query($sql);
				}
			}	
				//********		
			echo "true";
			$err="发送成功";
			
			break;
		case "45015":
			echo "false";
			$err="该用户已脱机！";
			break;
		default:
			echo "false";
			$err="发送失败";
			break;
		
	}
	echo "</responce>";
	echo "<script>parent.StopUpload('{$err}');</script>";
	return;
case "DelUser":
	if(strlen($id)>0)
	{
		$sql="select * from public_msgs where (from_user='{$id}' or to_user='{$id}') and (msg_type='image' or msg_type='voice')";//删除图片和语音
		$result=mysql_query($sql);
		while($row=mysql_fetch_array($result))
		{
			unlink($row['content']);
		}
		$sql="delete from public_msgs where from_user='{$id}' or to_user='{$id}'";//删除对话记录
		mysql_query($sql);
		$sql="delete from signup_users where openid='{$id}'";
		mysql_query($sql);
		echo "<response>删除成功</response>";
	}
	
	//Header("Location:MainPage.php");
	return;
case "DeleteMsgs":
	$OrderID=$_GET['OrderID'];
	$ids=$_GET['ids'];
	$content="<responce>";
	if(strlen($ids)>0)
	{
		//避免误删其他条目
		$words=explode("<d>",$ids);
		$ids="";
		foreach($words as $va)
		{
			if(strlen($va)>0)
			{
				$ids.="'{$va}',";
			}
		}
		$ids=substr($ids,0,strlen($ids)-1);

		$sql="delete from public_msgs where ID in ({$ids})";
		//$content.=$sql;
		mysql_query($sql);
		//$sql="select * from account_book where OrderID in({$ids})";
	}
	//$content.="$ids";
	$content.="</responce>";
	echo $content;
	break;
}
?>
<title><?echo $UserName;?></title>
</head>
<body class="BodyNoSpace">
<table width="100%" border="1" cellspacing="0" class="ProductTable" id="PageTitle">
  <tr>
    <th width="24%" height="25" align="left" id="UserName"><a href="MainPage.php" onclick="return Back(this);">返回首页</a></th>
    <th align="center"><a href="" onclick="return TransmitMsg();">消息转发</a></th>
    <th width="30%" align="center">
      <select name="select" onchange="PersonalOperate(this);" style="display:<?if($_SESSION[modal]!="admin") echo "none";?>">
        <option selected="selected">操作列表</option>
        <option>消息转发</option>
        <option value="<?echo $FileName;?>?operate=DelUser&amp;id=<?echo $_GET['id'];?>">清除该用户</option>
        <option value="test.php?id=<?echo $_GET[id];?>">模板消息</option>
    </select>    </th>
  </tr>
</table>
<div align="center">
<iframe style="width:99%; height:300px; border:0;" src="MsgList.php?<?echo "id={$id}&ItemCount={$ItemCount}";?>" name="MsgWnd" id="MsgWnd">
</iframe>
</div>
<?
$SendImgLink="{$FileName}?operate=SendImg&userID={$_GET[id]}";
$SendMsgLink="{$FileName}?operate=SendMsg&userID={$_GET[id]}";
?>
<form action="none.php" method="post" enctype="multipart/form-data" target="form-target" name="SendForm" id="SendForm" onsubmit="startUpload();">
  <table width="100%" border="0" cellspacing="0" class="ProductTable" id="PageFoot">
  <tr>
      <td height="30" colspan="4" align="center"><table width="100%" border="0">
        <tbody>
          <tr>
            <td width="50%" align="left"><input type="button" style="width:90%;" value="<<" onclick="window.MsgWnd.LastPage();"/></td>
            <td align="right"><input type="button" style="width:90%;" id="submit" value=">>" onclick="window.MsgWnd.NextPage();"/></td>
          </tr>
        </tbody>
      </table></td>
    </tr>
    <tr>
      <td width="12%" height="30" align="center">
      <button onclick="return SwitchInput();">---</button>      </td>
      <td colspan="2" align="center"><input style="width:60%;display:none;" type="file" name="file" id="file"/>
      <textarea name="content" id="content" style="height:40px;font-size:{$FontSize}px;" width="70%"></textarea></td>
      <td width="16%" align="center">
      <input type="submit" style="width:60px;" onclick="return PostContent();" value='发送'></td>
    </tr>
    <tr>
      <th height="30" colspan="4" align="center" id="textout">
      
      </th>
    </tr>
  </table>
</form>
<iframe style="width:98%; height:0px; border:0;display:none;" name="form-target" id="form-target"></iframe>
</body>
</html>
<?
//include_once("Personal_Interfacenew.js");
include_once("Personal_operate.js");
?>
<script src="../function/jquery.js"></script>
<script>
InitScreenHeight();
function InitScreenHeight()
{
	//alert($("#form-target").height());
	try
	{
		var height=$(window).height()-$("#PageFoot").height()-$("#form-target").height()-$("#PageTitle").height();
		var height=Math.round(height*0.96);
		//var height=$(window).height()-$("#PageFoot").height()-$("#MsgWnd").offset().top;
		//MsgWnd
		//alert(height);
		$("#MsgWnd").height(height);//数据显示区调整到最大

		
	}catch(e)
	{
		alert("InitScreenHeight:"+e);
	}
	//alert(RowCountInScreen);
}
function ShowString(str)
{
	$("#textout").text(str);
}
</script>