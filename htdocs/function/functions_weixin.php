<?php
function getSignPackage($appid,$secret,$TokenFilePath,$TicketFilePath)
{
	$jsapiTicket = getJsApiTicket($appid,$secret,$TokenFilePath,$TicketFilePath);
	//echo $jsapiTicket."<><><br>";

	$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$timestamp = time();
	$nonceStr = createNonceStr();

	// 这里参数的顺序要按照 key 值 ASCII 码升序排序
	$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
	//echo $string;echo "<br>";
	$signature = sha1($string);
	//echo "{$signature}<br>";

	$signPackage = array(
	"appId"     => $appId,
	"nonceStr"  => $nonceStr,
	"timestamp" => $timestamp,
	"url"       => $url,
	"signature" => $signature,
	"rawString" => $string
	);
	return $signPackage; 
}
function getJsApiTicket($appid,$secret,$TokenFilePath,$TicketFilePath)
{
	// jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
	$TokenPath=$TokenFilePath;
	
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	
	$access_token=GetAccessToken($TokenFilePath,$url);
	//echo $access_token;echo "<br>";
	$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$access_token}";
	$ticket = GetApiTicket($TicketFilePath,$url);
	//echo $ticket;echo "<br>";
	return $ticket;
}
function GetApiTicket($FileName,$url)
{
	// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
	$content=file_get_contents($FileName);
	$data = json_decode($content);
	//print_r($data);
	//echo $data->expire_time."<>".time()."<br>";
	$remain=$data->expire_time-time();
	$len=strlen($content);
	//echo "token剩余有效时间：{$remain}<br>";
	if ($remain<0 || strlen($content)==0)//看看token有没有过期,过期了就重新获取
	{
		//echo "重新获取ticket<br>";
		// 如果是企业号用以下URL获取access_token
		// $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
		//$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$data['appid']}&secret={$data['secret']}";
		//echo $url."<br>";
		$res = json_decode(HttpGet($url));
		$ticket = $res->ticket;
		
		//记录到文件
		$data->expire_time = time() + 7000;
		$data->ticket = $ticket;
		$fp = fopen($FileName, "w");
		fwrite($fp, json_encode($data));
		fclose($fp);
		 //******************
		
	}
	else
	{
		//echo "利旧<br>";
		$ticket = $data->ticket;
	}
	//echo $ticket."<br>";
	return $ticket;
}
function createNonceStr($length = 16)
{
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$str = "";
	for ($i = 0; $i < $length; $i++)
	{
	$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $str;
}

function PushMsg($MsgContent,$MsgType,$from,$to)//将消息记录到后台
{
	$time=time();
	//$msg="{$CurUrl}?modal={$_GET[modal]}";
	$sql="insert into public_msgs (from_user,time,content,msg_type) values('{$from}','{$time}','{$MsgContent}','{$MsgType}')";
	echo $sql;echo "<br>";
	mysql_query($sql);
	$sql="update signup_users set last_msg='{$MsgContent}',last_msg_time='{$time}' where openid='{$from}'";
	//echo $sql;echo "<br>";
	mysql_query($sql);
}
function GetAccessTokenFilePath($appid)
{
	//$item=GetAppInfo();
	//$user=$item['user'];
	//$appid=$item['appid'];
	//$secret=$item['secret'];
	return "../function/{$appid}_access_token.json";
}
function GetUsersInfo($openids,$appid,$secret,$TokenFile)
{
	//echo "sdfad<><br>";
	//print_r($openids);echo "<br>";
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	
	$token=GetAccessToken($TokenFile,$url);
	
	$data[user_list]=Array();
	foreach($openids as $id)
	{
		$data[user_list][]=Array('openid'=>$id,"lang"=>'zh_CN');
	}
	//print_r($data);
	$JsonData=json_encode($data,JSON_UNESCAPED_UNICODE);
	//print_r($JsonData);echo "<br>";
	
	//$url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";
	$url="https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token={$token}";
	$result = https_post($url, $JsonData);
	LogInFile($result,"Log_GetUserInfo.txt");
	$ar=json_decode($result,true);//print_r($ar);echo "<br>";
	$ar=$ar[user_info_list];
	$result=Array();
	foreach($ar as $info)
	{
		$result[$info[openid]]=$info;
		
	}
	return $result;
	//
	//$users->next_openid;
	//return $users->data->openid;
}
function GetUserInfo($openid,$appid,$secret,$TokenFile)
{
	/*$item=GetAppInfo();
	$appid=$item['appid'];
	$secret=$item['secret'];*/
	
	
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	
	$token=GetAccessToken($TokenFile,$url);
	
	$url="https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}";
	$result=HttpGet($url);
	LogInFile($result,"Log_GetUserInfo.txt");
	$UserInfo=json_decode($result);
	
	return $UserInfo;
	/*$openid=$UserInfo->openid;
	$nickname=$UserInfo->nickname;*/
}
function GetAccessToken($FileName,$url)
{
	
	// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
	$content=file_get_contents($FileName);
	$data = json_decode($content);
	
	//echo $data->expire_time."<>".time()."<br>";
	$remain=$data->expire_time-time();
	$len=strlen($content);
	//echo "token剩余有效时间：{$remain}<br>";
	if ($remain<0 || strlen($content)==0)//看看token有没有过期,过期了就重新获取
	{
		//echo "重新获取token<br>";
		// 如果是企业号用以下URL获取access_token
		// $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
		//$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$data['appid']}&secret={$data['secret']}";
		//echo $url."<br>";
		$ReturnData=HttpGet($url);
		LogInFile($url,"GetAccountTokenRecords.txt");
		LogInFile($ReturnData,"GetAccountTokenRecords.txt");
		$res = json_decode($ReturnData);
		$access_token = $res->access_token;
		//记录到文件
		$data->expire_time = time() + 7000;
		$data->access_token = $access_token;
		$fp = fopen($FileName, "w");
		fwrite($fp, json_encode($data));
		fclose($fp);
		 //******************
		
	}
	else
	{
		//echo "利旧<br>";
		$access_token = $data->access_token;
	}
	return $access_token;
}
?>
