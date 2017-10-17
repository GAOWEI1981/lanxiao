<?php
define("TOKEN","88909_S");
//header("Content-Type:text/html;charset=gb2312");
header("Content-Type:text/html;charset=utf-8");
error_reporting(E_ALL & ~E_NOTICE);
if(!isset($_SESSION)) session_start();
include_once("../function/config.php");
include_once("../function/functions_mall.php");
//include_once("../function/functions_weixin.php");
include_once("../function/WeiXinLib.php");
include_once("LocalConfig.php");
class wechatCallbackapiTest
{

	public function valid()
	{
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()==true)
		{
			//LogInFile("sig true","SignatureLog.txt");
			//echo $echoStr;
			return "YES";
		}
		else
		{
			//LogInFile("sig false","SignatureLog.txt");
			return "NO";
		}
	}

	private function checkSignature()
	{
		$signature = $_GET["signature"];
		//LogInFile($signature,"Log.txt");
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		$token =TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr,SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		//LogInFile($tmpStr."<>".$signature,"Log.txt");
		if( $tmpStr == $signature )
		{
			//LogInFile("ok sig","Log.txt");
			return true;
		}
		else
		{
			
			return false;
		}
	}
}
function ResponseAuto($msg)
{
	$content="";
	$words=ReadArray("KeywordResponce.inf");
	$now=time();
	
	foreach($words as $va)
	{
		$inf=json_decode($va,true);
		$key=$inf['keyword'];
		$content=$inf['content'];
		
		//LogInFile($content,"re.txt");
		if(IsTimeBetween(date("H:i",$now),$inf['time_from'],$inf['time_to']))
		{
			if(strlen($key)==0)//假若关键词为空则所有消息都需要回复
			{
				return $content;
			}
			else if($msg==$key)
			{
				return $content;
			}
		}
		
	}
	
	return "";
	
}
/*function TransferCustomerService($msg,$openid)
{
	$path=substr($_SERVER['SCRIPT_URI'],0,strrpos($_SERVER['SCRIPT_URI'],'/'));
	$sql="select * from users where openid is not null";
	$result=mysql_query($sql);
	
	$TokenPath=GetAccessTokenFilePath();
	$user=GetUserInfo($openid,"account.json",$TokenPath);
	if(isset($user->errcode))
	{
	}
	else
	{
		$headimg=$user->headimgurl;
		$headimg=substr($headimg,0,strlen($headimg)-1)."96";
		$row=GetItemA("signup_users","openid",$openid);
		$name=$row['name'];
		if(strlen($name)==0)
			$name=$user->nickname;
		//$name=$openid;
	}
	while($row=mysql_fetch_array($result))
	{
		if($row['openid']==$openid) continue;//自己给公众号的消息自己不用接受
		$url="{$path}/Personal.php?operate=SendMsg&userID={$row['openid']}";
		$data = array
				(
			    'content' => $name.'\r\n发来一条消息:\r\n'.$msg,
			    'password' => 'password',
				'save' => 'false'
				);
		
		$re=https_post($url,$data);
		//LogInFile("222","re.txt");
		//$content.=$url."\r\n\r\n";
		
	}
	
	return "";
	
}*/
function TransferCustomerService($msg,$openid)
{
	$path=substr($_SERVER['SCRIPT_URI'],0,strrpos($_SERVER['SCRIPT_URI'],'/'));
	$sql="select * from users where openid is not null";
	$result=mysql_query($sql);
	
	$TokenPath=GetAccessTokenFilePath();
	$user=GetUserInfo($openid,"account.json",$TokenPath);
	if(isset($user->errcode))
	{
	}
	else
	{
		
		$row=GetItemA("signup_users","openid",$openid);
		$name=$row['name'];
		if(strlen($name)==0) $name=$user->nickname;
	}
	while($row=mysql_fetch_array($result))
	{
		if($row['openid']==$openid) continue;//自己给公众号的消息自己不用接受
		$url="{$path}/Personal.php?operate=SendMsg&userID={$row['openid']}";
		$data = array
				(
			    'content' => $name.'\r\n发来一条消息:\r\n'.$msg,
			    'password' => 'password',
				'save' => 'false'
				);
		
		$re=https_post($url,$data);
		//LogInFile("222","re.txt");
		//$content.=$url."\r\n\r\n";
		
	}
	
	return "";
	
}

function ExpressResponse($msg)
{
	//LogInFile($msg,"res.txt");
	$word="";
	$LastTime=time()-3600*24*15;
	$msg=str_replace("：",":",$msg);
	$msg=str_replace("快递单号:","#keyword#",$msg);
	$msg=str_replace("快递号码:","#keyword#",$msg);
	$msg=str_replace("快递编号:","#keyword#",$msg);
	$msg=str_replace("快递状态:","#keyword#",$msg);
	$msg=str_replace("包裹单号:","#keyword#",$msg);
	$msg=str_replace("包裹编号:","#keyword#",$msg);
	$msg=str_replace("包裹状态:","#keyword#",$msg);
	$msg=str_replace("快递:","#keyword#",$msg);
	$msg=str_replace("包裹:","#keyword#",$msg);
	$msg=str_replace("快递","#keyword#",$msg);
	$msg=str_replace("包裹","#keyword#",$msg);
	if(strpos($msg,"#keyword#")!==false)
	{
		$key=GetContentBetweenTwoWords($msg,"#keyword#","");
		$sql="select * from express_response where (remark like '%{$key}%' or receiver like '%{$key}%' or address like '%{$key}%') order by time limit 0,5";
		LogInFile($sql,"sql.txt");
		$result=mysql_query($sql);$ItemCount=0;
		while($row=mysql_fetch_array($result))
		{
			$time=date("Y-m-d",$row['time']);
			$text="收件人:{$row['receiver']}\r\n快递公司:{$row['express_company']}\r\n单号:{$row['express_number']}\r\n{$row['remark']}\r\n********\r\n";
			
			$word.=$text;
			$ItemCount++;
		}
		if($ItemCount==0)
		{
			return "自动回复：未找到快递单号,如您需要查找快递单号，请输入'快递'+'收件人姓名'进行查询。\r\n例如：\r\n快递张三";
		}
	}
	else 
		LogInFile("fefefef","sql.txt");
	return $word;
	
	
	
}
function IsMsgRepeat($time,$UserID)//判断消息是否重复了
{
	$sql="select * from public_msgs where time='{$time}' and from_user='{$UserID}'";
	$result=mysql_query($sql);
	$num=mysql_num_rows($result);
	//if($num>0) LogInFile($keyword,"repeat.txt");
	//if($item[last_msg]!=$keyword) LogInFile($keyword,"repeat.txt");
	if($num==0)
	{
		return false;		
	}
	else return true;
}
function DisposeMsg($input)
{
	
	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
	//LogInFile($postStr,"res.txt");
	if (!empty($postStr))
	{
		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		$fromUsername = $postObj->FromUserName;
		$msgType=$postObj->MsgType;
		$picUrl=$postObj->PicUrl;
		$toUsername = $postObj->ToUserName;
		$keyword = trim($postObj->Content);
		$MediaId=$postObj->MediaId;
		$time = time();
		$EventKey=$postObj->EventKey;
		$textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[%s]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<FuncFlag>1</FuncFlag>
			</xml>";
			//准备信息
			

		//LogInFile($keyword,"ClientInput.txt");//记录客户输入
		
		$response="";//自动回复
		$sql="insert into signup_users (openid,time) values('$fromUsername','{$time}')";
		mysql_query($sql);
		//LogInFile($msgType,"UndefineMsgType.txt");
		if(IsMsgRepeat($postObj->CreateTime,$fromUsername))//消息重复就不做任何处理
		{
			echo "success";
			return;
		}
		switch($msgType)
		{
		case "text":
			$sql="insert into public_msgs (from_user,time,content,msg_type) values('{$fromUsername}','{$postObj->CreateTime}','{$keyword}','{$msgType}')";
			mysql_query($sql);
			UpdateMsgState($fromUsername,$keyword);
			//$response=ResponseAuto($keyword);
			$response.=ExpressResponse($keyword);
			TransferCustomerService($keyword,$fromUsername);
			//echo sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType,"success");			
			break;
		case "image":
			$path="../pic/data/".date("YmdHis").CreateID(4).".jpg";
			$sql="insert into public_msgs (from_user,time,content,msg_type) values('{$fromUsername}','{$postObj->CreateTime}','{$path}','{$msgType}')";
			GrabImage($picUrl,$path);
			mysql_query($sql);
			UpdateMsgState($fromUsername,$path);
			TransferCustomerService("图片消息",$fromUsername);
			break;
		case "voice":
			$item=GetAppInfo();
			$user=$item['user'];
			$appid=$item['appid'];
			$secret=$item['secret'];
			
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
			//echo $url."<br>";
			$TokenPath=GetAccessTokenFilePath();
			$token=GetAccessToken($TokenPath,$url);
						
			$file="http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$token}&media_id={$MediaId}";
			$path="../voice/".date("YmdHis").".amr";
			GrabFile($file,$path);
			$path="/voice/".date("YmdHis").".amr";
			
			$sql="insert into public_msgs (from_user,time,content,msg_type) values('{$fromUsername}','{$postObj->CreateTime}','{$path}','{$msgType}')";
			mysql_query($sql);
			
			UpdateMsgState($fromUsername,$path);
			TransferCustomerService("语音消息",$fromUsername);
			break;
		case "event":
			$OwnerID=str_replace("qrscene_","",$EventKey);//谁介绍关注的？
			LogInFile($postStr,"ResponseString.txt");
			$utool=new UsersDatabase();

			switch($postObj->Event)
			{
			case "subscribe"://首次关注
				$utool->ChangeUserOwner($OwnerID,$fromUsername);//更改会员归属关系
				$text=file_get_contents("LocalConfig.inf");
				$response=GetXMLParam($text,"SubscribeMsg");
				UpdateMsgState($fromUsername,$postObj->Event);
				break;
			case "CLICK":
				//LogInFile(serialize($postObj);
				$response=$postObj->EventKey;
				//$response.=$postObj->eventKey;
				UpdateMsgState($fromUsername,$postObj->Event);
				break;
			case "TEMPLATESENDJOBFINISH":
				//模板消息不记录，因为无法确定是否可以给客户发消息
				break;
			case "SCAN":
				//LogInFile($postStr,"OffNormalEvent.txt");
				//LogInFile($OwnerID." ".$fromUsername,"res.txt");
				
				//扫码更改会员归属必须确保两者不是上下级关系
				$utool->ChangeUserOwner($OwnerID,$fromUsername);//更改会员归属关系
				/*if(strlen($OwnerID)>0 && $utool->IsOwner($fromUsername,$OwnerID)==false && $utool->IsOwner($OwnerID,$fromUsername)==false)//不能互相包含
				{
					$sql="update signup_users set owner='{$OwnerID}' where openid='{$fromUsername}'";
					LogInFile($sql,"res.txt");
					mysql_query($sql);
					
				}*/
				break;
			}
			//LogInFile($postObj->Event,"UndefineMsgType.txt");
			//点击菜单以及关注等消息都要记录
			
			break;
		default:
			//LogInFile(json_encode($postObj),"UndefineMsgType.txt");
			break;
		}
		
		
		if(strlen($response)>0)
		{
			$text=sprintf($textTpl, $fromUsername, $toUsername, $time,"text",$response);	
			echo $text;		
		}
		else echo "success";
		
	}
}
?>