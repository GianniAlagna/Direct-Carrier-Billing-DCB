<?php
/**
 * QTPass iPhone web app (Mobile Safari, 2007/2008 "web clip").
 * Secondary channel: registration, balance, top-up. Online only; the door
 * code itself is produced by the native app or by SMS (IN <no>).
 *
 * Add to Home Screen -> full screen thanks to apple-mobile-web-app-capable
 * (iPhone OS 2.1+).
 *
 * Draft: the form handlers join.php / topup.php are not included; they call
 * the same logic as sms/inbound.php (JOIN) and api/topup_start.php.
 */
$page = isset($_GET['p']) ? $_GET['p'] : 'home';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=320, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<link rel="apple-touch-icon" href="icon57.png" />
<title>QT Pass</title>
<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
<div class="toolbar"><h1>QT Pass</h1></div>

<?php if ($page == 'home'): ?>
  <ul class="menu">
    <li><a href="?p=join">Become a member</a></li>
    <li><a href="?p=balance">Balance &amp; history</a></li>
    <li><a href="?p=topup">Top up</a></li>
    <li><a href="?p=sms">Pay by SMS - how it works</a></li>
  </ul>
  <p class="note">Get the QT Pass app from the App Store for offline door codes.</p>

<?php elseif ($page == 'join'): ?>
  <form method="post" action="join.php" class="panel">
    <label>First name</label><input name="first_name" />
    <label>Last name</label><input name="last_name" />
    <label>Date of birth (DD.MM.YYYY)</label><input name="birth_date" />
    <label>Mobile number</label><input name="msisdn" type="tel" value="07" />
    <label class="check"><input type="checkbox" name="tc" value="1" /> I accept the T&amp;C and privacy notice</label>
    <label class="check"><input type="checkbox" name="optin" value="1" /> SMS news about events (optional)</label>
    <input type="submit" value="Send verification SMS" class="button" />
    <p class="note">Your ID is checked at your first visit. Entry and alcohol rules (16+/18+) apply.</p>
  </form>

<?php elseif ($page == 'topup'): ?>
  <form method="post" action="topup.php" class="panel">
    <label>Member no.</label><input name="member_no" type="tel" />
    <label>Amount</label>
    <select name="amount">
      <option value="20">CHF 20.-</option><option value="50">CHF 50.-</option>
      <option value="100">CHF 100.-</option><option value="200">CHF 200.-</option>
    </select>
    <input type="submit" value="Pay by card (+10% bonus)" class="button" />
    <p class="note">You are redirected to the secure payment page of our payment provider.
       QT never sees your card data.</p>
  </form>

<?php elseif ($page == 'sms'): ?>
  <div class="panel">
    <h2>Any phone works</h2>
    <p><b>Top up:</b> SMS <code>TOP</code> to <b>9391</b> (CHF 10.-/SMS) or <b>9392</b> (CHF 20.-/SMS),
       charged on your phone bill / prepaid credit.</p>
    <p><b>Door code:</b> SMS <code>IN 4711</code> (your member no.) to <b>939</b>.</p>
    <p><b>Balance:</b> SMS <code>BAL</code> to 939. &nbsp;<b>Block:</b> <code>STOP 4711</code> (free).</p>
    <p class="note">939: standard SMS fee of your provider. Prices incl. VAT. Example numbers.</p>
  </div>

<?php else: ?>
  <div class="panel"><p>Please log in with your member no. and an SMS code.</p></div>
<?php endif; ?>

<p class="footer"><a href="?p=home">Home</a> &middot; QT Records &middot; concept 2008</p>
</body>
</html>
