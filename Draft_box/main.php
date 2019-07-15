<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'ArticleEdt';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('Draft_box')) {
  $zbp->ShowError(48);
  die();
}
require 'function.php';
$Draft_box_action = GetVars('act', 'GET');
switch ($Draft_box_action) {
  case 'list':
    Draft_box_list();
    exit;
    break;
  case 'get':
    Draft_box_get();
    exit;
    break;
  case 'post':
    Draft_box_post();
    exit;
    break;
  case 'del':
    Draft_box_del();
    exit;
    break;
  default:
    exit;
    break;
}
?>
