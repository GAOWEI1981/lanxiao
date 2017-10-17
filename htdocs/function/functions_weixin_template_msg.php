<?php
function BroadcastMsg($MsgContent,$ReceiverType,$MsgLink,$url)
{
	$sql="select * from signup_users where user_type='{$ReceiverType}'";
	//echo $sql;echo "<br>";
	$result=mysql_query($sql);
	while($row=mysql_fetch_array($result))
	{
		//print_r($row);echo "<br>";
		if($row[openid]!=$_SESSION[openid])//消息不能自己发给自己
			$ids.="{$row[openid]}%_%";
		//SendServiceConfirmMsg($TemplateID,$MsgContent,"客服广播","",$row[],$appid,$secret,$TokenFile)
	}
	$ids=substr($ids,0,strlen($ids)-3);
	$SendLink="{$MsgLink}?operate=batch&modal=&MsgContent={$MsgContent}&MsgType=消息广播";
	$SendLink.="&openid={$ids}&url={$url}";
	//echo $SendLink;echo "<br>";
	//update states set item='unread' where item='oHg4iwwyNN1rbRumk9V_cQq_fsiM'
	file_get_contents($SendLink);
}
function GetTemplateMsgList($appid,$secret,$TokenFile)
{
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	//$TokenPath=GetAccessTokenFilePath();
	$token=GetAccessToken($TokenFile,$url);
	
	$url="https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$token}";
	//$dataRes = request_post($url, urldecode($json_template));
	$result = https_post($url,$template);
	//print_r($result);echo "<br>";
	//echo $url;echo "<br>";
	//echo $result;echo "<br>";
	$data=json_decode($result,true);
	/*foreach($data[template_list] as $item)
	{
		print_r($item);
		echo "<br><br><br>";
	}*/
	return $data[template_list];
}
function SendServiceConfirmMsg($TemplateID,$msg,$ServiceType,$remark,$url,$ToUser,$appid,$secret,$TokenFile)
{
	
	$time=date("Y-m-d H:i:s",time());
	//******************
	$title=ToUTF8($msg);
	$zu=ToUTF8($ServiceType);
	$count=1;
	//$remark=date("Y-d-m H:i:s",time());
	//$remark="您的选择我们已经收到";
	//echo "到这里<br>";
	$template=array
	(
		'touser'=>$ToUser,
		'template_id'=>$TemplateID,    //ģ���id
		'url'=>$url,
		'topcolor'=>"#FF0000",
		'data'=>array
		(
			'first'=>array('value'=>$title,'color'=>"#00008B"),    //�������ι�����name     
			'keyword1'=>array('value'=>$zu,'color'=>'#00008B'),        //�������ι�����zu
			'keyword2'=>array('value'=>$count,'color'=>'#00008B'),   //ʱ��
			'remark'=>array('value'=>$remark,'color'=>'#00008B'),//�������ι�����ramain
		)
	);
	$template=json_encode($template);
	
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	$token=GetAccessToken($TokenFile,$url);
	echo $token;echo "<br>";echo $TokenFile;echo "<br>";
	//$url="https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$token}";
	$url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$token}";
	//$dataRes = request_post($url, urldecode($json_template));
	$result = https_post($url,$template);
	//echo $url;echo "<br>";
	//print_r($result);echo "<br>";
	$result=json_decode($result);
	//print_r($result);

}
?>
