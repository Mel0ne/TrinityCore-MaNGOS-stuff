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


// because error needs a header but header.php requires databases,
// we make our own page header, and get any additional required libraries
session_start();

$time_start = microtime(true);

//---------------------Loading User Theme and Language Settings----------------
if ( isset($_COOKIE["corem_theme"]) )
{
  if ( is_dir("themes/".$_COOKIE["corem_theme"]) )
    if ( is_file("themes/".$_COOKIE["corem_theme"]."/".$_COOKIE["corem_theme"]."_1024.css") )
      $theme = $_COOKIE["corem_theme"];
}
else
  $theme = "Sulfur";

if ( isset($_COOKIE["corem_lang"]) )
{
  $lang = $_COOKIE["corem_lang"];
  if ( !file_exists("lang/".$lang.".php") )
    $lang = "english";
}
else
  $lang = "english";

//---------------------Load Default and User Configuration---------------------
if ( file_exists("configs/config.php") )
{
  if ( !file_exists("configs/config.dist.php") )
    exit('<center><br><code>\'configs/config.dist.php\'</code> not found,<br>
          please restore <code>\'configs/config.dist.php\'</code></center>');
  require_once "configs/config.php";
}
else
  exit('<center><br><code>\'configs/config.php\'</code> not found,<br>
        please copy <code>\'configs/config.dist.php\'</code> to
        <code>\'configs/config.php\'</code> and make appropriate changes.');

//---------------------Current Filename----------------------------------------
$cur_filename = "error.php";

//---------------------Loading Libraries---------------------------------------
require_once "libs/config_lib.php";
require_once "libs/global_lib.php";
require_once "lang/".$lang.".php";
require_once "libs/lang_lib.php";

// generate minimum info to prevent php errors
if ( $allow_anony && empty($_SESSION["logged_in"]) )
  $_SESSION["user_lvl"] = -1;

if ( isset($_SESSION["user_lvl"]) )
  $user_lvl = $_SESSION["user_lvl"];

// sets encoding defined in config for language support
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
    <script type="text/javascript" src="libs/js/layout.js"></script>
  </head>';

$output .= '
  <body onload="dynamicLayout();">
      <div>
        <br />
      </div>
      <div id="body_main">';
// end of header

// we get the error message which was passed to us
$err = ( ( isset($_SESSION["pass_error"]) ) ? $_SESSION["pass_error"] : lang("error", "generic_error").'...' );

// we start with a lead of 10 spaces,
//  because last line of header is an opening tag with 8 spaces
//  keep html indent in sync, so debuging from browser source would be easy to read
$output .= '
        <div class="bubble">
          <!-- start of error.php -->
          <div class="center_text">
            <br />
            <table id="error_message_table" class="flat center">
              <tr>
                <td align="center">
                  <h1>
                    <span class="error">
                      <img src="img/warn_red.gif" width="48" height="48" alt="error" />
                      <br />'.lang("error", "error").'!
                    </span>
                  </h1>
                  <br />'.htmlspecialchars($err).'<br />
                </td>
              </tr>
            </table>
            <br />
            <table id="error_buttons" class="hidden center">
              <tr>
                <td align="center">';
makebutton(lang("global", "home"), 'index.php', 130);
makebutton(lang("global", "back"), 'javascript:window.history.back()', 130);
unset($err);
$output .= '
                </td>
              </tr>
            </table>
            <br />
          </div>
          <!-- end of error.php -->';

require_once "footer.php";


?>
