<?php
/*
    CoreManager, PHP Front End for ArcEmu, MaNGOS, and TrinityCore
    Copyright (C) 2010-2013  CoreManager Project
    Copyright (C) 2009-2010  ArcManager Project

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


require_once "header.php";

//#############################################################################
// Login
//#############################################################################
function dologin()
{
  global $corem_db, $sql, $core;

  if ( empty($_POST["login"]) || empty($_POST["password"]) )
    redirect("login.php?error=2");

  $user_name  = $sql["mgr"]->quote_smart($_POST["true_login"]);
  $user_pass  = $sql["mgr"]->quote_smart($_POST["password"]);

  if ( ( strlen($user_name) > 255 ) || ( strlen($user_pass) > 255 ) )
    redirect("login.php?error=1");

  // ArcEmu: Detect whether account uses clear or encrypted password
  if ( $core == 1 )
  {
    $pass_query = "SELECT * FROM accounts WHERE login='".$user_name."' AND encrypted_password<>''";
    $pass_result = $sql["logon"]->query($pass_query);
    $arc_encrypted = $sql["logon"]->num_rows($pass_result);
  }

  // check for matching login
  if ( $core == 1 )
  {
    if ( $arc_encrypted )
      $query = "SELECT *, encrypted_password AS passData FROM accounts WHERE login='".$user_name."'";
    else
      $query = "SELECT *, SHA(CONCAT(UPPER(login), ':', UPPER(password))) AS passData FROM accounts WHERE login='".$user_name."'";
  }
  else
    $query = "SELECT *, sha_pass_hash AS passData FROM account WHERE username='".$user_name."'";

  $name_result = $sql["logon"]->query($query);
  $name_result = $sql["logon"]->fetch_assoc($name_result);

  $pass_data = $name_result["passData"];

  if ( $_POST["password"] == sha1(strtoupper($pass_data).$_SESSION["pub_key"]) )
    $user_pass = $pass_data;

  // final check for proper login
  if ( $core == 1 )
  {
    if ( $arc_encrypted )
      $query = "SELECT * FROM accounts WHERE login='".$user_name."' AND encrypted_password='".$user_pass."'";
    else
      $query = "SELECT * FROM accounts WHERE login='".$user_name."' AND SHA(CONCAT(UPPER(login), ':', UPPER(password)))='".$user_pass."'";
  }
  else
    $query = "SELECT * FROM account WHERE username='".$user_name."' AND sha_pass_hash='".$user_pass."'";

  $result = $sql["logon"]->query($query);
  $s_result = $sql["mgr"]->query("SELECT SecurityLevel AS gm FROM config_accounts WHERE Login='".$user_name."'");
  $temp = $sql["mgr"]->fetch_assoc($s_result);

  if ( $temp["gm"] == NULL )
    $temp["gm"] = 0;

  if ( $temp["gm"] >= 1073741824 )
    $temp["gm"] -= 1073741824;

  $_SESSION["gm_lvl"] = $temp["gm"];

  //we need this later
  //unset($user_name);

  if ( $sql["logon"]->num_rows($result) == 1 )
  {
    if ( $core == 1 )
      $acct = $sql["logon"]->result($result, 0, 'acct');
    else
      $acct = $sql["logon"]->result($result, 0, "id");

    if ( $core == 1 )
      $ban_query = "SELECT banned AS unbandate, banreason FROM accounts WHERE login='".$user_name."' AND banned<>0";
    else
      $ban_query = "SELECT unbandate, banreason FROM account_banned WHERE id='".$acct."' AND active=1";

    $ban_result = $sql["logon"]->query($ban_query);
    $ban = $sql["logon"]->fetch_assoc($ban_result);

    if ( $sql["logon"]->num_rows($ban_result) != 0 )
    {
      $info = lang("login", "ban_reason")." ".$ban["banreason"];
      $info .= "/br/"; // because we do a little XSS prevention, we'll replace this with <br /> below
      $info .= lang("login", "unbandate")." ".date("g:i A", $ban["unbandate"])." on ".date("d-M-Y", $ban["unbandate"]);
      redirect("login.php?error=3&info=".$info);
    }
    else
    {
      $_SESSION["user_id"] = $acct;
      if ( $core == 1 )
        $_SESSION["login"] = $sql["logon"]->result($result, 0, "login");
      else
        $_SESSION["login"] = $sql["logon"]->result($result, 0, "username");
      // if we got a screen name, we'll want it later.
      $_SESSION["screenname"] = $name["ScreenName"];
      //gets our numerical level based on Security Level.
      $_SESSION["user_lvl"] = gmlevel($temp["gm"]);
      $_SESSION["realm_id"] = $sql["logon"]->quote_smart($_POST["realm"]);
      $_SESSION["client_ip"] = ( ( isset($_SERVER["REMOTE_ADDR"]) ) ? $_SERVER["REMOTE_ADDR"] : getenv("REMOTE_ADDR") );
      $_SESSION["logged_in"] = true;

      if ( isset($_POST["remember"]) && $_POST["remember"] != '' )
      {
        setcookie(   "corem_login", bin2hex(gzcompress($_SESSION["login"])), time() + 60*60*24*30);
        setcookie("corem_password", bin2hex(gzcompress($user_pass)), time() + 60*60*24*30);
        setcookie("corem_realm_id", $_SESSION["realm_id"], time() + 60*60*24*30);
      }
      redirect("index.php");
    }
  }
  else
  {
    redirect("login.php?error=1");
  }
}


//#################################################################################################
// Print login form
//#################################################################################################
function login()
{
  global $output, $characters_db, $server, $remember_me_checked, $sql, $core, $site_encoding;
  
  $override_remember_me = $_COOKIE["corem_override_remember_me"];
  // if the cookie doesn't exist, we default to showing
  if ( !isset($override_remember_me) )
    $override_remember_me = 1;

  $output .= '
          <script type="text/javascript" src="libs/js/sha1.js"></script>
          <script type="text/javascript">
            // <![CDATA[
              function dologin ()
              {
                var myForm = document.getElementById("form");

                passhash = hex_sha1(myForm.true_login.value.toUpperCase()+":" + myForm.login_pass.value.toUpperCase()).toUpperCase();
                myForm.password.value = hex_sha1(passhash + myForm.pub_key.value);

                do_submit();
              }

              function get_username()
              {
                var myForm = document.getElementById("form");

                user = myForm.login.value;

                if ( user != "" )
                {
                  login = "";
                  obj = new XMLHttpRequest();
                  obj.onreadystatechange = function()
                  {
                    if ( obj.readyState == 4 )
                    {
                      eval("result = " + obj.responseText);
                      login = result["result"];

                      myForm.true_login.value = login;
                    }
                  }
                  obj.open("GET", "libs/get_username_lib.php?username=" + user, true);
                  obj.send(null);
                }
              }

              function got_focus()
              {
                document.getElementById("form").login.select();
                get_username();
              }
            // ]]>
          </script>
          <div class="half_frame fieldset_border center">
            <span class="legend">'.lang("login", "login").'</span>
            <form method="post" action="login.php?action=dologin" id="form" onsubmit="return dologin()">
              <div>
                <input type="hidden" name="password" value="" maxlength="256" />
                <input type="hidden" name="pub_key" value="'.$_SESSION["pub_key"].'" />
                <input type="hidden" name="true_login" value="" />
              </div>
              <table class="hidden" id="login_table">
                <tr>
                  <td colspan="3">
                    <hr />
                  </td>
                </tr>
                <tr>
                  <td align="right" valign="top" style="width: 35%;">'.lang("login", "username").' :</td>
                  <td>&nbsp;</td>
                  <td align="left">
                    <input type="text" name="login" size="24" maxlength="16" onfocus="got_focus();" onchange="get_username();" />
                    <br />
                    '.lang("login", "or_screenname").'
                  </td>
                </tr>
                <tr>
                  <td align="right">'.lang("login", "password").' :</td>
                  <td>&nbsp;</td>
                  <td align="left">
                    <input type="password" name="login_pass" size="24" maxlength="40" />
                  </td>
                </tr>';

  $result = $sql["mgr"]->query('SELECT `Index` AS id, Name AS name FROM config_servers LIMIT 10');

  if ( ( $sql["mgr"]->num_rows($result) > 1 ) && ( count($server) > 1 ) && ( count($characters_db) > 1 ) )
  {
    $output .= '
                <tr align="right">
                  <td>'.lang("login", "select_realm").' :</td>
                  <td>&nbsp;</td>
                  <td align="left">
                    <select name="realm" id="login_realm">';

    while ( $realm = $sql["mgr"]->fetch_assoc($result) )
      if ( isset($server[$realm["id"]]) )
        $output .= '
                      <option value="'.$realm["id"].'" '.( $_SESSION["realm_id"] == $realm["id"] ? 'selected="selected"' : '' ).'>'.htmlentities($realm["name"], ENT_COMPAT, $site_encoding).'</option>';
                      
    $output .= '
                    </select>
                  </td>
                </tr>';
  }
  else
    $output .= '
                <tr>
                  <td style="display: none;">
                    <input type="hidden" name="realm" value="'.$sql["mgr"]->result($result, 0, "id").'" />
                  </td>
                </tr>';

  $output .= '
                <tr>
                  <td align="right">'.lang("login", "remember_me").' : </td>
                  <td>&nbsp;</td>
                  <td align="left">
                    <input type="checkbox" name="remember" value="1"'.( ( $remember_me_checked && $override_remember_me ) ? ' checked="checked"' : '' ).' />
                  </td>
                </tr>
                <tr>
                  <td colspan="3"></td>
                </tr>
                <tr>
                  <td colspan="3">
                    <div style="width: 290px;" class="center">
                      <input type="submit" value="" style="display:none" />';

  makebutton(lang("login", "not_registrated"), 'register.php" type="wrn', 130);
  makebutton(lang("login", "login"), 'javascript:dologin()" type="def', 130);

  $output .= '
                    </div>
                  </td>
                </tr>
                <tr align="center">
                  <td colspan="3">
                    <a href="register.php?action=pass_recovery">'.lang("login", "pass_recovery").'</a>
                  </td>
                </tr>
                <tr>
                  <td colspan="3">
                    <hr />
                  </td>
                </tr>
              </table>
              <script type="text/javascript">
                // <![CDATA[
                  document.getElementById("form").login.focus();
                // ]]>
              </script>
            </form>
            <br />
          </div>
          <br />
          <br />';
}


//#################################################################################################
// Login via set cookie
//#################################################################################################
function do_cookie_login()
{
  global $corem_db, $sql, $core;

  if ( empty($_COOKIE["corem_login"]) || empty($_COOKIE["corem_password"]) || empty($_COOKIE["corem_realm_id"]) )
    redirect("login.php?error=2");

  $user_name = $sql["logon"]->quote_smart($_COOKIE["corem_login"]);
  $user_pass = $sql["logon"]->quote_smart($_COOKIE["corem_password"]);

  // see if our login & password cookies are old or new form...
  if ( !preg_match("/^([A-Fa-f0-9]{2})*$/", $user_name) )
  {
    // we have old form cookies, we'll rebuild them to new form and go from there
    setcookie("corem_login",    bin2hex(gzcompress($user_name)), time() + 60*60*24*30);
    setcookie("corem_password", bin2hex(gzcompress($user_pass)), time() + 60*60*24*30);

    redirect("login.php");
  }
  else
  {
    // we have new form cookies
    $user_name = ( ( isset($_COOKIE["corem_login"]) ) ? gzuncompress(pack("H*" , $_COOKIE["corem_login"])) : NULL );
		$user_pass = ( ( isset($_COOKIE["corem_password"]) ) ? gzuncompress(pack("H*" , $_COOKIE["corem_password"])) : NULL );
  }

  // Users may log in using either their username or screen name
  // check for matching login
  if ( $core == 1 )
    $query = "SELECT * FROM accounts WHERE login='".$user_name."'";
  else
    $query = "SELECT * FROM account WHERE username='".$user_name."'";

  $name_result = $sql["logon"]->query($query);

  if ( !$sql["logon"]->num_rows($name_result) )
  {
    // if we didn't find one, check for matching screen name
    $query = "SELECT * FROM config_accounts WHERE ScreenName='".$user_name."'";
    $name_result = $sql["mgr"]->query($query);

    if ( $sql["mgr"]->num_rows($name_result) )
    {
      $name = $sql["mgr"]->fetch_assoc($name_result);
      $user_name = $name["Login"];
    }
  }
  else
  {
    // we'll still need the screen name if we have one
    $query = "SELECT * FROM config_accounts WHERE Login='".$user_name."'";
    $name_result = $sql["mgr"]->query($query);
    $name = $sql["mgr"]->fetch_assoc($name_result);
  }
  // if we didn't find the name given for either entries, then the name will come up bad below

  // ArcEmu: Detect whether account uses clear or encrypted password
  if ( $core == 1 )
  {
    $pass_query = "SELECT * FROM accounts WHERE login='".$user_name."' AND encrypted_password<>''";
    $pass_result = $sql["logon"]->query($pass_query);
    $arc_encrypted = $sql["logon"]->num_rows($pass_result);
  }

  if ( $core == 1 )
  {
    if ( $arc_encrypted )
      $query = "SELECT * FROM accounts WHERE login='".$user_name."' AND encrypted_password='".$user_pass."'";
    else
      $query = "SELECT * FROM accounts WHERE login='".$user_name."' AND SHA(CONCAT(UPPER(login), ':', UPPER(password)))='".$user_pass."'";
  }
  else
    $query = "SELECT *, username AS login FROM account WHERE username='".$user_name."' AND sha_pass_hash='".$user_pass ."'";

  $result = $sql["logon"]->query($query);

  $s_result = $sql["mgr"]->query("SELECT SecurityLevel AS gm FROM config_accounts WHERE Login='".$user_name."'");
  $temp = $sql["mgr"]->fetch_assoc($s_result);

  if ( $temp["gm"] == NULL )
    $temp["gm"] = 0;

  if ( $temp["gm"] >= 1073741824 )
    $temp["gm"] -= 1073741824;

  $_SESSION["gm_lvl"] = $temp["gm"];

  if ( $sql["logon"]->num_rows($result) )
  {
    if ( $core == 1)
      $acct = $sql["logon"]->result($result, 0, "acct");
    else
      $acct = $sql["logon"]->result($result, 0, "id");
    
    if ( $core == 1 )
      $ban_query = "SELECT COUNT(*) FROM accounts WHERE acct='".$acct."' AND banned='1'";
    else
      $ban_query = "SELECT COUNT(*) FROM account_banned WHERE id='".$acct."' AND active='1'";

    if ( $sql["logon"]->result($sql["logon"]->query($ban_query), 0) )
      redirect("login.php?error=3");
    else
    {
      $_SESSION["user_id"] = $acct;
      $_SESSION["login"] = $sql["logon"]->result($result, 0, 'login');
      // if we got a screen name, we'll want it later.
      $_SESSION["screenname"] = $name["ScreenName"];
      //gets our numerical level based on ArcEmu level.
      $_SESSION["user_lvl"] = gmlevel($temp["gm"]);
      $_SESSION["realm_id"] = $sql["logon"]->quote_smart($_COOKIE["corem_realm_id"]);
      $_SESSION["client_ip"] = ( ( isset($_SERVER["REMOTE_ADDR"]) ) ? $_SERVER["REMOTE_ADDR"] : getenv("REMOTE_ADDR") );
      $_SESSION["logged_in"] = true;
      redirect("index.php");
    }
  }
  else
  {
    setcookie (   "corem_login", "", time() - 3600);
    setcookie ("corem_realm_id", "", time() - 3600);
    setcookie ("corem_password", "", time() - 3600);

    redirect("login.php?error=1");
  }
}


//#################################################################################################
// MAIN
//#################################################################################################
if ( isset($_COOKIE["corem_login"]) && isset($_COOKIE["corem_password"]) && isset($_COOKIE["corem_realm_id"]) && empty($_GET["error"]) )
  do_cookie_login();

$err = ( ( isset($_GET["error"]) ) ? $_GET["error"] : NULL );
$info = ( ( isset($_GET["info"]) ) ? $_GET["info"] : NULL );

$output .= '
      <div class="bubble">
          <div class="top">';

if ( $err == 1 )
  $output .=  '
            <h1><span class="error">'.lang("login", "bad_pass_user").'</span></h1>';
elseif ( $err == 2 )
  $output .=  '
            <h1><span class="error">'.lang("login", "missing_pass_user").'</span></h1>';
elseif ( $err == 3 )
{
  $output .=  '
            <h1><span class="error">'.lang("login", "banned_acc").'</span></h1>';
  if ( isset($info) )
  {
    $info = htmlspecialchars($info);
    $info = str_replace("/br/", "<br />", $info);
    $output .= '<h1>'.$info.'</h1>';
  }
}
elseif ( $err == 5 )
{
  $output .=  '
            <h1><span class="error">'.lang("login", "no_permision").'</span></h1>';
  if ( isset($info) )
    $output .= '<h1><span class="error">'.lang("login", "req_permision").': '.$info.'</span></h1>';
}
elseif ( $err == 6 )
{
  $output .=  '
            <h1><span class="error">'.lang("login", "after_registration").'</span></h1>';
  if ( isset($info) )
    $output .= '<h1><span class="error">'.lang("register", "referrer_not_found").'</span></h1>';
}
elseif ( $err == 7 )
  $output .=  '
            <h1><span class="error">'.lang("login", "after_activation").'</span></h1>';
elseif ( $err == 8 )
{
  $output .=  '
            <h1><span class="error">'.lang("login", "confirm_sent").'</span></h1>';
  if ( isset($info) )
    $output .= '<h1><span class="error">'.lang("register", "referrer_not_found").'</span></h1>';
}
elseif ( $err == 9 )
  $output .= '<h1><span class="error">'.lang("register", "recovery_mail_sent".( ( $core == 1 ) ? "A" : "MT" )).'</span></h1>';
else
  $output .=  '
            <h1>'.lang("login", "enter_valid_logon").'</h1>';

unset($err);

$output .= '
          </div>';

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

if ( $action === "dologin" )
  dologin();
else
  login();

unset($action);
unset($action_permission);

require_once "footer.php";


?>
