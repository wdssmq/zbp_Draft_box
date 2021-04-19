Date.prototype.Format = function (fmt) {
  //author: meizz
  var o = {
    "M+": this.getMonth() + 1, //月份
    "d+": this.getDate(), //日
    "h+": this.getHours(), //小时
    "m+": this.getMinutes(), //分
    "s+": this.getSeconds(), //秒
    "q+": Math.floor((this.getMonth() + 3) / 3), //季度
    S: this.getMilliseconds(), //毫秒
  };
  if (/(y+)/.test(fmt))
    fmt = fmt.replace(
      RegExp.$1,
      (this.getFullYear() + "").substr(4 - RegExp.$1.length)
    );
  for (var k in o)
    if (new RegExp("(" + k + ")").test(fmt))
      fmt = fmt.replace(
        RegExp.$1,
        RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length)
      );
  return fmt;
};
function fnAutoSave() {
  //异步提交文章数据
  //alert('xss1');
  //ajax Begin
  $.post(
    bloghost + "/zb_users/plugin/Draft_box/main.php?act=post",
    {
      //"isajax":true,
      ID: $("#edtID").val(),
      CateID: $("#cmbCateID").val(),
      AuthorID: $("#cmbUser").val(),
      Tag: $("#edtTag").val(),
      Status: 1, //设定为草稿
      Type: $("#edtType").val(),
      Alias: $("#edtAlias").val(),
      IsTop: $("#edtIstop").val(),
      IsLock: $("#edtIslock").val(),
      Title: $("#edtTitle").val(),
      Intro: $("#editor_intro").val(),
      Content: editor_api.editor.content.get(),
      PostTime: $("#edtDateTime").val(),
      Template: $("#cmbTemplate").val(),
    },
    function (data) {
      //var data =$.parsejson(data);
      if (data.id == 0) {
        return;
      }

      var time = new Date().Format("yyyy-MM-dd hh:mm:ss");
      $("#lasttime").html(
        '上一次保存时间: <a href="javascript:void(0)" onclick="Draft_box_list_slide()" >' +
        time +
        "</a>"
      );
    },
    "json"
  ); //ajax End
}

/****************************/
// const start = 7;
const start = 60;
const step = -1;
let loop = start;
let lstHash = "";
console.log(lstHash);
function count() {
  $("#autosavetime").text(loop);
  if (loop == 0 && editor_api.editor.content.get() !== "") {
    //alert(start);
    const curHahs = fnCurHash();
    console.log(curHahs);
    if (lstHash != curHahs) {
      fnAutoSave();
      lstHash = curHahs;
    }
  }
  loop += step;
  if (loop < 0) loop = start;
  setTimeout("count()", 1000);
}

setTimeout(() => {
  count();
}, 1300);

function fnHashVal(string) {
  let hash = 0,
    i,
    chr;
  if (string.length === 0) return hash;
  for (i = 0; i < string.length; i++) {
    chr = string.charCodeAt(i);
    hash = (hash << 5) - hash + chr;
    hash |= 0; // Convert to 32bit integer
  }
  return hash;
}

function fnCurHash() {
  const body = editor_api ? editor_api.editor.content.get() : "";
  const other = ((arr) => {
    const aRlt = [];
    arr.forEach((el) => {
      aRlt.push($(`#${el}`).val().trim());
    });
    return aRlt.join("|");
  })(["edtTitle", "edtAlias", "edtTag"]);
  return fnHashVal(body + other);
}

function Draft_box_list_slide() {
  // $domDraft.slideToggle();
  $domDraft = $("#DraftList");
  Draft_box_list(1);
  if ($domDraft.is(":hidden")) {
    $domDraft
      .css({
        backgroundColor: "#fdfdfd",
        border: "1px solid #ccc",
        display: "block",
        position: "absolute",
        zIndex: "2000",
        margin: "5px 0 10px",
        padding: "10px",
        width: "820px",
        height: "410px",
        opacity: 0,
      })
      .animate({
        top:
          ($(window).height() - $domDraft.height()) / 2 + $(window).scrollTop(),
        left: ($(window).width() - $domDraft.width()) / 2,
        opacity: 1,
      })
      .draggable();
  } else {
    $domDraft.fadeOut();
  }
}

function Draft_box_list(page) {
  $domDraft = $("#DraftList");
  const curPage = arguments[0] ? arguments[0] : 1;
  const nextPage = curPage + 1;
  // console.log(curPage, nextPage);
  $.getJSON(
    bloghost + "zb_users/plugin/Draft_box/main.php?act=list&page=" + curPage,
    function (data) {
      // console.log(data);
      var str =
        '<p class="editinputname">自动保存的草稿(每页15条)：<button class="dra-btn page" type="button" onclick="Draft_box_list(' +
        nextPage +
        ')">下一页</button>&nbsp;&nbsp;&nbsp;<button onclick="$domDraft.fadeOut();return false;">关闭</button></p><table style="width: 100%;"><tr><td>时间</td><td>文章标题</td><td>内容</td><td>操作</td></tr>';
      $.each(data, function (i, v) {
        str +=
          '<tr id="dra' +
          v.ID +
          '"><td class="td20" title="' +
          v.ID +
          '">' +
          v.PostTime +
          '</td><td class="td20" title="' +
          v.ID +
          '">' +
          v.Title +
          '</td><td title="' +
          v.ID +
          '">' +
          v.Content +
          '</td><td class="td15 tdCenter">' +
          '<a onclick="return Draft_box_get(' +
          v.ID +
          ')" href="javascript:;"><img src="' +
          bloghost +
          'zb_system/image/admin/page_edit.png" alt="恢复" title="恢复" width="16"></a>&nbsp;&nbsp;&nbsp;&nbsp;' +
          '<a onclick="return Draft_box_del(' +
          v.ID +
          ')" href="javascript:;" class="button"><img src="' +
          bloghost +
          '/zb_system/image/admin/delete.png" alt="删除" title="删除" width="16"></a></td></tr>';
      });
      str += "</table>";
      // console.log(str);
      $domDraft.html(str);
      if (data.length === 0) {
        $(".dra-btn.page").text("已经是最后一页").attr("disabled", "disabled");
        return;
      }
    }
  );
  return false;
}

function Draft_box_get(id) {
  $.getJSON(
    bloghost + "/zb_users/plugin/Draft_box/main.php?act=get&id=" + id,
    function (data) {
      $("#edtType").val(data.Type);
      $("#edtTitle").val(data.Title);
      $("#editor_content").val(data.Content);

      $("#edtAlias").val(data.Alias);
      $("#edtTag").val(data.Tag);
      $("#cmbCateID").val(data.CateID);
      $("#cmbPostStatus").val(data.Status);
      $("#cmbTemplate").val(data.Template);
      $("#edtDateTime").val(data.PostTime);

      $("#edtIstop").val(data.IsTop);
      $("#edtIslock").val(data.IsLock);

      $("#editor_intro").val(data.Intro);
      editor_api.editor.content.put(data.Content);
      loop = start;
      $domDraft = $("#DraftList");
      $domDraft.fadeOut();
    }
  );
}

function Draft_box_del(id) {
  $.getJSON(
    bloghost + "/zb_users/plugin/Draft_box/main.php?act=del&id=" + id,
    function (data) {
      // console.log(data);
      $("#dra" + id + " .td15").html("已删除");
    }
  );
  return false;
}
