<?

class SysConfig
{
	public $ConfigContent;
	public $FilePath;
	function  __construct($path)
	{
		
		$this->ConfigContent=file_get_contents($path);
		$this->FilePath=$path;
		/*//返利设置
		$key="{$row[openid]}_rebate_one";
		$OneRebate=GetXMLParam($config,$key);
		$key="{$row[openid]}_rebate_two";
		$TwoRebate=GetXMLParam($config,$key);*/
	}
	public function GetParam($key)
	{
		return GetXMLParam($this->ConfigContent,$key);
	}
	public function SetParam($key,$value)
	{
		$config=$this->ConfigContent;
		$config=SetXMLParam($config,$key,$value);
		$this->ConfigContent=$config;
		file_put_contents($this->FilePath,$config);
	}
	public function DelParamA($content,$key)
	{
		$value=GetXMLParam($content,$key);
		//echo $key;echo "<br>";
		//echo $value;echo "<br>";
		$mi="<{$key}>{$value}</{$key}>";
		//echo $mi;echo "1111<br>";
		return str_replace($mi,"",$content);
	}
};
$LocalSysConfig=new SysConfig("ConfigInfo.inf");
class RebateConfig extends SysConfig
{
	public $OneClassName="_rebate_one";
	public $TwoClassName="_rebate_two";
	public $NormalRebate=0.05;//默认返利
	function __construct($path)
	{
		parent::__construct($path);
	}
	public function GetRebate($id)
	{
		//返利设置
		$key="{$id}{$this->OneClassName}";
		//echo "{$key}<br>";
		$value[]=$this->GetParam($key);
		$key="{$id}{$this->TwoClassName}";
		//echo "{$key}LLLLLLL<br>";
		$value[]=$this->GetParam($key);
		
		for($i=0;$i<count($value);$i++)
		{
			if(strlen($value[$i])==0) $value[$i]=$this->NormalRebate;
		}
		
		return $value;
	}
	public function SetRebate($id,$one,$two)
	{
		$key="{$id}{$this->OneClassName}";
		$value=floatval($one);
		$this->SetParam($key,$value);
		
		$key="{$id}{$this->TwoClassName}";
		$value=floatval($two);
		$this->SetParam($key,$value);
	}
	public function GetRebateInfo($UserID,$time)
	{
		$Rebate=$this->GetRebate($UserID);
		$info=GetClientsPayments_OneLeve($UserID,$time);
		$re[OneExpress]=$info[express];//快递附加费
		$re[OnePayment]=$info[pay];//实付款
		$re[OneRebate]=($info[pay]-$info[express])*$Rebate[0];//print_r($info);echo "<br>";
		$info=GetClientsPayments_TwoLeve($UserID,$time);
		$re[TwoExpress]=$info[express];//快递附加费
		$re[TwoPayment]=$info[pay];
		$re[TwoRebate]=($info[pay]-$info[express])*$Rebate[1];
		return $re;
	}
	public function RebateExe($UserID,$time)
	{
		$info=$this->GetRebateInfo($UserID,$time);
		$gross=$info[OneRebate]+$info[TwoRebate];
		$CurTime=time();
		$sql="insert into account_book (OrderID,time,gross) values('{$UserID}','{$CurTime}','{$gross}')";
		//echo $sql;echo "<br>";
		mysql_query($sql);
	}
	public function GetLastRebate($UserID)
	{
		$sql="select * from account_book where OrderID='{$UserID}' order by time desc";
		$result=mysql_query($sql);
		$item=mysql_fetch_array($result);
		//$time=$item[time];//获取最近一次分红时间
		return $item;
	}
}
class UsersDatabase
{
	public function GetUsersInfo($owner)
	{
		$one=GetClientsPayments_OneLeve($owner);
		$result=mysql_query($one[0]);
		$re[OneTotalNum]=mysql_num_rows($result);
		$sql="select a.* from ({$one[0]}) a where a.phone is not null and a.phone<>''";
		$result=mysql_query($sql);
		$re[OneRegisterNum]=mysql_num_rows($result);
		
		$two=GetClientsPayments_TwoLeve($owner);
		$result=mysql_query($two[0]);
		$re[TwoTotalNum]=mysql_num_rows($result);
		$sql="select a.* from ({$two[0]}) a where a.phone is not null and a.phone<>''";
		$result=mysql_query($sql);
		$re[TwoRegisterNum]=mysql_num_rows($result);
		return $re;
	}
	public function ChangeUserOwner($Owner,$User)
	{
		
		if($Owner==$User || strlen($User)==0 || strlen($Owner)==0)
		{
			//echo "change owner false11<br>";
			return false;
		}
		
		$UserInfo=GetItemA("signup_users","openid",$User);
		$OwnerInfo=GetItemA("signup_users","openid",$Owner);
		if($OwnerInfo[owner]==$User)//不能互相包含，不能互相为上下级关系
		{

			LogInFile("{$OwnerInfo[name]}:{$Owner}\r\n企图更改用户 {$UserInfo[name]}:{$User}上下级关系被拒绝，原因是已经存在上下级关系","UsersOwnerChangeRecord.txt");
			//echo "change owner false33<br>";
			return false;
		}
		if(strlen($UserInfo[owner])==0)
		{
			
		}
		else//假若已经有归属了要更改上下属关系
		{
			//$item=GetItemA("signup_users","openid",$Owner);
			//if($item[user_type]=="5" && $this->IsOwner($Owner,$User) || $this->IsOwner($User,$Owner))//不能互相包含
			
			if($OwnerInfo[user_type]==5 || $OwnerInfo[user_type]=="5")//权限管理员可以任意改变归属关系
			{
				//echo "change owner666<br>";
				//echo "can change<br>";
				LogInFile("权限管理员 {$OwnerInfo[name]}:{$Owner}\r\n更改了用户 {$UserInfo[name]}:{$User}的上下级关系","UsersOwnerChangeRecord.txt");
				//return false;
			}
			else
			{
				LogInFile("{$OwnerInfo[name]}:{$Owner}\r\n企图更改用户 {$UserInfo[name]}:{$User}的上下级关系被拒绝","UsersOwnerChangeRecord.txt");
				return false;
			}
		}
		//echo $OwnerInfo[owner]." ".$User;echo "<br>";
		LogInFile("{$UserInfo[name]}:{$User}\r\n成为了{$OwnerInfo[name]}:{$Owner}\r\n的会员","UsersOwnerChangeRecord.txt");
		$sql="update signup_users set owner='{$Owner}' where openid='{$User}'";
		LogInFile($sql,"res.txt");
		mysql_query($sql);
		//echo "change owner55<br>";
		return true;
		
	}
	public function IsOwner($Owner,$User)
	{
		if($Owner==$User) return true;
		/*$sql="select openid from signup_users where owner='{$Owner}'";
		//echo $one;echo "<br><br>";
		$result=mysql_query($sql);
		while($row=mysql_fetch_array($result))
		{
			if($row[openid]==$User) 
			{
				//echo "repeat<br>";
				return true;
			}
		}
		$sql="select openid from signup_users where owner in ({$sql})";
		$result=mysql_query($sql);
		while($row=mysql_fetch_array($result))
		{
			if($row[openid]==$User) 
			{
				//echo "repeat<br>";
				return true;
			}
		}
		
		//echo "not repeat<br>";*/
		do
		{
			$item=GetItemA("signup_users","openid",$User);
			if($item[owner]==$Owner)
			{
				//echo "father<br>";
				return true;
			}
			$User=$item[owner];
		}while(strlen($item[owner])>0);
		//echo "not father<br>";
		return false;
	}
}
function UpdateMsgState($fromUsername,$event)
{
	$time=time();
	//LogInFile($time,"sql.txt");
	if(strlen($fromUsername)>0)
	{
		$TokenPath=GetAccessTokenFilePath();
		//LogInFile($TokenPath,"sql.txt");
		$user=GetUserInfo($fromUsername,"account.json",$TokenPath);
		$sql="update signup_users set last_msg='{$event}',last_msg_time='{$time}',nickname='{$user->nickname}' where openid='{$fromUsername}'";
		mysql_query($sql);
		
		$sql="update states set state='unread' where item='{$fromUsername}' ";
		//LogInFile($sql,"log_sql.txt");
		mysql_query($sql);
	}
	//*********
}
function GetClientsPayments_TwoLeve($admin,$time)
{
	//echo "sdfsdf";
	if(strlen($time)==0) $time=0;
	//echo "{$time}<tow><><><br>";
	
	
	$users="select openid from signup_users where owner='{$admin}'";
	$users="select * from signup_users where owner in ({$users}) order by name desc,phone desc";//三级
	$sql="SELECT
		account_book.id as AccountID,
		account_book.product,
		account_book.cost,
		account_book.price,
		account_book.count,
		account_book.gross,
		orders.ID,
		orders.creater,
		signup_users.name,
		signup_users.openid,
		account_book.type,
		account_book.title,
		account_book.time,
		account_book.cost_express
		FROM
		account_book
		LEFT JOIN orders ON orders.ID = account_book.OrderID
		LEFT JOIN signup_users ON signup_users.openid = orders.creater
		";
	$sql="select a.* from ({$sql}) a where a.type<>'loan' and a.type<>'Loan' and a.time > '{$time}'";
	$sql="select a.*,count(AccountID) as ItemCount,sum(cast(a.gross as decimal(8,3))) as MoneyTotal,sum(cast(a.cost_express as decimal(8,3))) as ExpressTotal from ({$sql}) a group by creater";
	
	$sql="select a.phone,a.openid,a.name,a.remark,b.product,b.gross,b.ItemCount,b.MoneyTotal,b.ExpressTotal from ({$users}) a left join ({$sql}) b on a.openid=b.openid";
	$result=mysql_query($sql);
	//echo $sql;echo "<br>";
	$gross=0;$express=0;
	while($row=mysql_fetch_array($result))
	{
		$UserIds[]=$row[openid];
		$value=(float)$row[MoneyTotal];
		$gross+=$value;
		$value=(float)$row[ExpressTotal];
		$express+=$value;
		//echo $row[creater]."--{$value}-{$row[MoneyTotal]}";echo "<br>";
	}
	$re[]=$sql;
	$re[pay]=(float)$gross;
	$re[express]=(float)$express;
	$re[UserIds]=$UserIds;
	return $re;
	//echo $sql;echo "<br>";
	
}
function GetClientsPayments_OneLeve($admin,$time)
{
	if(strlen($time)==0) $time=0;
	//echo "{$time}<tow><><><br>";
	
	$users="select * from signup_users where owner='{$admin}' order by name desc,phone desc";
	
	//$users="select openid from signup_users where owner in ({$users})";//三级
	$sql="SELECT
		account_book.id as AccountID,
		account_book.product,
		account_book.cost,
		account_book.price,
		account_book.count,
		account_book.gross,
		orders.ID,
		orders.creater,
		signup_users.name,
		signup_users.openid,
		account_book.type,
		account_book.title,
		account_book.time,
		account_book.cost_express
		FROM
		account_book
		LEFT JOIN orders ON orders.ID = account_book.OrderID
		LEFT JOIN signup_users ON signup_users.openid = orders.creater
		";
	$sql="select a.* from ({$sql}) a where a.type<>'loan' and a.type<>'Loan' and a.time > '{$time}'";
	//
	$sql="select a.*,count(AccountID) as ItemCount,sum(cast(a.gross as decimal(8,3))) as MoneyTotal,sum(cast(a.cost_express as decimal(8,3))) as ExpressTotal from ({$sql}) a group by creater";
	
	$sql="select a.phone,a.openid,a.name,a.remark,b.product,b.gross,b.ItemCount,b.MoneyTotal,b.ExpressTotal from ({$users}) a left join ({$sql}) b on a.openid=b.openid";
	$result=mysql_query($sql);
	//LogInFile($sql,"log.txt");

	$gross=0;$express=0;
	while($row=mysql_fetch_array($result))
	{
		$UserIds[]=$row[openid];
		$value=(float)$row[MoneyTotal];
		$gross+=$value;
		$value=(float)$row[ExpressTotal];
		$express+=$value;
		//echo $row[creater]."--{$value}-{$row[MoneyTotal]}";echo "<br>";
	}
	$re[]=$sql;
	$re[pay]=(float)$gross;
	$re[express]=(float)$express;
	$re[UserIds]=$UserIds;
	return $re;
	//echo $sql;echo "<br>";
	
}
?>