<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport"content="width=device-width"/>
<link href="../function/InterfaceStyle.css" rel="stylesheet" type="text/css" />

<form id="TitleForm" name="TitleForm" method="post" action="">
<table width="100%" border="1" cellspacing="0" class="ProductTable">
<tr>
  <th width="66%" colspan="2" align="left">用户名：<?echo $AdminInfo['name'];?></th>
  <th width="33%" align="right"><a href="index.php">退出</a></th>
</tr>
<tr align="center">
  <td colspan="3"><table width="100%" border="0" cellpadding="0" cellspacing="4">
    <tbody>
      <tr>
        <td width="33%" align="center"><a href='MainPage.php'>信息管理</a></td>
        <td width="34%" align="center"><a href='UsersView.php?operate=UserTable'>管理员列表</a></td>
        <td width="33%" align="center"><select name="ConfigSel" id="ConfigSel">
          <option selected="selected">操作</option>
          <option value="ExpressPage.php">快递列表</option>
          <option value="ConfigMenu.php">菜单设置</option>
          <option value="ConfigPage.php">一般配置</option>
          </select></td>
        </tr>
      </tbody>
    </table></td>
</tr>
</table>
</form>
<script src="../function/jquery.js"></script>
<script>
//<a href='ConfigPage.php'>配置</a>
$("#ConfigSel").change(
function()
{
	try
	{
		var url=$(this).find("option:selected").val();
		
		location=url;
	}catch(e)
	{
		alert(e);
	}
});
</script>