<?php
function Draft_box_list()
{
  global $zbp;
  $p               = new Pagebar($zbp->host . '{&page=%page%}{&search=%search%}', false);
  $p->PageCount    = 15; //每页显示数量
  $p->PageBarCount = 7; //最大页数
  $p->PageNow      = (int) GetVars('page', 'GET') == 0 ? 1 : (int) GetVars('page', 'GET');
  $w               = array();
  //$arysubcate=array(array('log_CateID',2),array('log_CateID', 3),array('log_CateID', 4));
  //$w[]=array('array', $arysubcate);
  $array           = Get_Draft_list('*', $w, array(
    'log_PostTime' => 'DESC'
  ), array(
    ($p->PageNow - 1) * $p->PageCount,
    $p->PageCount
  ), array(
    'pagebar' => $p
  ), false);
  $list            = array();
  foreach ($array as $a) {
    $array2             = array();
    $array2['ID']       = $a['ID'];
    $array2['PostTime'] = $a['PostTime'];
    $array2['Title']    = strlen($a['Title']) <= 24 ? $a['Title'] : SubStrUTF8($a['Title'], 24) . '...';
    $tempcontent        = FormatString($a['Content'], '[nohtml][noenter]');
    $array2['Content']  = strlen($tempcontent) <= 40 ? $tempcontent : SubStrUTF8($tempcontent, 40) . '...';
    $list[]             = $array2;
  }
  echo json_encode($list);
}
function Draft_box_get()
{
  global $zbp;
  if (GetVars('id', 'GET') == null) {
    return false;
  }
  $id    = (int) GetVars('id', 'GET');
  $w     = array();
  $w[]   = array(
    '=',
    'log_ID',
    $id
  );
  $array = Get_Draft_list('*', $w, null, 1);
  //$select=null,$where=null,$order=null,$limit=null,$option=null
  if (count($array) > 0) {
    echo json_encode($array[0]);
    return true;
  } else {
    return false;
  }
}
function Draft_box_post()
{
  global $zbp;
  if (!isset($_POST['ID']))
    return;
  if (isset($_COOKIE['timezone'])) {
    $tz = GetVars('timezone', 'COOKIE');
    if (is_numeric($tz)) {
      date_default_timezone_set('Etc/GMT' . sprintf('%+d', -$tz));
    }
    unset($tz);
  }
  if (isset($_POST['Tag'])) {
    $_POST['Tag'] = TransferHTML($_POST['Tag'], '[noscript]');
  }
  if (!isset($_POST['AuthorID'])) {
    $_POST['AuthorID'] = $zbp->user->ID;
  } else {
    if (($_POST['AuthorID'] != $zbp->user->ID) && (!$zbp->CheckRights('ArticleAll'))) {
      $_POST['AuthorID'] = $zbp->user->ID;
    }
    if ($_POST['AuthorID'] == 0)
      $_POST['AuthorID'] = $zbp->user->ID;
  }
  if (isset($_POST['Alias'])) {
    $_POST['Alias'] = TransferHTML($_POST['Alias'], '[noscript]');
  }
  $curTime          = time();
  $_POST['PostTime'] = $curTime;
  if (!$zbp->CheckRights('ArticleAll')) {
    unset($_POST['IsTop']);
  }
  $article  = array();

  foreach ($GLOBALS['datainfo']['plugin_Draft_box'] as $key => $value) {
    if ($key == 'ID' || $key == 'Meta') {
      continue;
    }
    if (isset($_POST[$key])) {
      $article[$key] = GetVars($key, 'POST');
    }
  }
  $article['ID']   = 0;
  $article['Type'] = ZC_POST_TYPE_ARTICLE;
  FilterPost_from_Arr($article);
  $logID = SavePost_from_Arr($article);
  $rltData = array("log_ID" => $logID);
  echo json_encode($rltData);
}
function Draft_box_del()
{
  global $zbp;
  if (GetVars('id', 'GET') == null) {
    return false;
  }
  $id           = (int) GetVars('id', 'GET');
  $objData      = (object) array(
    "id" => $id
  );
  // echo $id;
  $objData->msg = Draft_Auto_del($id);
  echo json_encode($objData);
}
function FilterPost_from_Arr(&$article)
{
  global $zbp;
  $article['Title'] = strip_tags($article['Title']);
  $article['Alias'] = TransferHTML($article['Alias'], '[normalname]');
  $article['Alias'] = str_replace(' ', '', $article['Alias']);
  if ($article['Type'] == ZC_POST_TYPE_ARTICLE) {
    if (!$zbp->CheckRights('ArticleAll')) {
      $article['Content'] = TransferHTML($article['Content'], '[noscript]');
      $article['Intro']   = TransferHTML($article['Intro'], '[noscript]');
    }
  } elseif ($article['Type'] == ZC_POST_TYPE_PAGE) {
    if (!$zbp->CheckRights('PageAll')) {
      $article['Content'] = TransferHTML($article['Content'], '[noscript]');
      $article['Intro']   = TransferHTML($article['Intro'], '[noscript]');
    }
  }
}
function SavePost_from_Arr($article)
{
  global $zbp;
  $keys = array();
  foreach ($GLOBALS['datainfo']['plugin_Draft_box'] as $key => $value) {
    if (!is_array($value) || count($value) != 4)
      continue;
    $keys[] = $value[0];
  }
  $keyvalue = array_fill_keys($keys, '');
  foreach ($GLOBALS['datainfo']['plugin_Draft_box'] as $key => $value) {
    if (!is_array($value) || count($value) != 4)
      continue;
    if (!array_key_exists($key, $article))
      $article[$key] = '';
    if ($value[1] == 'boolean') {
      $keyvalue[$value[0]] = (int) $article[$key];
    } elseif ($value[1] == 'integer') {
      $keyvalue[$value[0]] = (int) $article[$key];
    } elseif ($value[1] == 'float') {
      $keyvalue[$value[0]] = (float) $article[$key];
    } elseif ($value[1] == 'double') {
      $keyvalue[$value[0]] = (float) $article[$key];
    } elseif ($value[1] == 'string') {
      $keyvalue[$value[0]] = $article[$key];
    } else {
      $keyvalue[$value[0]] = $article[$key];
    }
  }
  array_shift($keyvalue);
  $sql = $zbp->db->sql->Insert($GLOBALS['table']['plugin_Draft_box'], $keyvalue);
  $logID = $zbp->db->Insert($sql);
  //大于60条删除，删除以前的记录
  Draft_Auto_del();
  return $logID;
}
function Get_Draft_list($select = null, $where = null, $order = null, $limit = null, $option = null)
{
  global $zbp;
  if (empty($select)) {
    $select = array(
      '*'
    );
  }
  if (empty($where)) {
    $where = array();
  }
  $sql   = $zbp->db->sql->Select($GLOBALS['table']['plugin_Draft_box'], $select, $where, $order, $limit, $option); //echo($sql);exit;
  $array = $zbp->db->Query($sql);
  if (!isset($array)) {
    return array();
  } //print_r ($array);echo '<hr>';exit($type.$sql);
  $list = array();
  foreach ($array as $a) {
    $l = array();
    foreach ($GLOBALS['datainfo']['plugin_Draft_box'] as $key => $value) {
      if (!is_array($value) || count($value) != 4)
        continue;
      if (HasNameInString("log_CreateTime|log_UpdateTime", $value[0]))
        continue;
      $l[$key] = $a[$value[0]];
      //echo $a[$value[0]];
    }
    $l['PostTime'] = date("m-d H:i", $l['PostTime']);
    $list[]        = $l;
  }
  return $list;
}
function Draft_Auto_del($id = 'x')
{
  global $zbp;
  if ($id <> 'x') {
    $sql = $zbp->db->sql->Delete($GLOBALS['table']['plugin_Draft_box'], array(
      array(
        '=',
        'log_ID',
        $id
      )
    ));
    $zbp->db->Delete($sql);
  }
  $reust_conut = $zbp->db->sql->Count($GLOBALS['table']['plugin_Draft_box'], array(
    array(
      'COUNT',
      '*',
      'num'
    )
  ), '');
  $reust_conut = GetValueInArrayByCurrent($zbp->db->Query($reust_conut), 'num');
  if ($reust_conut > 60) {
    $array = Get_Draft_list('*', array(), array(
      'log_PostTime' => 'ASC'
    ), 1, '', false);
    $id    = $array[0]['ID'];
    $sql   = $zbp->db->sql->Delete($GLOBALS['table']['plugin_Draft_box'], array(
      array(
        '=',
        'log_ID',
        $id
      )
    ));
    $zbp->db->Delete($sql);
  }
  return "删除成功";
}
