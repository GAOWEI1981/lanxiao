<?
include_once("../function/Script.php");
?>
<script>	
var offset=0;
var addup=0;
var TimeSpace=4000;
var t1 = window.setInterval(CheckServer,TimeSpace); 
CheckServer();

function NextPage(obj)
{
	var va="<?php echo $PageSize;?>";	
	offset+=parseInt(va);
	CheckServer();
	return false;
}
function LastPage(obj)
{
	var va="<?php echo $PageSize;?>";	
	offset-=parseInt(va);
	if(offset<0) offset==0;
	CheckServer();
	return false;
}
function CheckServer()
{

	var name,time,msg;
	url="<?echo $FileName;?>?operate=GetList&offset="+offset;
	//alert(url);
	var ajax=InitAjax();

	//ajax.open("POST",url,false);//同步
	//ajax.send();
	
	
	text=ajax.open("GET",url,true);//异步
	ajax.send();
	ajax.onreadystatechange =function()//回调函数
	{
		try
		{
			if (ajax.readyState==4 && ajax.status==200)
			{
				responce=ajax.responseText;
				for(i=0;i<10;i++)
				{
					content=GetXMLParam(responce,i);
					//alert(content);
					if(content.length>0)
					{
						var ImgPath=GetXMLParam(content,"img");
						var msg=GetXMLParam(content,"msg");
						var time=GetXMLParam(content,"time");
						var id=GetXMLParam(content,"id");
						var ReadState=GetXMLParam(content,"read_state");
						
						obj=document.getElementById(i+"table");
						switch(ReadState)
						{
							case "unread":
								obj.style.backgroundColor="#FF0000";
								break;
							default:
								obj.style.backgroundColor="#00FF00";
								break;
						}
						
						//是否已经获得真实姓名，如果没有就用他自己的昵称
						name=GetXMLParam(content,"name");
						//alert(name);
						if(name.length==0)
							name=GetXMLParam(content,"nickname");
						
						//alert(nickname);
						obj=document.getElementById(i+"img");
						obj.src=ImgPath;
						obj=document.getElementById(i+"msg");
						//alert(msg);
						obj.innerHTML=msg;
						obj=document.getElementById(i+"time");
						obj.innerHTML=time;
						obj=document.getElementById(i+"nick");
						url="<a href='PersonalInfoEdit.php?id="+id+"&LastPage=<?echo $FileName;?>'>"+name+"</a>";
						obj.innerHTML=url;
						
						obj=document.getElementById(i+"view");
						obj.innerHTML="<a href='Personal.php?id="+id+"'>查看</a>";
						
						obj=document.getElementById(i+"line");
						obj.style.display="";
						obj=document.getElementById(i+"line1");
						obj.style.display="";
						
						
					}
					else
					{
						obj=document.getElementById(i+"line");
						obj.style.display="none";
						obj=document.getElementById(i+"line1");
						obj.style.display="none";
					}
				}
				
			}
		}catch(e)
		{
			alert(e);
		}
	}
	
	
	ShowString(addup/1000);
	addup+=TimeSpace;
	var second=addup/1000;
	if(second>600)//页面闲置超时
	{
		
		window.clearInterval(t1);//页面超时
		ShowString("页面超时，需要更新时请重新刷新"); 
	}
}
function ShowString(str)
{
	var ele=document.getElementById("textout");
	ele.innerText=str;
}
</script>
