<?php
function Draft_box_list()
{
  // var_dump(1);
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
    $array2['Title']    = strlen($a['Title']) <= 24 ? urlencode($a['Title']) : urlencode(substr($a['Title'], 0, 24) . '......');
    $tempcontent        = TransferHTML($a['Content'], '[nohtml]');
    $array2['Content']  = strlen($tempcontent) <= 40 ? urlencode($tempcontent) : mb_substr($tempcontent, 0, 40,"utf-8") . '......';
    $list[]             = $array2;
  }
  // print_r($list);
  // if (count($list) > 0)
  echo urldecode(json_encode($list));
  // else
  // echo '{"msg":"empty"}';
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
  $temotime          = time();
  $_POST['PostTime'] = $temotime;
  if (!$zbp->CheckRights('ArticleAll')) {
    unset($_POST['IsTop']);
  }
  $article0 = new Post(); //array();
  $article  = array();
  if (GetVars('ID', 'POST') == 0) {
    if (!$zbp->CheckRights('ArticlePub')) {
      $_POST['Status'] = ZC_POST_STATUS_AUDITING;
    }
  } else {
    $article0->LoadInfoByID(GetVars('ID', 'POST'));
    if (($article0->AuthorID != $zbp->user->ID) && (!$zbp->CheckRights('ArticleAll'))) {
      $zbp->ShowError(6, __FILE__, __LINE__);
    }
    if ((!$zbp->CheckRights('ArticlePub')) && ($article0->Status == ZC_POST_STATUS_AUDITING)) {
      $_POST['Status'] = ZC_POST_STATUS_AUDITING;
    }
  }
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
  SavePost_from_Arr($article);
  $array = Get_Draft_list('*', array(), array(
    'log_PostTime' => 'DESC'
  ), 1, '', false);
  if ($array[0]['PostTime'] = $temotime) {
    $tempid = $array[0]['ID'];
  } else {
    $tempid = 0;
  }
  echo '{  "id":   "' . $tempid . '" }';
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
      $keyvalue[$value[0]] = (integer) $article[$key];
    } elseif ($value[1] == 'integer') {
      $keyvalue[$value[0]] = (integer) $article[$key];
    } elseif ($value[1] == 'float') {
      $keyvalue[$value[0]] = (float) $article[$key];
    } elseif ($value[1] == 'double') {
      $keyvalue[$value[0]] = (double) $article[$key];
    } elseif ($value[1] == 'string') {
      $keyvalue[$value[0]] = $article[$key];
    } else {
      $keyvalue[$value[0]] = $article[$key];
    }
  }
  array_shift($keyvalue);
  $id_field = reset($GLOBALS['datainfo']['plugin_Draft_box']);
  $id_name  = key($GLOBALS['datainfo']['plugin_Draft_box']);
  $id_field = $id_field[0];
  if ($article['ID'] == 0) {
    $sql = $zbp->db->sql->Insert($GLOBALS['table']['plugin_Draft_box'], $keyvalue);
    //大于60条删除，删除以前的记录
    Draft_Auto_del();
    $article['ID'] = $zbp->db->Insert($sql);
  } else {
    $sql = $zbp->db->sql->Update($GLOBALS['table']['plugin_Draft_box'], $keyvalue, array(
      array(
        '=',
        $id_field,
        $article['ID']
      )
    ));
    Draft_Auto_del();
    return $zbp->db->Update($sql);
  }
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
      $l[$key] = $a[$value[0]];
      //echo $a[$value[0]];
    }
    $l['PostTime'] = date("Y-m-d H:i:s", $l['PostTime']);
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