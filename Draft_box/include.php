<?php
if (!isset($zbp)) {
  exit;
}
#注册插件
RegisterPlugin("Draft_box", "ActivePlugin_Draft_box");

$GLOBALS['table']['plugin_Draft_box'] = '%pre%plugin_draft_box';
$GLOBALS['datainfo']['plugin_Draft_box'] = $zbp->datainfo['Post'];

function ActivePlugin_Draft_box()
{
  Add_Filter_Plugin('Filter_Plugin_Edit_Response3', 'Draft_box_Main');
  Add_Filter_Plugin('Filter_Plugin_Edit_Response2', 'Draft_box_txt');
}

function Draft_box_Main()
{
  global $zbp;
  echo <<<jj
<script src="$zbp->host/zb_users/plugin/Draft_box/Draft.js" type="text/javascript"></script>
<script type="text/javascript">
$(function(){count();});
</script>
<span id="autosavetime"> 60</span> 秒后自动至<label  class="editinputname">
<a onclick="Draft_box_list_slide()" href="javascript:void(0)"  id="Draft_box" >草稿箱</a></label><br />
<div id="lasttime">上次保存时间: 无</div>
jj;
}

function Draft_box_txt()
{
  echo '<div class="ui-draggable" id="DraftList" style="display: none;position: absolute;">Loading</div>';
   # echo '<div id="sss" >ssssssss</div>';
}

function InstallPlugin_Draft_box()
{
  global $zbp;
  // $zbp->table['plugin_Draft_box'] = 'plugin_draft_box';
  // $zbp->datainfo['plugin_Draft_box'] = $zbp->datainfo['Post'];
  // 判断是否已创建，否则新建数据表
  if (!$zbp->db->ExistTable($zbp->table['plugin_Draft_box'])) {
    $s = $zbp->db->sql->CreateTable($zbp->table['plugin_Draft_box'], $zbp->datainfo['plugin_Draft_box']);
    $zbp->db->QueryMulit($s);
  }
}

function UninstallPlugin_Draft_box()
{
  global $zbp;
  return;
  if ($zbp->db->ExistTable($GLOBALS['table']['plugin_Draft_box'])) {
    $s = $zbp->db->sql->DelTable($GLOBALS['table']['plugin_Draft_box']);
    $zbp->db->QueryMulit($s);
  }
}
