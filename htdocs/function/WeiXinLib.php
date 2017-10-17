<?php
include_once("config.php");
function GetAccessTokenFilePath()
{
	$item=GetAppInfo();
	$user=$item['user'];
	$appid=$item['appid'];
	$secret=$item['secret'];
	return "../function/{$appid}_access_token.json";
}
function GetAppInfo()
{
	$content=file_get_contents("account.json");
	$account=GetXMLParam($content,"account");//获取本地设置
	$info['appid']=GetXMLParam($content,"appid");
	$info['secret']=GetXMLParam($content,"secret");
	return $info;
}
function GetUsers()
{
	$item=GetAppInfo();
	$user=$item['user'];
	$appid=$item['appid'];
	$secret=$item['secret'];
	
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	//echo $url."<br>";
	$token=GetAccessToken("../access_token.json",$url);
	
	$url="https://api.weixin.qq.com/cgi-bin/user/get?access_token={$token}&next_openid=";
	$users=json_decode(HttpGet($url));
	//$users->next_openid;
	return $users->data->openid;
}
function GetUserInfo($openid,$AccountInfoFile,$TokenFile)
{
	$item=GetAppInfo();
	$appid=$item['appid'];
	$secret=$item['secret'];
	
	
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	
	$token=GetAccessToken($TokenFile,$url);
	
	$url="https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}";
	$UserInfo=json_decode(HttpGet($url));
	//LogInFile($url,"we.txt");
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
function GetApiTicket($FileName,$url)
{
	
	// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
	$content=file_get_contents($FileName);
	$data = json_decode($content);
	//echo $data->expire_time."<>".time()."<br>";
	$remain=$data->expire_time-time();
	$len=strlen($content);
	echo "token剩余有效时间：{$remain}<br>";
	if ($remain<0 || strlen($content)==0)//看看token有没有过期,过期了就重新获取
	{
		echo "重新获取ticket<br>";
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
		echo "利旧<br>";
		$ticket = $data->ticket;
	}
	//echo $ticket."<br>";
	return $ticket;
}
function rad($d)
{
   return $d * 3.1415926 / 180.0;
}

function GetDistance($lat1,$lng1,$lat2,$lng2)
{
	$EARTH_RADIUS = 6378.137;//地球半径
	$radLat1 = rad($lat1);
	$radLat2 = rad($lat2);
	$a = $radLat1 - $radLat2;
	$b = rad($lng1) - rad($lng2);
	
	$s = 2.0 * asin(sqrt(pow(sin($a/2.0),2) +cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
	$s = $s * $EARTH_RADIUS;
	$s = round($s * 10000) / 10000;
	return $s;
}
?>
