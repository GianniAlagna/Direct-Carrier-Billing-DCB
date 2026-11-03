<?php
/**
 * QTPass door terminal - staff UI (Safari 3, full screen on a MacBook).
 * Numeric keypad input: member no. [Enter] code [Enter]
 * Plain XMLHttpRequest (2008), no framework.
 */
$terminal = isset($_GET['t']) ? htmlspecialchars($_GET['t']) : 'DOOR1';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>QTPass Door - <?php echo $terminal; ?></title>
<style type="text/css">
  body { margin: 0; background: #111; color: #fff; font: 24px "Lucida Grande", Helvetica, Arial, sans-serif; }
  #bar { background: #000; padding: 10px 20px; font-size: 16px; color: #888; }
  #bar span { float: right; }
  #input { padding: 40px; text-align: center; }
  .field { font-size: 64px; letter-spacing: 8px; background: #222; border: 2px solid #444;
           color: #fff; width: 420px; text-align: center; padding: 10px; margin: 10px; }
  .label { font-size: 18px; color: #aaa; text-transform: uppercase; }
  #result { display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; text-align: center; padding-top: 60px; }
  #result.GREEN  { background: #0a8f2e; }
  #result.ORANGE { background: #e08a00; }
  #result.RED    { background: #c0141c; }
  #photo { width: 240px; height: 320px; border: 6px solid #fff; background: #333; }
  #msg { font-size: 48px; font-weight: bold; margin-top: 20px; }
  #who { font-size: 36px; margin-top: 10px; }
  #hint { font-size: 18px; margin-top: 30px; opacity: .8; }
  #offline { display: none; background: #e08a00; color: #000; padding: 6px 20px; font-size: 16px; }
</style>
</head>
<body>
<div id="bar">QTPass &middot; <?php echo $terminal; ?> <span id="clock"></span></div>
<div id="offline">Central server unreachable &ndash; OFFLINE MODE (local cache)</div>

<div id="input">
  <div class="label">Member no.</div>
  <input id="member" class="field" maxlength="6" autocomplete="off" />
  <div class="label">Code</div>
  <input id="code" class="field" maxlength="6" autocomplete="off" />
</div>

<div id="result">
  <img id="photo" src="" alt="" />
  <div id="who"></div>
  <div id="msg"></div>
  <div id="hint">Compare face with photo &middot; press any key for next guest</div>
</div>

<script type="text/javascript">
var TERMINAL = '<?php echo $terminal; ?>';
var STAFF    = 0; // set at shift login (manager PIN screen, not shown in draft)

function $(id) { return document.getElementById(id); }

function xhr(url, params, cb) {
  var r = new XMLHttpRequest();
  r.open('POST', url, true);
  r.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  r.onreadystatechange = function () {
    if (r.readyState == 4) {
      if (r.status == 200) { cb(eval('(' + r.responseText + ')')); } // 2008: no JSON.parse in Safari 3
      else { cb({ colour: 'RED', message: 'Terminal error ' + r.status }); }
    }
  };
  var q = [];
  for (var k in params) { q.push(k + '=' + encodeURIComponent(params[k])); }
  r.send(q.join('&'));
}

function reset() {
  $('result').style.display = 'none';
  $('member').value = ''; $('code').value = '';
  $('member').focus();
}

function show(res) {
  var box = $('result');
  box.className = res.colour;
  $('msg').innerHTML = res.message;
  $('who').innerHTML = res.first_name ? res.first_name + ' &middot; ' + res.age + ' y' : '';
  $('photo').src = res.photo ? res.photo : 'nophoto.png';
  box.style.display = 'block';
  if (res.colour == 'GREEN') { setTimeout(reset, 4000); }
}

$('member').onkeydown = function (e) {
  if (e.keyCode == 13 && this.value.length > 0) { $('code').focus(); return false; }
};
$('code').onkeydown = function (e) {
  if (e.keyCode == 13 && this.value.length == 6) {
    xhr('checkin.php', { member_no: $('member').value, code: this.value,
                         terminal_id: TERMINAL, staff_id: STAFF }, show);
    return false;
  }
};
document.onkeydown = function () {
  if ($('result').style.display == 'block') { reset(); return false; }
};

setInterval(function () {
  var d = new Date();
  $('clock').innerHTML = (d.getHours() < 10 ? '0' : '') + d.getHours() + ':' +
                         (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
}, 1000);

// Offline indicator: venue node writes sync status into status.txt
setInterval(function () {
  var r = new XMLHttpRequest();
  r.open('GET', 'status.txt?' + new Date().getTime(), true);
  r.onreadystatechange = function () {
    if (r.readyState == 4) { $('offline').style.display = (r.responseText.indexOf('ONLINE') == 0) ? 'none' : 'block'; }
  };
  r.send(null);
}, 15000);

reset();
</script>
</body>
</html>
