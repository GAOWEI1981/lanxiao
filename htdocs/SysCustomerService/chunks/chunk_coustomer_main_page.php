<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport"content="width=device-width"/>
<link href="../function/InterfaceStyle.css" rel="stylesheet" type="text/css" />
<?
$operate=$_GET['operate'];
if(!isset($_SESSION['adminID']))
{
	Header("Location:index.php");
	return;
}
$now=time();
switch($operate)
{
case "GetList":
	//MemState("MainPage GetList B");
	$PageOffset=$_GET['offset'];
	if(strlen($PageOffset)==0) $PageOffset=0;

	$sql="select * from signup_users where last_msg is not null order by last_msg_time desc limit {$PageOffset},{$PageSize}";
	
	//LogInFile($sql,"uuuuu.txt");
	$result=mysql_query($sql);
	$i=0;
	//LogInFile(mysql_num_rows($result),"uuuuu.txt");
	while($row=mysql_fetch_array($result))
	{
		$id=$row['openid'];
		$time=date("Y-m-d",$row['last_msg_time'])."<br>".date("H:i:s",$row['last_msg_time']);	
		
		
		$TokenPath=GetAccessTokenFilePath();
		
		$user=GetUserInfo($id,"account.json",$TokenPath);
		//LogInFile($id,"uuuuu.txt");
		if(isset($user->errcode))
		{
		}
		else
		{
			
			$headimg=$user->headimgurl;
			$headimg=substr($headimg,0,strlen($headimg)-1)."46";//头像 0、46、64、96、132
			
			$content= $row['last_msg'];
			$content=mb_strimwidth($content, 0, 20, '...', 'utf8');
			$name=$row['name'];
			$nickname=$user->nickname;
			//保存一下昵称
			if(strlen($nickname)>0)
			{
				$s="update signup_users set nickname='{$nickname}' where openid='{$id}'";
				mysql_query($s);
			}
			//*******************
			//阅读状态
			$sql="insert into states (item,owner,event,time,state) values('{$id}','{$_SESSION[adminID]}','read_state','{$time}','unread')";
			mysql_query($sql);
			
			$sql="select * from states where item='{$id}' and owner='{$_SESSION[adminID]}'";
			$r=mysql_query($sql);
			$item=mysql_fetch_array($r);
			//LogInFile($item[state],"log_sql.txt");
			//*************
			
			$line="<read_state>{$item[state]}</read_state><nickname>{$nickname}</nickname><name>{$row['name']}</name><id>{$id}</id><time>{$time}</time><img>{$headimg}</img><msg>{$content}</msg>";
			echo "<{$i}>{$line}</{$i}><br>";
		}
		unset($row);
		$i++;
		
	}
	return;
case "":
	
	/*for($i=0;$i<$LineCount;$i++)
	{
		//echo "{$i}msg<br>";
		$body.="<tr id='{$i}line'>
						<td  rowspan='2' class='BottomLine'>
						<img id='{$i}img' src='NULL'></td><td style='font-size:{$FontSize}px' align='left' id='{$i}nick'>&nbsp</td><td style='font-size:{$SmallFontSize}px' id='{$i}time'>&nbsp</td>
						</tr>
						<tr id='{$i}line1' class='BottomLine'><td align='left' id='{$i}msg'>&nbsp</td><td align='right' id='{$i}view' class='BottomLine'>查看</td></tr>";
	}
	$body.="<tr><td id='textout' colspan='4'>show</td></tr>";
	$LastLink="";
	$body.="<tr><td align='4' colspan='4'><a href='MainPage.php' onclick='return LastPage(this);'>上一页</a>
					<a href='MainPage.php' onclick='return NextPage(this);'>下一页</a></td></tr></table>";
	*/

	break;
}

?>
<style type="text/css">
<!--

div {
	border-top-color: #666699;
	border-right-color: #666699;
	border-bottom-color: #666699;
	border-left-color: #666699;
	border-style: solid;
	border-width: 1px;
}

-->
</style>

<?
for($i=0;$i<$PageSize;$i++)
{
?><div>
  <table width="100%" border="0" cellspacing="0" class="ProductTable" id="<?echo "{$i}table";?>">
  <tr id="<?echo "{$i}line";?>">
    <td width="19%" rowspan="2" align="center"><img src='NULL' name="<?echo "{$i}img";?>" id="<?echo "{$i}img";?>"/></td>
    <td colspan="2" id="<?echo "{$i}nick";?>">&nbsp;</td>
    <td width="15%" rowspan="2" align="center" id="<?echo "{$i}view";?>">查看</td>
  </tr>
  <tr id="<?echo "{$i}line1";?>">
    <td width="50%" id="<?echo "{$i}msg";?>">&nbsp;</td>
    <td width="16%" align="center" ><span id="<?echo "{$i}time";?>" class="TimeText" >&nbsp;</span></td>
  </tr></table></div>
 <?
 }
 ?>
 <div>
 <table width="100%" border="0" cellspacing="0" class="ProductTable">
   
   <tr align="center">
     <td width="33%" height="40"><a href='MainPage.php' onclick='return LastPage(this);'>上一页</a></td>
     <td width="33%" id="textout" name="textout">&nbsp;</td>
     <td width="33%"><a href='MainPage.php' onclick='return NextPage(this);'>下一页</a></td>
   </tr>
 </table>
 </div>