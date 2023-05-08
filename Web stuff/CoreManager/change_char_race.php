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
require_once "libs/char_lib.php";

valid_login($action_permission["view"]);


//#############################################################################
// SELECT CHARACTER
//#############################################################################

function sel_char()
{
  global $output, $action_permission, $characters_db, $corem_db, $realm_id, $user_id, $sql, $core;

  valid_login($action_permission["view"]);

  $output .= '
          <div id="xname_fieldset" class="fieldset_border center">
            <span class="legend">'.lang("xrace", "selectchar").'</span>
            <span class="xname_info">'.lang("xrace", "info").'</span>
            <br />
            <br />
            <form method="get" action="change_char_race.php" id="form">
              <div>
                <input type="hidden" name="action" value="chooserace" />
              </div>
              <table class="lined" id="xname_char_table">
                <tr>
                  <th class="xname_radio">&nbsp;</th>
                  <th class="xname_name">'.lang("xrace", "char").'</th>
                  <th class="xname_LRC">'.lang("xrace", "lvl").'</th>
                  <th class="xname_LRC">'.lang("xrace", "race").'</th>
                  <th class="xname_LRC">'.lang("xrace", "class").'</th>
                </tr>';

  if ( $core == 1 )
    $chars = $sql["char"]->query("SELECT * FROM characters WHERE acct='".$user_id."' AND guid NOT IN (SELECT guid FROM ".$corem_db["name"].".char_changes)");
  else
    $chars = $sql["char"]->query("SELECT * FROM characters WHERE account='".$user_id."' AND guid NOT IN (SELECT guid FROM ".$corem_db["name"].".char_changes)");

  while ( $char = $sql["char"]->fetch_assoc($chars) )
  {
    $output .= '
                <tr>
                  <td>
                    <input type="radio" name="char" value="'.$char["guid"].'"/>
                  </td>
                  <td>'.$char["name"].'</td>
                  <td>'.char_get_level_color($char["level"]).'</td>
                  <td>
                    <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                  </td>
                  <td>
                    <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                  </td>
                </tr>';
  }

  $output .= '
                <tr>
                  <td colspan="5" class="hidden">';
  makebutton(lang("xrace", "selectchar"), "javascript:do_submit()", 180);
  $output .= '
                  </td>
                </tr>
              </table>
            </form>
          </div>
          <br />';
}


//#############################################################################
// SELECT NEW RACE
//#############################################################################

$Class_Races  = array
                (
                  1  => array( 1, 2, 3, 4, 5, 6, 7, 8,     11,),
                  2  => array( 1,    3,                10, 11,),
                  3  => array(    2, 3, 4,    6,    8, 10, 11,),
                  4  => array( 1, 2, 3, 4, 5,    7, 8, 10,    ),
                  5  => array( 1,    3, 4, 5,       8, 10, 11,),
                  6  => array( 1, 2, 3, 4, 5, 6, 7, 8, 10, 11,),
                  7  => array(    2,          6,    8,     11,),
                  8  => array( 1,          5,    7, 8, 10, 11,),
                  9  => array( 1, 2,       5,    7,    10,    ),
                  11 => array(          4,    6,              ),
                );

function chooserace()
{
  global $output, $action_permission, $characters_db, $realm_id, $user_id,
    $Class_Races, $user_name, $race_credits, $sql, $core;

  valid_login($action_permission["view"]);

  $guid = $sql["char"]->quote_smart($_GET["char"]);
  $new1 = '';
  if ( isset($_GET["new1"]) )
    $new1 = $sql["char"]->quote_smart($_GET["new1"]);
  $new2 = '';
  if ( isset($_GET["new2"]) )
    $new2 = $sql["char"]->quote_smart($_GET["new2"]);

  $char = $sql["char"]->fetch_assoc($sql["char"]->query("SELECT * FROM characters WHERE guid='".$guid."'"));

  // credits
  if ( $race_credits >= 0 )
  {
    // get our credit balance
    $cr_query = "SELECT Credits FROM config_accounts WHERE Login='".$user_name."'";
    $cr_result = $sql["mgr"]->query($cr_query);
    $cr_result = $sql["mgr"]->fetch_assoc($cr_result);
    $credits = $cr_result["Credits"];
  }

  $output .= '
          <div id="xname_fieldset" class="fieldset_border center">
            <span class="legend">'.lang("xrace", "chooserace").'</span>
            <form method="get" action="change_char_race.php" id="form">
              <div>
                <input type="hidden" name="action" value="getapproval" />
                <input type="hidden" name="guid" value="'.$char["guid"].'" />
              </div>
              <table id="xname_char_table" class="center">
                <tr>
                  <td rowspan="4" style="width: 170px;">
                    <div style="width: 64px; margin-left: auto; margin-right: auto;">
                      <img src="'.char_get_avatar_img($char["level"], $char["gender"],  $char["race"],  $char["class"]).'" alt="" />
                    </div>
                  </td>
                  <td>
                    <span class="xname_char_name">'.$char["name"].'</span>
                  </td>
                </tr>
                <tr>
                  <td>'.lang("xrace", "level").': '.$char["level"].'</td>
                </tr>
                <tr>
                  <td>'.lang("xrace", "race").': '.char_get_race_name($char["race"]).'</td>
                </tr>
                <tr>
                  <td>'.lang("xrace", "class").': '.char_get_class_name($char["class"]).'</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                </tr>';

  if ( $race_credits > 0 )
  {
    $cost_line = lang("xrace", "credit_cost");
    $cost_line = str_replace("%1", '<b>'.$race_credits.'</b>', $cost_line);

    $output .= '
                <tr>
                  <td colspan="2">'.$cost_line.'</td>
                </tr>';

    if ( $credits >= 0 )
    {
      $credit_balance = lang("xrace", "credit_balance");
      $credit_balance = str_replace("%1", '<b>'.(float)$credits.'</b>', $credit_balance);

      $output .= '
                <tr>
                  <td colspan="2">'.$credit_balance.'</td>
                </tr>';

      if ( $credits < $race_credits )
        $output .= '
                <tr>
                  <td colspan="2">'.lang("xrace", "insufficient_credits").'</td>
                </tr>';
      else
        $output .= '
                <tr>
                  <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                  <td colspan="2">'.lang("xrace", "delay_warning").'</td>
                </tr>';
    }
    else
      $output .= '
                <tr>
                  <td colspan="2">'.lang("global", "credits_unlimited").'</td>
                </tr>';

    $output .= '
                <tr>
                  <td colspan="2">&nbsp;</td>
                </tr>';
  }

  $output .= '
                <tr>
                  <td colspan="2"><b>'.lang("xrace", "enterrace").':</b></td>
                </tr>
                <tr>
                  <td>'.lang("xrace", "newrace").':</td>
                  <td>';
  $races = $Class_Races[$char["class"]];
  $available_races = array();
  for ( $i = 0; $i < count($races); $i++ )
  {
    if ( !( $races[$i] == $char["race"] ) )
    {
      if ( char_get_side_id($races[$i]) == char_get_side_id($char["race"]) )
        $available_races[] = $races[$i];
    }
  }

  if ( count($available_races) > 0 )
  {
    $output .= '
                    <select name="newrace">';

    foreach ( $available_races as $race )
      $output .= '
                      <option value="'.$race.'">'.char_get_race_name($race).'</option>';

    $output .= '
                    </select>';
  }
  else
    $output .= '
                  <span>'.lang("xrace", "no_races").'</span>';

  $output .= '
                  </td>
                </tr>';

    // if we have unlimited credits, then we fake our credit balance here
    $credits = ( ( $credits < 0 ) ? $race_credits : $credits );

    if ( ( $race_credits <= 0 ) || ( $credits >= $race_credits ) )
    {
      $output .= '
                <tr>
                  <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                  <td colspan="2">';
      makebutton(lang("xrace", "save"), "javascript:do_submit()", 180);
      $output .= '
                  </td>
                </tr>';
    }

    $output .= '
              </table>
            </form>
          </div>
          <br />';
}


//#############################################################################
// SUBMIT RACE CHANGE
//#############################################################################

function getapproval()
{
  global $output, $action_permission, $corem_db, $characters_db, $realm_id,
    $user_id, $Class_Races, $sql;

  valid_login($action_permission["view"]);

  $guid = $sql["mgr"]->quote_smart($_GET["guid"]);
  $newrace = $sql["mgr"]->quote_smart($_GET["newrace"]);

  $count = $sql["mgr"]->num_rows($sql["mgr"]->query("SELECT * FROM char_changes WHERE `guid`='".$guid."'"));
  if ($count)
    redirect("change_char_race.php?error=3");

  $char = $sql["char"]->fetch_assoc($sql["char"]->query("SELECT * FROM characters WHERE `guid`='".$guid."'"));
  if ( !in_array($newrace, $Class_Races[$char["class"]]) )
    redirect("change_char_race.php?error=2");

  // credits
  // we do a credit balance check here in case of URL insertion
  if ( $race_credits > 0 )
  {
    // we need the player's account
    if ( $core == 1 )
      $acct_query = "SELECT login AS username FROM accounts WHERE acct=(SELECT acct FROM ".$characters_db[$realm_id]["name"].".characters WHERE guid='".$guid."')";
    else
      $acct_query = "SELECT username FROM account WHERE id=(SELECT account FROM ".$characters_db[$realm_id]["name"].".characters WHERE guid='".$guid."')";

    $acct_result = $sql["logon"]->query($acct_query);
    $acct_result = $sql["logon"]->fetch_assoc($acct_result);
    $username = $acct_result["username"];

    // now we get the user's credit balance
    $cr_query = "SELECT Credits FROM config_accounts WHERE Login='".$username."'";
    $cr_result = $sql["mgr"]->query($cr_query);
    $cr_result = $sql["mgr"]->fetch_assoc($cr_result);
    $credits = $cr_result["Credits"];

    // we fake how many credits the account has if the account is unlimited
    $credits = ( ( $credits < 0 ) ? $race_credits : $credits );

    if ( $credits < $race_credits )
      redirect("change_char_race.php?error=6");
  }

  $result = $sql["mgr"]->query("INSERT INTO char_changes (guid, new_race) VALUES ('".$guid."', '".$newrace."')");

  redirect("change_char_race.php?error=5");
}


//#############################################################################
// DENY RACE CHANGE
//#############################################################################

function denied()
{
  global $output, $action_permission, $corem_db, $characters_db, $realm_id, $user_id, $sql, $core;

  valid_login($action_permission["update"]);

  $guid = $sql["mgr"]->quote_smart($_GET["guid"]);

  $result = $sql["mgr"]->query("DELETE FROM char_changes WHERE `guid`='".$guid."'");

  $char = $sql["char"]->fetch_assoc($sql["char"]->query("SELECT * FROM characters WHERE guid='".$guid."'"));

  // Localization
  $body = lang("xrace", "body");
  $body = str_replace("%1", $char["name"], $body);

  redirect("mail.php?action=send_mail&type=ingame_mail&to=".$char["name"]."&subject=".lang("xrace", "subject")."&body=".$body."&group_sign==&group_send=gm_level&money=0&att_item=0&att_stack=0&redirect=index.php");
}


//#############################################################################
// SAVE NEW RACE
//#############################################################################

function saverace()
{
  global $output, $action_permission, $corem_db, $characters_db, $realm_id, $user_id,
    $race_credits, $sql, $core;

  valid_login($action_permission["update"]);

  $guid = $sql["mgr"]->quote_smart($_GET["guid"]);

  $name = $sql["mgr"]->fetch_assoc($sql["mgr"]->query("SELECT * FROM char_changes WHERE guid='".$guid."'"));

  $int_err = 0;

  // credits
  if ( $race_credits > 0 )
  {
    // we need the player's account
    if ( $core == 1 )
      $acct_query = "SELECT login AS username FROM accounts WHERE acct=(SELECT acct FROM ".$characters_db[$realm_id]["name"].".characters WHERE guid='".$guid."')";
    else
      $acct_query = "SELECT username FROM account WHERE id=(SELECT account FROM ".$characters_db[$realm_id]["name"].".characters WHERE guid='".$guid."')";

    $acct_result = $sql["logon"]->query($acct_query);
    $acct_result = $sql["logon"]->fetch_assoc($acct_result);
    $username = $acct_result["username"];

    // now we get the user's credit balance
    $cr_query = "SELECT Credits FROM config_accounts WHERE Login='".$username."'";
    $cr_result = $sql["mgr"]->query($cr_query);
    $cr_result = $sql["mgr"]->fetch_assoc($cr_result);
    $credits = $cr_result["Credits"];

    // since this action is delayed, we have to make sure the account still has sufficient funds
    // if the account doesn't have enough, we just delete the change request
    if ( ( $credits >= 0 ) && ( $credits < $race_credits ) )
      $int_err = 1;

    if ( !$int_err )
    {
      // we don't charge credits if the account is unlimited
      if ( $credits >= 0 )
        $credits = $credits - $race_credits;

      $money_query = "UPDATE config_accounts SET Credits='".$credits."' WHERE Login='".$username."'";

      $money_result = $sql["mgr"]->query($money_query);
    }
  }

  if ( !$int_err )
    $result = $sql["char"]->query("UPDATE characters SET race='".$name["new_race"]."' WHERE guid='".$guid."'");

  $result = $sql["mgr"]->query("DELETE FROM char_changes WHERE guid='".$guid."'");

  // this_is_junk: The retail version of this swaps the character's old home faction reputation with
  // their reputation with the new faction.  So, an Orc wanting to become a Blood Elf would have
  // her reputation with Orgrimmar swapped with their rep for Silvermoon.  Because of how ArcEmu stores
  // reputation, I don't want to have to mess with this atm.  It's not life-or-death because you can only
  // change within Horde or Alliance not between, so, you can just build up your 'new' home rep.
  // They also swap the mounts too, but that's silly. ^_^

  redirect("index.php");
}


//########################################################################################################################
// MAIN
//########################################################################################################################
$err = ( ( isset($_GET["error"]) ) ? $_GET["error"] : NULL );

$output .= '
        <div class="bubble">
          <div class="top">';

if ( $err == 1 )
  $output .= '
            <h1><span class="error">'.lang("global", "empty_fields").'</span></h1>';
elseif ( $err == 2 )
  $output .= '
            <h1><span class="error">'.lang("xrace", "nomatch").'</span></h1>';
elseif ( $err == 3 )
  $output .= '
            <h1><span class="error">'.lang("xrace", "already").'</span></h1>';
elseif ( $err == 5 )
  $output .= '
            <h1>'.lang("xrace", "done").'</h1>';
elseif ( $err == 6 )
  $output .= '
            <h1><span class="error">'.lang("xrace", "insufficient_credits").'</span></h1>';
else
  $output .= '
            <h1>'.lang("xrace", "changerace").'</h1>';

unset($err);

$output .= '
          </div>';

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

if ( $action == "chooserace" )
  chooserace();
elseif ( $action == "getapproval" )
  getapproval();
elseif ( $action == "denied" )
  denied();
elseif ( $action == "approve" )
  saverace();
else
  sel_char();

unset($action);
unset($action_permission);

require_once "footer.php";


?>
