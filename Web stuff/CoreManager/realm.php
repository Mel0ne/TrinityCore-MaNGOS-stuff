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

valid_login($action_permission["view"]);

//####################################################################################################
// SHOW REALMS
//####################################################################################################
function show_realm()
{
  global $output, $server, $action_permission, $user_lvl, $sql;

  valid_login($action_permission["view"]);

  //==========================$_GET and SECURE=================================
  $order_by = ( ( isset($_GET["order_by"]) ) ? $sql["mgr"]->quote_smart($_GET["order_by"]) : 'rid' );
  if ( !preg_match('/^[_[:lower:]]{1,8}$/', $order_by) )
    $order_by = 'rid';

  $dir = ( ( isset($_GET["dir"]) ) ? $sql["mgr"]->quote_smart($_GET["dir"]) : 1 );
  if ( !preg_match('/^[01]{1}$/', $dir) )
    $dir = 1;

  $order_dir = ( ( $dir ) ? "ASC" : "DESC" );
  $dir = ( ( $dir ) ? 0 : 1 );
  //==========================$_GET and SECURE end=============================
  $result = $sql["char"]->query("SELECT COUNT(*) FROM characters");
  $sum = $sql["char"]->fetch_row($result);
  $sum = $sum[0];

  $result = $sql["mgr"]->query("SELECT `Index` AS rid, Name AS name, External_Address AS address, Port AS port, Icon AS icon, Color AS color, Timezone AS timezone
            FROM config_servers ORDER BY ".$order_by." ".$order_dir);
  $total_realms = $sql["mgr"]->num_rows($result);

$output .= '
          <table class="top_hidden">
            <tr>
              <td>';
  /*if ( $user_lvl >= $action_permission["insert"] )
                  makebutton(lang("realm", "add_realm"), 'realm.php?action=add_realm', 130);*/
                  makebutton(lang("global", "back"), 'javascript:window.history.back()', 130);
  $output .= '
              </td>
              <td align="right">'.lang("realm", "tot_realms").' : '.$total_realms.'</td>
            </tr>
          </table>
          <table class="lined">
            <tr>';
  /*if ( $user_lvl >= $action_permission["delete"] )
    $output .= '
                <th width="1%">'.lang("global", "delete_short").'</th>';*/
  $output .= '
              <th style="width: 1%;"><a href="realm.php?order_by=rid&amp;dir='.$dir.'"'.( ( $order_by==='rid' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "id").'</a></th>
              <th style="width: 40%;"><a href="realm.php?order_by=name&amp;dir='.$dir.'"'.( ( $order_by==='name' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "name").'</a></th>
              <th style="width: 1%;">'.lang("realm", "online").'</th>
              <th style="width: 10%;">'.lang("realm", "tot_char").'</th>
              <th style="width: 10%;"><a href="realm.php?order_by=address&amp;dir='.$dir.'"'.( ( $order_by==='address' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "address").'</a></th>
              <th style="width: 1%;"><a href="realm.php?order_by=port&amp;dir='.$dir.'"'.( ( $order_by==='port' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "port").'</a></th>
              <th style="width: 1%;"><a href="realm.php?order_by=icon&amp;dir='.$dir.'"'.( ( $order_by==='icon' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "icon").'</a></th>
              <th style="width: 1%;"><a href="realm.php?order_by=color&amp;dir='.$dir.'"'.( ( $order_by==='color' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "color").'</a></th>
              <th style="width: 7%;"><a href="realm.php?order_by=timezone&amp;dir='.$dir.'"'.( ( $order_by==='timezone' ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("realm", "timezone").'</a></th>
            </tr>';
  unset($dir);
  unset($order_dir);
  unset($order_by);
  $icon_type = get_icon_type();
  $timezone_type = get_timezone_type();

  while ( $realm = $sql["mgr"]->fetch_assoc($result) )
  {
    $output .= '
            <tr>';
    /*if ( $user_lvl >= $action_permission["delete"] )
      $output .= '
                <td><a href="realm.php?action=del_realm&amp;id='.$realm["rid"].'"><img src="img/aff_cross.png" alt="" /></a></td>';*/
    $output .= '
              <td>'.$realm["rid"].'</td>';
    if ( isset($server[$realm["rid"]]['game_port']) )
    {
      if ( $user_lvl >= $action_permission["update"] )
        $output .= '
              <td><a href="realm.php?action=edit_realm&amp;id='.$realm["rid"].'">'.$realm["name"].'</a></td>';
      else
        $output .= '
              <td>'.$realm["name"].'</td>';
      if ( test_port($server[$realm["rid"]]['addr'], $server[$realm["rid"]]['game_port']) )
        $output .= '
              <td><img src="img/up.gif" alt="" /></td>';
      else
        $output .= '
              <td><img src="img/down.gif" alt="" /></td>';
    }
    else
    {
      $output .= '
              <td>';
      if ( $user_lvl >= $action_permission["update"] )
        $output .= '
                <a href="realm.php?action=edit_realm&amp;id='.$realm["rid"].'">'.$realm["name"].' ('.lang("realm", "notconfigured").')</a>';
      else
        $output .= ''.
                $realm["name"].' ('.lang("realm", "notconfigured").')';
      $output .= '
              </td>
              <td>***</td>';
    }
    $output .= '
              <td>'.$sum.'</td>
              <td>'.$realm["address"].'</td>
              <td>'.$realm["port"].'</td>
              <td>'.$icon_type[$realm["icon"]][1].'</td>
              <td>'.$realm["color"].'</td>
              <td>'.$timezone_type[$realm["timezone"]][1].'</td>
            </tr>';
  }
  unset($realm);
  unset($icon_type);
  unset($timezone_type);
  unset($result);
  $output .= '
          </table>
          <br />';

}


//####################################################################################################
//  EDIT REALM
//####################################################################################################
function edit_realm()
{
  global $output, $server, $action_permission, $user_lvl, $sql;

  valid_login($action_permission["update"]);

  $result = $sql["char"]->query("SELECT COUNT(*) FROM characters");
  $sum = $sql["char"]->fetch_row($result);
  $sum = $sum[0];

  if ( empty($_GET["id"]) )
    redirect("realm.php?error=1");

  $id = $sql["mgr"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    redirect("realm.php?error=1");

  if ( $realm = $sql["mgr"]->fetch_assoc($sql["mgr"]->query('SELECT `Index` AS rid, Name AS name, External_Address AS address, Port AS port, Icon AS icon, Color AS color, Timezone AS timezone
         FROM config_servers WHERE `Index`='.$id.'')) )
  {
    $output .= '
          <div class="half_frame fieldset_border center">
            <span class="legend">'.lang("realm", "edit_realm").'</span>
            <form method="get" action="realm.php" id="form">
              <div>
                <input type="hidden" name="action" value="doedit_realm" />
                <input type="hidden" name="id" value="'.$id.'" />
              </div>
              <table class="flat center">
                <tr>
                  <td>'.lang("realm", "id").'</td>
                  <td>'.$realm["rid"].'</td>
                </tr>
                <tr>
                  <td>'.lang("realm", "name").'</td>
                  <td><input type="text" name="new_name" size="40" maxlength="32" value="'.$realm["name"].'" /></td>
                </tr>
                <tr>
                  <td>'.lang("realm", "address").'</td>
                  <td><input type="text" name="new_address" size="40" maxlength="32" value="'.$realm["address"].'" /></td>
                </tr>
                <tr>
                  <td>'.lang("realm", "port").'</td>
                  <td><input type="text" name="new_port" size="40" maxlength="5" value="'.$realm["port"].'" /></td>
                </tr>
                <tr>
                  <td>'.lang("realm", "icon").'</td>
                  <td>
                    <select name="new_icon">';
    unset($id);
    foreach ( get_icon_type() as $icon )
    {
      $output .= '
                      <option value="'.$icon[0].'" '.( ( $realm["icon"]==$icon[0] ) ? 'selected="selected" ' : '' ).'>'.$icon[1].'</option>';
    }
    unset($icon);
    $output .= '
                    </select>
                  </td>
                </tr>
                <tr>
                  <td>'.lang("realm", "color").'</td>
                  <td><input type="text" name="new_color" size="40" maxlength="3" value="'.$realm["color"].'" /></td>
                </tr>
                <tr>
                  <td>'.lang("realm", "timezone").'</td>
                  <td>
                    <select name="new_timezone">';
    foreach ( get_timezone_type() as $zone )
    {
      $output .= '
                      <option value="'.$zone[0].'" '.( ( $realm["timezone"]==$zone[0] ) ? 'selected="selected" ' : '' ).'>'.$zone[1].'</option>';
    }
    unset($zone);
    $output .= '
                    </select>
                  </td>
                </tr>';
    if ( isset($server[$realm["rid"]]['game_port']) )
    {
      $output .= '
                <tr>
                  <td>'.lang("realm", "status").'</td>
                  <td>'.( ( test_port($server[$realm["rid"]]['addr'], $server[$realm["rid"]]['game_port']) ) ? '<img src="img/up.gif" alt="" />' : '<img src="img/down.gif" alt="" />' ).'</td>
                </tr>
                <tr>
                  <td>'.lang("realm", "tot_char").'</td>
                  <td>'.$sum.'</td>
                </tr>';
    }
    else
      $output .= '
                <tr>
                  <td colspan="2">'.lang("realm", "conf_from_file").'</td>
                </tr>';
    $output .= '
                <tr>
                  <td>';
    if ( $user_lvl >= $action_permission["delete"] )
      makebutton(lang("realm", "delete"), 'realm.php?action=del_realm&amp;id='.$realm["rid"].'" type="wrn', 130);

    unset($realm);
    $output .= '
                  </td>
                  <td>';

    makebutton(lang("realm", "update"), 'javascript:do_submit()', 130);
    makebutton(lang("global", "back"), 'realm.php" type="def', 130);

    $output .= '
                  </td>
                </tr>
              </table>
            </form>
          </div>
          <br />
          <br />';
  }
  else
    error(lang("global", "err_no_result"));

}


//####################################################################################################
//  DO EDIT REALM
//####################################################################################################
function doedit_realm()
{
  global $action_permission, $sql;

  valid_login($action_permission["update"]);

  if ( !isset($_GET["id"]) ||
      !isset($_GET["new_name"]) ||
      !isset($_GET["new_address"]) ||
      !isset($_GET["new_port"]) ||
      !isset($_GET["new_icon"]) ||
      !isset($_GET["new_color"]) ||
      !isset($_GET["new_timezone"]) )
    redirect("realm.php?error=1");

  $id = $sql["mgr"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    redirect("realm.php?error=1");

  $new_name     = $sql["mgr"]->quote_smart($_GET["new_name"]);
  $new_address  = $sql["mgr"]->quote_smart($_GET["new_address"]);
  $new_port     = $sql["mgr"]->quote_smart($_GET["new_port"]);
  $new_icon     = $sql["mgr"]->quote_smart($_GET["new_icon"]);
  $new_color    = $sql["mgr"]->quote_smart($_GET["new_color"]);
  $new_timezone = $sql["mgr"]->quote_smart($_GET["new_timezone"]);

  $query = $sql["mgr"]->query("UPDATE config_servers SET Name='".$new_name."', External_Address='".$new_address."' , Port='".$new_port."', Icon='".$new_icon."', Color='".$new_color."', Timezone='".$new_timezone."' WHERE `Index`='".$id."'");

  unset($new_name);
  unset($new_address);
  unset($new_port);
  unset($new_icon);
  unset($new_color);
  unset($new_timezone);

  if ( $sql["mgr"]->affected_rows() )
    redirect("realm.php?error=3");
  else
    redirect("realm.php?action=edit_realm&id=".$id."&error=4");
}


//####################################################################################################
// DELETE REALM
//####################################################################################################
function del_realm()
{
  global $output, $action_permission, $sql;

  valid_login($action_permission["delete"]);

  if ( empty($_GET["id"]) )
    redirect("realm.php?error=1");

  $id = $sql["mgr"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    redirect("realm.php?error=1");

  $output .= '
          <center>
            <h1><font class="error">'.lang("global", "are_you_sure").'</font></h1>
            <br />
            <font class="bold">'.lang("realm", "realm_id").': '.$id.'<br />'.lang("global", "will_be_erased").'</font>
            <br /><br />
            <table width="300" class="hidden">
              <tr>
                <td>';
                  makebutton(lang("global", "yes"), 'realm.php?action=dodel_realm&amp;id='.$id.'" type ="wrn', 130);
                  makebutton(lang("global", "no"), 'realm.php" type="def', 130);
  unset($id);
  $output .= '
                </td>
              </tr>
            </table>
          </center>';
}


//####################################################################################################
// DO DELETE REALM
//####################################################################################################
function dodel_realm()
{
  global $action_permission, $sql;

  valid_login($action_permission["delete"]);

  if ( empty($_GET["id"]) )
    redirect("realm.php?error=1");

  $id = $sql["mgr"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    redirect("realm.php?error=1");

  $sql["mgr"]->query("DELETE FROM config_servers WHERE `Index`='".$id."'");
  unset($id);

  if ( $sql["mgr"]->affected_rows() )
    redirect("realm.php");
  else
    redirect("realm.php?error=2");
}


//####################################################################################################
//  ADD NEW REALM
//####################################################################################################
function add_realm()
{
  global $action_permission, $sql, $core;

  valid_login($action_permission["insert"]);
  
  switch ( $core )
  {
    case 1:
    {
      $name = "ArcEmu";
      $port = "8129";
      break;
    }
    case 2:
    {
      $name = "MaNGOS";
      $port = "8085";
      break;
    }
    case 3:
    {
      $name = "Trinity";
      $port = "8085";
      break;
    }
  }

  $result = $sql["mgr"]->query("INSERT INTO config_servers (`Index`, Name, External_Address, Port, Icon, Color, Timezone) VALUES (NULL, '".$name."', '127.0.0.1', ".$port.", 0 ,0 ,1)");

  if ( $result )
    redirect("realm.php");
  else
    redirect("realm.php?error=4");
}


//####################################################################################################
// SET REALM TO DEFAULT
//####################################################################################################
function set_def_realm()
{
  global $action_permission, $sql;

  valid_login($action_permission["view"]);

  $id = ( ( isset($_GET["id"]) ) ? $sql["mgr"]->quote_smart($_GET["id"]) : 1 );
  if ( !is_numeric($id) )
    $id = 1;

  if ( $sql["mgr"]->num_rows($sql["mgr"]->query("SELECT `Index` AS id FROM config_servers WHERE `Index`='".$id."'")) )
    $_SESSION["realm_id"] = $id;
  unset($id);

  $url = ( ( isset($_GET["url"]) ) ? $_GET["url"] : "index.php" );
  redirect($url);
}


function get_icon_type()
{
  return Array
  (
    0 => array( 0, lang("realm", "normal")),
    1 => array( 1, lang("realm", "pvp")),
    6 => array( 6, lang("realm", "rp")),
    8 => array( 8, lang("realm", "rppvp")),
  );
}


function get_timezone_type()
{
  return Array
  (
    1 => array( 1, lang("realm", "development")),
    2 => array( 2, lang("realm", "united_states")),
    3 => array( 3, lang("realm", "oceanic")),
    4 => array( 4, lang("realm", "latin_america")),
    5 => array( 5, lang("realm", "tournament")),
    6 => array( 6, lang("realm", "korea")),
    8 => array( 8, lang("realm", "english")),
    9 => array( 9, lang("realm", "german")),
   10 => array(10, lang("realm", "french")),
   11 => array(11, lang("realm", "spanish")),
   12 => array(12, lang("realm", "russian")),
   14 => array(14, lang("realm", "taiwan")),
   16 => array(16, lang("realm", "china")),
   26 => array(26, lang("realm", "test_server")),
   28 => array(28, lang("realm", "qa_server")),
  );
}


//####################################################################################################
// MAIN
//####################################################################################################
$err = ( ( isset($_GET["error"]) ) ? $_GET["error"] : NULL );

$output .= '
      <div class="bubble">
          <div class="top">';

if ( $err == 1 )
  $output .= '
            <h1><span class="error">'.lang("global", "empty_fields").'</span></h1>';
elseif ( $err == 2 )
  $output .= '
            <h1><span class="error">'.lang("realm", "err_deleting").'</span></h1>';
elseif ( $err == 3 )
  $output .= '
            <h1><span class="error">'.lang("realm", "update_executed").'</span></h1>';
elseif ( $err == 4 )
  $output .= '
            <h1><span class="error">'.lang("realm", "update_err").'</span></h1>';
else
  $output .= '
            <h1>'.lang("realm", "realm_data").'</h1>';

unset($err);

$output .= '
          </div>';

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

if ( $action == "edit_realm" )
  edit_realm();
elseif ( $action == "doedit_realm" )
  doedit_realm();
elseif ( $action == "del_realm" )
  del_realm();
elseif ( $action == "dodel_realm" )
  dodel_realm();
elseif ( $action == "add_realm" )
  add_realm();
elseif ( $action == "set_def_realm" )
  set_def_realm();
else
  show_realm();


unset($action);
unset($action_permission);

require_once "footer.php";


?>
