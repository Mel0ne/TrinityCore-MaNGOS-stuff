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


$time_start = microtime(true);

// if CoreManager isn't in the / path we probably need to
// specify a path in our session cookie
// get our path
$path = $_SERVER["SCRIPT_NAME"];
$filename = Explode("/", $path);
$filename = $filename[count($filename) - 1];
// remove the current filename from the path
$path = str_replace("/".$filename, "", $path);

// resuming login session if available, or start new one
if ( !ini_get("session.auto_start") )
{
  if ( $path != "" )
  {
    // we're not in the / path, use our current path in the session cookie
    $params = session_get_cookie_params();
    session_set_cookie_params($params["lifetime"], $path, $params["domain"], $params["secure"], $params["httponly"]);
  }

  session_start();
}

//---------------------Load Default and User Configuration---------------------
if ( file_exists("configs/config.php") )
{
  if ( !file_exists("configs/config.dist.php") )
    exit('<span style="width: 100%; text-align: center;"><br /><code>\'configs/config.dist.php\'</code> not found,<br />
          please restore <code>\'configs/config.dist.php\'</code></span>');

  require_once "configs/config.php";
}
else
  exit('<span style="width: 100%; text-align: center;"><br /><code>\'configs/config.php\'</code> not found,<br />
        please copy <code>\'configs/config.dist.php\'</code> to
        <code>\'configs/config.php\'</code> and make appropriate changes.</span>');

require_once("libs/config_lib.php");

//----------------- Make sure a valid core has been selected ------------------

if ( ( $core < 0 ) || ( $core > 3 ) )
  die("Invalid Core selected.");

//---------------------Error reports for Debugging-----------------------------
if ( $debug )
  $tot_queries = 0;
if ( $debug > 1 )
  error_reporting(E_ALL);
else
  error_reporting(E_COMPILE_ERROR);

//---------------------Loading User Theme and Language Settings----------------
if ( isset($_COOKIE["corem_theme"]) )
{
  if ( is_dir("themes/".$_COOKIE["corem_theme"]) )
    if ( is_file("themes/".$_COOKIE["corem_theme"]."/".$_COOKIE["corem_theme"]."_1024.css") )
      $theme = $_COOKIE["corem_theme"];
}

if ( isset($_COOKIE["corem_lang"]) )
{
  $lang = $_COOKIE["corem_lang"];
  if ( !file_exists("lang/".$lang.".php") )
    $lang = $language;
}
else
{
  $lang = $language;
  // if we didn't get a cookie for language, create one
  setcookie("corem_lang", $language, time() + 60 * 60 * 24 * 30 * 6); // six months
}

//---------------------Current Filename----------------------------------------
$cur_filename = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/")+1);

//---------------------Loading Libraries---------------------------------------
require_once "libs/db_lib.php";
require_once "lang/".$lang.".php";

require_once "libs/data_lib.php";
require_once "libs/global_lib.php";
require_once "libs/lang_lib.php";
require_once "libs/get_lib.php";

//---------------------------Improved Login Security---------------------------
// Originally login sent the sha1(name + pass) and we compared that with our
// records.  But, if that data was intercepted then an acceptable (but fake)
// login could be made.
// So, we implement a Public Key system.
// Each Session should have a unique Public Key. (I say 'should', as the key
// is 32 characters chosen 'randomly' from the list below, and 32^62 is quite a
// range of possible outcomes)
// Now login will send sha1(sha1(name+pass) + public key) which should nearly
// eliminate that security flaw.

// if necessary, generate a Public Key for Login
if ( !isset($_SESSION["pub_key"]) )
{
  $symbols = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  $login_public_key = "";

  while ( strlen($login_public_key) < 32 )
  {
    // randomly select a symbol to append
    $rnd = rand(0, 61);

    // append
    $login_public_key .= $symbols[$rnd];
  }

  $_SESSION["pub_key"] = $login_public_key;
}
//-----------------------------------------------------------------------------

//---------------------Cache Expiration Date Offset----------------------------
//$expire_offset = 60 * 60 * 24 * 3;

//---------------------Header's header-----------------------------------------
// sets encoding defined in config for language support
header("Content-Type: text/html; charset=".$site_encoding);
// the webserver should be adding this directive, but just in case...
header("Cache-Control: private, must-revalidate, post-check=0, pre-check=0");

$output .= '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>'.$title.'</title>
    <meta http-equiv="Content-Type" content="text/html; charset='.$site_encoding.'" />
    <meta http-equiv="Content-Type" content="text/javascript; charset='.$site_encoding.'" />
    <link rel="stylesheet" type="text/css" href="themes/'.$theme.'/'.$theme.'.php" title="1024" />
    <!-- link rel="stylesheet" type="text/css" href="themes/'.$theme.'/'.$theme.'_1280.css" title="1280" / -->
    <link rel="SHORTCUT ICON" href="img/favicon.ico" />
    <script type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="libs/js/general.js"></script>
    <script type="text/javascript" src="libs/js/layout.js"></script>';

	// make wowhead tooltops generally available
	//wowhead_tt();

$output .= '
  </head>';

if ( $cur_filename == "login.php" )
  $output .= '
  <body onload="get_username();">';
else
  $output .= '
  <body onload="dynamicLayout();">';

$output .= '
      <div class="table_top">
        <div class="header_logo">';

if ( $use_custom_logo )
{
  $output .= '
          <img src="libs/custom_logo_lib.php" alt="" />';
}

$output .= '
        </div>
        <div class="table_top_left">';
// this_is_junk: how did this site ever really work?  we can't clear a global!  idiots. <_<
//unset($title);

// check for host php script execution time limit,
//  warn user if it is not high enough for CoreManager to run

// this_is_junk: I guess it's time to acknowledge that no part of CoreManager should need to run longer than
// php's default max_execution_time of 30 seconds
/*if ( ini_get("max_execution_time") < 1800 )
{
  if ( !ini_set("max_execution_time", 0) )
    error('Error - max_execution_time not set.<br /> Please set it manually to 0, in php.ini for full functionality.');
}*/

//---------------------Guest login Predefines----------------------------------
if ( $allow_anony && empty($_SESSION["logged_in"]) )
{
  $_SESSION["user_lvl"] = -1;
  $_SESSION["gm_lvl"] = "-1";
  $_SESSION["login"] = $anony_uname;
  $_SESSION["user_id"] = -1;
  // realm id is REQUIRED by many things, so if the user isn't logged in, we use the configured default
  $_SESSION["realm_id"] = $anony_realm_id;
  $_SESSION["client_ip"] = ( ( isset($_SERVER["REMOTE_ADDR"]) ) ? $_SERVER["REMOTE_ADDR"] : getenv("REMOTE_ADDR") );
}

$realm_id = ( ( isset($_GET["realm_id"]) ) ? (int)$_GET["realm_id"] : $_SESSION["realm_id"] );

// set up databse global
$sql = array();

$sql["logon"] = new SQL;
$sql["logon"]->connect($logon_db["addr"], $logon_db["user"], $logon_db["pass"], $logon_db["name"], $logon_db["encoding"]);

$sql["dbc"] = new SQL;
$sql["dbc"]->connect($dbc_db["addr"], $dbc_db["user"], $dbc_db["pass"], $dbc_db["name"], $dbc_db["encoding"]);

$sql["mgr"] = new SQL;
$sql["mgr"]->connect($corem_db["addr"], $corem_db["user"], $corem_db["pass"], $corem_db["name"], $corem_db["encoding"]);

// we have to make sure that the realm id we have has entries
$db_query = "SELECT * FROM config_character_databases WHERE `Index`='".$realm_id."'";
$db_result = $sql["mgr"]->query($db_query);
$db_count = $sql["mgr"]->num_rows($db_result);
$db_query = "SELECT * FROM config_world_databases WHERE `Index`='".$realm_id."'";
$db_result = $sql["mgr"]->query($db_query);
$db_count += $sql["mgr"]->num_rows($db_result);
$db_query = "SELECT * FROM config_servers WHERE `Index`='".$realm_id."'";
$db_result = $sql["mgr"]->query($db_query);
$db_count += $sql["mgr"]->num_rows($db_result);

if ( $db_count < 3 )
{
  if ( $_SESSION["realm_id"] = $anony_realm_id )
  {
    // gotta love cold failure >_<
    $death = lang("global", "err_bad_realmid");
    $death = str_replace('%1', '<a href="mailto:'.$admin_mail.'">'.lang("footer", "site_admin").'</a>', $death);
    die($death);
  }
  else
  {
    $_SESSION["realm_id"] = $anony_realm_id;
    redirect("index.php");
  }
}

$sql["char"] = new SQL;
$sql["char"]->connect($characters_db[$realm_id]["addr"], $characters_db[$realm_id]["user"], $characters_db[$realm_id]["pass"], $characters_db[$realm_id]["name"], $characters_db[$realm_id]["encoding"]);

$sql["world"] = new SQL;
$sql["world"]->connect($world_db[$realm_id]["addr"], $world_db[$realm_id]["user"], $world_db[$realm_id]["pass"], $world_db[$realm_id]["name"], $world_db[$realm_id]["encoding"]);

// if $core is ZERO then we auto-detect based on the auth database
if ( $core == 0 )
  $core = detectcore();

//----Check if a user has login, if Guest mode is enabled, code above will login as Guest
if ( isset($_SESSION["user_lvl"]) && isset($_SESSION["login"]) && isset($_SESSION["realm_id"]) && empty($_GET["site_error"]) )
{
  // check for host php script max memory allowed,
  // setting it higher if it is not enough for CoreManager to run
  if ( ini_get("memory_limit") < 16 )
    @ini_set("memory_limit", "16M");

  // resuming logged in user settings
  session_regenerate_id();
  $user_lvl = $_SESSION["user_lvl"];
  $user_name = $_SESSION["login"];
  $user_id = $_SESSION["user_id"];
  // for CoreManager security system, getting the users' account group name
  // switched to use $_SESSION["gmlvl"]
  $user_lvl_name = gmlevel_name($_SESSION["gm_lvl"]);

  // get the file name that called this header
  $array = explode ( '/', $_SERVER["PHP_SELF"]);
  $lookup_file = $array[sizeof($array)-1];
  unset($array);

  //---------------------Top Menu----------------------------------------------
  $output .= '
          <div id="menuwrapper">
            <ul id="menubar">';

  $action_permission = array();
  foreach ( $menu_array as $trunk )
  {
    // ignore "invisible array" this is for setting security read/write values
    // for not accessible elements not in the navbar!
    if ( 'invisible' == $trunk[1] )
    {
      foreach ( $trunk[2] as $branch )
      {
        if ( $branch[0] === $lookup_file )
        {
          $action_permission["view"]   = $branch[2];
          $action_permission["insert"] = $branch[3];
          $action_permission["update"] = $branch[4];
          $action_permission["delete"] = $branch[5];
        }
      }
    }
    else
    {
      $output .= '
            <li><a href="'.$trunk[0].'">'.( ( lang("header", $trunk[1], true) ) ? lang("header", $trunk[1]) : $trunk[1] ).'</a>';

      if ( isset($trunk[2][0]) )
        $output .= '
                <ul>';

      foreach ( $trunk[2] as $branch )
      {
        if ( $branch[0] === $lookup_file )
        {
          $action_permission["view"]   = $branch[2];
          $action_permission["insert"] = $branch[3];
          $action_permission["update"] = $branch[4];
          $action_permission["delete"] = $branch[5];
        }

        if ( $user_lvl >= $branch[2] )
          $output .= '
                  <li><a href="'.$branch[0].'">'.( ( lang("header", $branch[1], true) ) ? lang("header", $branch[1]) : $branch[1] ).'</a></li>';
        else
          $output .= '
                  <li><span class="menubar_item_disabled"><img src="img/lock.png" alt="" />&nbsp;'.( ( lang("header", $branch[1], true) ) ? lang("header", $branch[1]) : $branch[1] ).'</span></li>';
      }

      if ( isset($trunk[2][0]) )
        $output .= '
                </ul>';
      $output .= '
              </li>';
    }
  }

  unset($branch);
  unset($trunk);
  unset($lookup_file);
  unset($menu_array);

  $output .= '
              <li><a href="edit.php">'.lang("header", "my_acc").'</a>
                <ul>';

  $result = $sql["mgr"]->query("SELECT `Index` AS id, Name AS name FROM `config_servers` LIMIT 10");

  // we check how many realms are configured, this does not check if config is valid
  if ( ( 1 < $sql["mgr"]->num_rows($result)) && ( 1 < count($server)) && ( 1 < count($characters_db)) )
  {
    $output .= '
                  <li><span style="text-align:center"><a href="#">'.lang("header", "realms").'</a></span></li>';
    while ( $realm = $sql["mgr"]->fetch_assoc($result) )
    {
      if ( isset($server[$realm["id"]]) )
      {
        $set = ( ( $realm_id === $realm["id"] ) ? '>' : '' );

        $current_page = $_SERVER["PHP_SELF"];

        // the Character Sheet Pages require a character to be selected
        // so, rather than have an error, we'll go to the Character List Page instead
        if ( $current_page == "/char.php" )
          $current_page = "/char_list.php";

        $output .= '
                  <li><a href="realm.php?action=set_def_realm&amp;id='.$realm["id"].'&amp;url='.$current_page.'">'.htmlentities($set.' '.$realm["name"], ENT_COMPAT, $site_encoding).'</a></li>';
      }
    }
    unset($set);
    unset($realm);
  }

  // we have a different menu for guest account
  if ( $allow_anony && empty($_SESSION["logged_in"]) )
  {
    $output .= '
                  <li><span style="text-align:center"><a href="#">'.lang("header", "account").'</a></span></li>
                  <li><a href="register.php">'.lang("login", "not_registrated").'</a></li>
                  <li><a href="login.php">'.lang("login", "login").'</a></li>';
  }
  else
  {
    if ( $core == 1 )
      $result = $sql["char"]->query("SELECT guid, name, race, class, level, gender FROM characters WHERE acct='".$user_id."'");
    else
      $result = $sql["char"]->query("SELECT guid, name, race, class, level, gender FROM characters WHERE account='".$user_id."'");

    // this puts links to user characters of active realm in "My Account" menu
    if ( $sql["char"]->num_rows($result) )
    {
      $output .= '
                  <li><span style="text-align:center"><a href="#">'.lang("header", "my_characters").'</a></span></li>';
      while ( $char = $sql["char"]->fetch_assoc($result) )
      {
        $output .= '
                  <li>
                    <a href="char.php?id='.$char["guid"].'">
                      <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" alt="" /><img src="img/c_icons/'.$char["class"].'.gif" alt="" />'.
                      ' '.$char["name"].
                      ' ('.$char["level"].') '.'
                    </a>
                  </li>';
      }
      unset($char);
    }
    $output .= '
                  <li><span style="text-align:center"><a href="#">'.lang("header", "account").'</a></span></li>
                  <li><a href="edit.php">'.lang("header", "edit_my_acc").'</a></li>
                  <li><a href="logout.php">'.lang("header", "logout").'</a></li>';
  }
  unset($result);
  $output .= '
                </ul>
              </li>
            </ul>
            <br class="clearit" />
          </div><!-- menu_wrapper -->
        </div>
        <div class="table_top_middle"></div>
        <div class="header_middle">';
  $web_admin_query = "SELECT * FROM config_accounts WHERE Login='".$user_name."'";
  $web_admin_result = $sql["mgr"]->query($web_admin_query);
  $web_admin = $sql["mgr"]->fetch_assoc($web_admin_result);
  $web_admin = $web_admin["SecurityLevel"] & 1073741824;
  //if (!$_SESSION["screenname"])
  //{
    $output .= '
          <div id="username">'.( ( isset($_SESSION["screenname"]) ) ? $_SESSION["screenname"] : $user_name ).' .:'.( ( $web_admin ) ? '<a href="admin.php">' : '' ).$user_lvl_name.'\'s '.lang("header", "menu").( ( $web_admin ) ? '</a>' : '' ).':.</div>';
  //}
  //else
  //{
    //$output .= '
    //        <div id="username">'..' .:'.( $web_admin ? '<a href="admin.php">' : '' ).$user_lvl_name.'\'s '.lang("header", "menu").( $web_admin ? '</a>' : '' ).':.</div>';
  //}
  $output .= '
        </div>
        <div class="table_top_right"></div>
      </div>';
}
else
{
  $output .= '
          </td>
          <td class="table_top_middle"></td>
          <td class="table_top_right"></td>
        </tr>
      </table>';
}
// show login and register buttons at top of every page if guest mode is activated
// we don't need to display the Register & Login Buttons on the Register & Login
// pages.  So, we'll get the current file's name and test for it below.
$filename = $_SERVER["SCRIPT_NAME"];
$filename = Explode('/', $filename);
$filename = $filename[count($filename) - 1];

if ( ( $allow_anony && empty($_SESSION["logged_in"]) ) && ( ( $filename != "login.php" ) && ( $filename != "register.php" ) ) )
{
  $output .= '
      <table class="center">
        <tr>
          <td>
            <a class="button footer_register_login" href="register.php">'.lang("header", "register").'</a>
            <a class="button footer_register_login" href="login.php">'.lang("header", "login").'</a>
          </td>
        </tr>
      </table>';
}

//---------------------Start of Body-------------------------------------------

if ( ( isset($_SESSION["logged_in"]) ) || ( $filename == "login.php" ) || ( $filename == "register.php" ) )
  $output .= '
      <div id="body_main" class="body_main_shallow">';
else
  $output .= '
      <div id="body_main" class="body_main_deep">';

$output .= '
        <!-- end of header.php -->';

?>
