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

//########################################################################################################################
// SHOW CHARACTER LIST
//########################################################################################################################
function show_list()
{
  global $realm_id, $output, $logon_db, $characters_db, $itemperpage, $action_permission, $user_lvl, $sql, $core;

  valid_login($action_permission["view"]);

  if ( $core == 1 )
    $query = "SELECT * FROM characters WHERE acct='".$_SESSION["user_id"]."'";
  else
    $query = "SELECT * FROM characters WHERE account='".$_SESSION["user_id"]."'";
  $result = $sql["char"]->query($query);
  $num_rows = $sql["char"]->num_rows($result);

  $output .= '
          <table class="top_hidden">
            <tr>
              <td>';
  $output .= '
                <div class="half_frame fieldset_border center">
                  <span class="legend">'.lang("questitem", "selectchar").'</span>';
  if ( $num_rows == 0 )
  {
    // Localization
    $nochars = lang("questitem", "nochars");
    $nochars = str_replace("%1", $_SESSION["login"], $nochars);

    $output .= '
                  <b>'.$nochars.'</b>
                  <br />
                  <br />';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def",130);
  }
  else
  {
    $output .= '
                  <form method="get" action="questitem_vendor.php" id="form">
                    <div>
                      <input type="hidden" name="action" value="selected_char" />
                    </div>
                    <table class="lined" id="xname_char_table">
                      <tr>
                        <th class="xname_radio">&nbsp;</th>
                        <th class="xname_name">'.lang("xname", "char").'</th>
                        <th class="xname_LRC">'.lang("xname", "lvl").'</th>
                        <th class="xname_LRC">'.lang("xname", "race").'</th>
                        <th class="xname_LRC">'.lang("xname", "class").'</th>
                      </tr>';
    if( $num_rows > 1 )
    {
      while ( $field = $sql["char"]->fetch_assoc($result) )
      {
        $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="charname" value="'.$field["name"].'" />
                        </td>
                        <td>'.$field["name"].'</td>
                        <td>'.char_get_level_color($field["level"]).'</td>
                        <td>
                          <img src="img/c_icons/'.$field["race"].'-'.$field["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($field["race"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                        </td>
                        <td>
                          <img src="img/c_icons/'.$field["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($field["class"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                        </td>
                      </tr>';
      }
    }
    else
    {
      $field = $sql["char"]->fetch_assoc($result);
      $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="charname" value="'.$field["name"].'" checked="true" />
                        </td>
                        <td>'.$field["name"].'</td>
                        <td>'.char_get_level_color($field["level"]).'</td>
                        <td>
                          <img src="img/c_icons/'.$field["race"].'-'.$field["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($field["race"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                        </td>
                        <td>
                          <img src="img/c_icons/'.$field["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($field["class"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                        </td>
                      </tr>';
    }
    $output .= '
                      <tr>
                        <td class="hidden" colspan="3">';
    makebutton(lang("questitem", "select"), "javascript:do_submit()\" type=\"def",180);
    $output .= '
                        </td>
                        <td class="hidden" colspan="2">';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def",130);
    $output .= '
                        </td>
                      </tr>
                    </table>';
    $output .= '
                    </form>';
  }
  $output .= '
                </div>
              </td>
            </tr>
          </table>';

}


//########################################################################################################################
// SHOW CHARACTER'S QUESTS
//########################################################################################################################
function select_quest()
{
  global $world_db, $characters_db, $realm_id, $user_name, $output, $locales_search_option,
    $action_permission, $user_lvl, $sql, $core;

  valid_login($action_permission["view"]);

  if ( empty($_GET["charname"]) )
    redirect("questitem_vendor.php?error=1");

  if ( $core == 1 )
    $query = "SELECT guid, gold, level FROM characters WHERE name='".$_GET["charname"]."'";
  else
    $query = "SELECT guid, money AS gold, level FROM characters WHERE name='".$_GET["charname"]."'";
  $result = $sql["char"]->query($query);
  $field = $sql["char"]->fetch_assoc($result);
  $guid = $field["guid"];

  if ( $core == 1 )
    $query = "SELECT * FROM questlog WHERE player_guid='".$guid."'";
  elseif ( $core == 2 )
    $query = "SELECT *, quest AS quest_id FROM character_queststatus WHERE guid='".$guid."' AND status<>0 AND rewarded=0";
  else
    $query = "SELECT *, quest AS quest_id FROM character_queststatus WHERE guid='".$guid."' AND status<>0";

  $result = $sql["char"]->query($query);
  $num_rows = $sql["char"]->num_rows($result);

  $output .= '
          <table class="top_hidden">
            <tr>
              <td>
                <div class="half_frame fieldset_border center">
                  <span class="legend">'.lang("questitem", "selectquest").'</span>';

  if ( $num_rows == 0 )
  {
    // Localization
    $noquests = lang("questitem", "noquests");
    $noquests = str_replace("%1", $_GET["charname"], $noquests);

    $output .= '
                  <table style="width: 100%; text-align: center;">
                    <tr>
                      <td>
                        <b>'.$noquests.'</b>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <div style="width: 130px;" class="center">';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def", 130);
    $output .= '
                        </div>
                      </td>
                    </tr>
                  </table>';
  }
  else
  {
    $output .= '
                  <form method="get" action="questitem_vendor.php" id="form">
                    <div>
                      <input type="hidden" name="action" value="selected_quest" />
                      <input type="hidden" name="chargold" value="'.$field["gold"].'" />
                      <input type="hidden" name="charname" value="'.$_GET["charname"].'" />
                      <input type="hidden" name="charlevel" value="'.$field["level"].'" />
                    </div>
                    <table id="qiv_quest_select">';
    if ( $num_rows > 1 )
    {
      while ( $field = $sql["char"]->fetch_assoc($result) )
      {
        if ( $core == 1 )
          $qquery = "SELECT *, Title AS Title1 FROM quests "
                      .( ( $locales_search_option != 0 ) ? "LEFT JOIN quests_localized ON (quests_localized.entry=quests.entry AND language_code='".$locales_search_option."' ) " : " " ).
                    "WHERE quests.entry='".$field["quest_id"]."'";
        elseif ( $core == 2 )
          $qquery = "SELECT *, Title AS Title1 FROM quest_template "
                      .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_quest ON locales_quest.entry=quest_template.entry " : " " ).
                    "WHERE quest_template.entry='".$field["quest_id"]."'";
        else
          $qquery = "SELECT *, Title AS Title1, Id AS entry FROM quest_template "
                      .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_quest ON locales_quest.entry=quest_template.Id " : " " ).
                    "WHERE quest_template.Id='".$field["quest_id"]."'";
        $qresult = $sql["world"]->query($qquery);
        $quest = $sql["world"]->fetch_assoc($qresult);

        // Localization
        if ( $locales_search_option == 0 )
          $quest["Title"] = $quest["Title1"];
        else
        {
          if ( $core == 1 )
            $quest["Title"] = $quest["Title"];
          else
            $quest["Title"] = $quest["Title_loc".$locales_search_option];
        }

        $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="charquest" value="'.$quest["entry"].'" />'.$quest["Title"].'
                        </td>
                      </tr>';
      }
    }
    else
    {
      $field = $sql["char"]->fetch_assoc($result);
      if ( $core == 1 )
        $qquery = "SELECT *, Title AS Title1 FROM quests WHERE "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN quests_localized ON (quests_localized.entry=quests.entry AND language_code='".$locales_search_option."' ) " : " " ).
                  "WHERE quests.entry='".$field["quest_id"]."'";
      elseif ( $core == 2 )
        $qquery = "SELECT *, Title AS Title1 FROM quest_template "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_quest ON locales_quest.entry=quest_template.entry " : " " ).
                  "WHERE quest_template.entry='".$field["quest_id"]."'";
      else
        $qquery = "SELECT *, Title AS Title1, Id AS entry FROM quest_template "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_quest ON locales_quest.entry=quest_template.Id " : " " ).
                  "WHERE quest_template.Id='".$field["quest_id"]."'";
      $qresult = $sql["world"]->query($qquery);
      $quest = $sql["char"]->fetch_assoc($qresult);

      // Localization
      if ( $locales_search_option == 0 )
        $quest["Title"] = $quest["Title1"];
      else
      {
        if ( $core == 1 )
          $quest["Title"] = $quest["Title"];
        else
          $quest["Title"] = $quest["Title_loc".$locales_search_option];
      }

      $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="charquest" value="'.$quest["entry"].'" checked="true" />'.$quest["Title"].'
                        </td>
                      </tr>';
    }
    $output .= '
                      <tr>
                        <td>';
    makebutton(lang("questitem", "select"), "javascript:do_submit()\" type=\"def",180);
    $output .= '
                        </td>
                        <td>';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def",130);
    $output .= '
                        </td>
                      </tr>
                    </table>';
    $output .= '
                  </form>';
  }
  $output .= '
                </div>
              </td>
            </tr>
          </table>';
}


//########################################################################################################################
// SHOW QUEST'S ITEMS
//########################################################################################################################
function select_item()
{
  global $world_db, $characters_db, $realm_id, $user_name, $output, $locales_search_option,
    $action_permission, $user_lvl, $sql, $core;

  valid_login($action_permission["view"]);

  if ( empty($_GET["charquest"]) )
    redirect("questitem_vendor.php?error=1");

  if ( $core == 1 )
    $query = "SELECT * FROM quests WHERE entry='".$_GET["charquest"]."'";
  elseif ( $core == 2 )
    $query = "SELECT * FROM quest_template WHERE entry='".$_GET["charquest"]."'";
  else
    $query = "SELECT *, Id AS entry, RequiredItemId1 AS ReqItemId1, RequiredItemCount1 AS ReqItemCount1, RequiredItemId2 AS ReqItemId2, RequiredItemCount2 AS ReqItemCount2, RequiredItemId3 AS ReqItemId3, RequiredItemCount3 AS ReqItemCount3, RequiredItemId4 AS ReqItemId4, RequiredItemCount4 AS ReqItemCount4, RequiredItemId5 AS ReqItemId5, RequiredItemCount5 AS ReqItemCount5, RequiredItemId6 AS ReqItemId6, RequiredItemCount6 AS ReqItemCount6 FROM quest_template WHERE Id='".$_GET["charquest"]."'";
  $result = $sql["world"]->query($query);
  $quest = $sql["world"]->fetch_assoc($result);

  $output .= '
          <table class="top_hidden">
            <tr>
              <td>
                <div class="half_frame fieldset_border">
                  <span class="legend">'.lang("questitem", "selectitem").'</span>';

  if ( $quest["ReqItemId1"] == 0 )
  {
    // Localization
    $noitems = lang("questitem", "noitems");
    $noitems = str_replace("%1", $quest["Title"], $noitems);

    $output .= '
                  <table id="qiv_item_select">
                    <tr>
                      <td>
                        <b>'.$noitems.'</b>
                      </td>
                    </tr>
                    <tr>
                      <td>';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def", 130);
    $output .= '
                      </td>
                    </tr>
                  </table>';
  }
  else
  {
    $output .= '
                  <form method="get" action="questitem_vendor.php" id="form">
                    <div>
                      <input type="hidden" name="action" value="selected_item" />
                      <input type="hidden" name="charname" value="'.$_GET["charname"].'" />
                      <input type="hidden" name="charquest" value="'.$_GET["charquest"].'" />
                    </div>
                    <table id="qiv_item_select">';
    if ( $quest["ReqItemId1"] )
    {
      if ( $core == 1 )
        $iquery = "SELECT * FROM items "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                  "WHERE items.entry='".$quest["ReqItemId1"]."'";
      else
        $iquery = "SELECT *, name AS name1 FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$quest["ReqItemId1"]."'";

      $iresult = $sql["world"]->query($iquery);
      $item = $sql["world"]->fetch_assoc($iresult);

      // Localization
      if ( $locales_search_option != 0 )
      {
        if ( $core == 1 )
          $item["name1"] = $item["name"];
        else
          $item["name1"] = $item["name_loc".$locales_search_option];
      }
      else
        $item["name1"] = $item["name1"];

      $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="questitem" value="'.$item["entry"].'_'.$quest["ReqItemCount1"].'" />'.$item["name1"].'
                        </td>
                      </tr>';
    }
    if ( $quest["ReqItemId2"] <> 0 )
    {
      if ( $core == 1 )
        $iquery = "SELECT * FROM items "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                  "WHERE items.entry='".$quest["ReqItemId2"]."'";
      else
        $iquery = "SELECT *, name AS name1 FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$quest["ReqItemId2"]."'";
      $iresult = $sql["world"]->query($iquery);
      $item = $sql["world"]->fetch_assoc($iresult);

      // Localization
      if ( $locales_search_option != 0 )
      {
        if ( $core == 1 )
          $item["name1"] = $item["name"];
        else
          $item["name1"] = $item["name_loc".$locales_search_option];
      }
      else
        $item["name1"] = $item["name1"];

      $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="questitem" value="'.$item["entry"].'_'.$quest["ReqItemCount2"].'" />'.$item["name1"].'
                        </td>
                      </tr>';
    }
    if ( $quest["ReqItemId3"] <> 0 )
    {
      if ( $core == 1 )
        $iquery = "SELECT * FROM items "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                  "WHERE items.entry='".$quest["ReqItemId3"]."'";
      else
        $iquery = "SELECT *, name AS name1 FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$quest["ReqItemId3"]."'";
      $iresult = $sql["world"]->query($iquery);
      $item = $sql["world"]->fetch_assoc($iresult);

      // Localization
      if ( $locales_search_option != 0 )
      {
        if ( $core == 1 )
          $item["name1"] = $item["name"];
        else
          $item["name1"] = $item["name_loc".$locales_search_option];
      }
      else
        $item["name1"] = $item["name1"];

      $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="questitem" value="'.$item["entry"].'_'.$quest["ReqItemCount3"].'" />'.$item["name1"].'
                        </td>
                      </tr>';
    }
    if ( $quest["ReqItemId4"] <> 0 )
    {
      if ( $core == 1 )
        $iquery = "SELECT * FROM items "
                    .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                  "WHERE items.entry='".$quest["ReqItemId4"]."'";
      else
        $iquery = "SELECT *, name AS name1 FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$quest["ReqItemId4"]."'";
      $iresult = $sql["world"]->query($iquery);
      $item = $sql["world"]->fetch_assoc($iresult);

      // Localization
      if ( $locales_search_option != 0 )
      {
        if ( $core == 1 )
          $item["name1"] = $item["name"];
        else
          $item["name1"] = $item["name_loc".$locales_search_option];
      }
      else
        $item["name1"] = $item["name1"];

      $output .= '
                      <tr>
                        <td>
                          <input type="radio" name="questitem" value="'.$item["entry"].'_'.$quest["ReqItemCount4"].'" />'.$item["name1"].'
                        </td>
                      </tr>';
    }
    $output .= '
                      <tr>
                        <td>';
    makebutton(lang("questitem", "select"), "javascript:do_submit()\" type=\"def",180);
    $output .= '
                        </td>
                        <td>';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def",130);
    $output .= '
                        </td>
                      </tr>
                    </table>
                  </form>';
  }
  $output .= '
                </div>
              </td>
            </tr>
          </table>';
}


//########################################################################################################################
// SELECT QUANTITY OF ITEM
//########################################################################################################################
function select_quantity()
{
  global $world_db, $characters_db, $realm_id, $user_name, $output, $action_permission, $user_lvl,
    $locales_search_option, $quest_item, $qiv_credits, $qiv_money, $credits_fractional, $sql, $core;

  valid_login($action_permission["view"]);

  if ( empty($_GET["questitem"]) )
    redirect("questitem_vendor.php?error=1");

  if ( $core == 1 )
    $query = "SELECT *, Title AS Title1 FROM quests "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN quests_localized ON (quests_localized.entry=quests.entry AND language_code='".$locales_search_option."' ) " : " " ).
              "WHERE quests.entry='".$_GET["charquest"]."'";
  elseif ( $core == 2 )
    $query = "SELECT *, Title AS Title1, RewOrReqMoney AS RewMoney FROM quest_template "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_quest ON locales_quest.entry=quest_template.entry " : " " ).
              "WHERE quest_template.entry='".$_GET["charquest"]."'";
  else
    $query = "SELECT *, Title AS Title1, RewardOrRequiredMoney AS RewMoney FROM quest_template "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_quest ON locales_quest.entry=quest_template.Id " : " " ).
              "WHERE quest_template.Id='".$_GET["charquest"]."'";
  $result = $sql["world"]->query($query);
  $quest = $sql["world"]->fetch_assoc($result);

  // Localization
  if ( $locales_search_option == 0 )
    $quest["Title"] = $quest["Title1"];
  else
  {
    if ( $core == 1 )
      $quest["Title"] = $quest["Title"];
    else
      $quest["Title"] = $quest["Title_loc".$locales_search_option];
  }

  // this_is_junk: We have to pass the required count with the item id or we'll get the required counts
  //               for every other item the quest requires.
  $questitem = explode("_", $_GET["questitem"]);
  $count = $questitem[1];
  $questitem = $questitem[0];

  if ( $core == 1 )
    $iquery = "SELECT * FROM items "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
              "WHERE items.entry='".$questitem."'";
  else
    $iquery = "SELECT *, name AS name1 FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$questitem."'";
  $iresult = $sql["world"]->query($iquery);
  $item = $sql["world"]->fetch_assoc($iresult);

  // Localization
  if ( $locales_search_option != 0 )
  {
    if ( $core == 1 )
      $item["name1"] = $item["name"];
    else
      $item["name1"] = $item["name_loc".$locales_search_option];
  }
  else
    $item["name1"] = $item["name1"];

  if ( $core == 1 )
    $cquery = "SELECT guid, level, gold FROM characters WHERE name='".$_GET["charname"]."'";
  else
    $cquery = "SELECT guid, level, money AS gold FROM characters WHERE name='".$_GET["charname"]."'";
  $cresult = $sql["char"]->query($cquery);
  $char = $sql["char"]->fetch_assoc($cresult);

  if ( $core == 1 )
    $ciquery = "SELECT * FROM playeritems
                WHERE ownerguid='".$char["guid"]."' AND entry='".$questitem."'";
  elseif ( $core == 2 )
    $ciquery = "SELECT * FROM character_inventory
                  LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
                WHERE character_inventory.guid='".$char["guid"]."' AND item_template='".$questitem."'";
  else
    $ciquery = "SELECT * FROM character_inventory
                  LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
                WHERE character_inventory.guid='".$char["guid"]."' AND itemEntry='".$questitem."'";
  $ciresult = $sql["char"]->query($ciquery);
  $cifield = $sql["char"]->fetch_assoc($ciresult);
  $cinumrows = $sql["char"]->num_rows($ciresult);
  if ( $cinumrows == 0 )
  {
    $have = 0;
  }
  elseif ( $cinumrows == 1 )
  {
    $have = $cifield["count"];
  }
  else
  {
    $have = 0;
    while ( $field = $sql["char"]->fetch_assoc($ciresult) )
    {
      $have = $have + $field["count"];
    }
  }

  $chargold = $char["gold"];
  $chargold = str_pad($chargold, 4, "0", STR_PAD_LEFT);
  $pg = substr($chargold,  0, -4);
  if ( $pg == "" )
    $pg = 0;
  $ps = substr($chargold, -4,  2);
  if ( ( $ps == "" ) || ( $ps == "00" ) )
    $ps = 0;
  $pc = substr($chargold, -2);
  if ( ( $pc == "" ) || ( $pc == "00" ) )
    $pc = 0;

  $RewMoney = $quest["RewMoney"];
  $RewMoney = str_pad($RewMoney, 4, "0", STR_PAD_LEFT);
  $rg = substr($RewMoney,  0, -4);
  if ( $rg == "" )
    $rg = 0;
  $rs = substr($RewMoney, -4,  2);
  if ( ( $rs == "" ) || ( $rs == "00" ) )
    $rs = 0;
  $rc = substr($RewMoney, -2);
  if ( ( $rc == "" ) || ( $rc == "00" ) )
    $rc = 0;

  // Localization
  $char_has_money = lang("questitem", "has");
  $char_has_money = str_replace("%1", '<b>'.$_GET["charname"].'</b>', $char_has_money);
  $char_money_display = $pg.'<img src="img/gold.gif" alt="gold" style="position: relative; bottom: -6px;" />'
                      .$ps.'<img src="img/silver.gif" alt="silver" style="position: relative; bottom: -6px;" />'
                      .$pc.'<img src="img/copper.gif" alt="copper" style="position: relative; bottom: -6px;" />';
  $char_has_money = str_replace("%2", $char_money_display, $char_has_money);
  
  $quest_will_reward = lang("questitem", "willreward");
  $quest_will_reward = str_replace("%1", '<b>'.$quest["Title"].'</b>', $quest_will_reward);
  $quest_reward_display = $rg.'<img src="img/gold.gif" alt="gold" style="position: relative; bottom: -6px;" />'
                        .$rs.'<img src="img/silver.gif" alt="silver" style="position: relative; bottom: -6px;" />'
                        .$rc.'<img src="img/copper.gif" alt="copper" style="position: relative; bottom: -6px;" />';
  $quest_will_reward = str_replace("%2", $quest_reward_display, $quest_will_reward);

  $output .= '
          <table class="top_hidden">
            <tr>
              <td>
                <div class="half_frame fieldset_border center center_text">
                  <span class="legend">'.lang("questitem", "selectquantity").'</span>';

  // Localization
  $requires = lang("questitem", "requires");
  $requires = str_replace("%1", '<b>'.$quest["Title"].'</b>', $requires);
  $requires = str_replace("%2", '<span id="qiv_quest_requires">'.$count.'</span>', $requires);
  $requires = str_replace("%3", '<b>'.$item["name1"].'</b>', $requires);
  $requires = str_replace("%4", '<br />', $requires);
  $requires = str_replace("%5", '<b>'.$_GET["charname"].'</b>', $requires);
  $requires = str_replace("%6", '<span id="qiv_player_has">'.$have.'</span>', $requires);

  $output .= $requires;
  $output .= '
                  <br />
                  <br />';

  $output .= $quest_will_reward;
  $output .= '
                  <br />
                  <br />';

  if ( $quest["RewMoney"] == 0 )
    $gold = $char["level"] * $quest_item["levelMul"];
  else
    $gold = $quest["RewMoney"] * $quest_item["rewMul"];
  $gold = str_pad($gold, 4, "0", STR_PAD_LEFT);
  $cg = substr($gold,  0, -4);
  if ( $cg == "" )
    $cg = 0;
  $cs = substr($gold, -4,  2);
  if ( ( $cs == "" ) || ( $cs == "00" ) )
    $cs = 0;
  $cc = substr($gold, -2);
  if ( ( $cc == "" ) || ( $cc == "00" ) )
    $cc = 0;

  // Localization
  $per_item = lang("questitem", "peritem");
  $per_item = str_replace("%1", '<b>'.$item["name1"].'</b>', $per_item);
  $item_cost_display = $cg.'<img src="img/gold.gif" alt="gold" style="position: relative; bottom: -6px;" />'
                    .$cs.'<img src="img/silver.gif" alt="silver" style="position: relative; bottom: -6px;" />'
                    .$cc.'<img src="img/copper.gif" alt="copper" style="position: relative; bottom: -6px;" />';
  $per_item = str_replace("%2", $item_cost_display, $per_item);

  $output .= $per_item;
  $output .= '
                  <br />
                  <br />';

  $output .= $char_has_money;
  $output .= '
                  <br />
                  <br />';

  // credits
  if ( $qiv_money > 0 )
  {
    // get our credit balance
    $query = "SELECT Credits FROM config_accounts WHERE Login='".$user_name."'";
    $result = $sql["mgr"]->query($query);
    $result = $sql["mgr"]->fetch_assoc($result);
    $credits = $result["Credits"];

    if ( $credits < 0 )
    {
      // unlimited credits
      $output .= lang("global", "credits_unlimited");
      $output .= '
                  <br />
                  <br />';
    }
    elseif ( $credits >= 0 )
    {
      $credit_cost = $qiv_credits * ($gold / $qiv_money);

      // if Allow Fractional Credits is disabled then cost must be a whole number
      $credit_cost = ( ( !$credits_fractional ) ? ceil($credit_cost) : $credit_cost );

      $credits_per_item = lang("questitem", "credits_peritem");
      $credits_per_item = str_replace("%1", '<b>'.$credit_cost.'</b>', $credits_per_item);
      $credits_per_item = str_replace("%2", '<b>'.$item["name1"].'</b>', $credits_per_item);

      $output .= $credits_per_item;
      $output .= '
                  <br />
                  <br />';

      $credits_avail = lang("questitem", "credits_avail");
      $credits_avail = str_replace("%1", '<b>'.(float)$credits.'</b>', $credits_avail);

      $output .= $credits_avail;
      $output .= '
                  <br />
                  <br />';
    }
  }

  $need = $count - $have;

  $output .= '
                  <form method="get" action="questitem_vendor.php" id="form">
                    <div>
                      <input type="hidden" name="action" value="selected_quantity" />
                    </div>
                    <table class="center">
                      <tr>
                        <td colspan="2">'
                            .lang("questitem", "wanted").': <input type="text" name="want" value="'.$need.'" />
                            <input type="hidden" name="charname" value="'.$_GET["charname"].'" />
                            <input type="hidden" name="gold" value="'.$gold.'" />
                            <input type="hidden" name="item" value="'.$item["entry"].'" />
                        </td>
                      </tr>
                      <tr>
                        <td>';
  makebutton(lang("questitem", "submit"), "javascript:do_submit()\" type=\"def",180);
  $output .= '
                        </td>
                        <td>';
  makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def",130);
  $output .= '
                        </td>
                      </tr>
                    </table>';
  $output .= '
                  </form>
                </div>
              </td>
            </tr>
          </table>';
}


//########################################################################################################################
// APPROVE TOTAL COST AND PURCHASE
//########################################################################################################################
function approve()
{
  global $world_db, $characters_db, $realm_id, $user_name, $output, $action_permission, $user_lvl,
    $locales_search_option, $quest_item, $qiv_credits, $qiv_money, $credits_fractional, $sql, $core;

  valid_login($action_permission["view"]);

  if ( empty($_GET["item"]) )
    redirect("questitem_vendor.php?error=1");
  if ( empty($_GET["gold"]) )
    redirect("questitem_vendor.php?error=1");
  if ( empty($_GET["want"]) )
    redirect("questitem_vendor.php?error=1");

  if ( $core == 1 )
    $query = "SELECT * FROM items "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
              "WHERE items.entry='".$_GET["item"]."'";
  else
    $query = "SELECT *, name AS name1 FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$_GET["item"]."'";
  $result = $sql["world"]->query($query);
  $item = $sql["world"]->fetch_assoc($result);

  // Localization
  if ( $locales_search_option != 0 )
  {
    if ( $core == 1 )
      $item["name1"] = $item["name"];
    else
      $item["name1"] = $item["name_loc".$locales_search_option];
  }
  else
    $item["name1"] = $item["name1"];

  $cquery = "SELECT *, money AS gold FROM characters WHERE name='".$_GET["charname"]."'";
  $cresult = $sql["char"]->query($cquery);
  $char = $sql["char"]->fetch_assoc($cresult);

  $total = $_GET["gold"] * $_GET["want"];
  $total = str_pad($total, 4, "0", STR_PAD_LEFT);
  $cg = substr($total,  0, -4);
  if ( $cg == "" )
    $cg = 0;
  $cs = substr($total, -4,  2);
  if ( ( $cs == "" ) || ( $cs == "00" ) )
    $cs = 0;
  $cc = substr($total, -2);
  if ( ( $cc == "" ) || ( $cc == "00" ) )
    $cc = 0;

  // credits
  if ( $qiv_money > 0 )
  {
    // get our credit balance
    $cr_query = "SELECT Credits FROM config_accounts WHERE Login='".$user_name."'";
    $cr_result = $sql["mgr"]->query($cr_query);
    $cr_result = $sql["mgr"]->fetch_assoc($cr_result);
    $credits = $cr_result["Credits"];

    $credit_cost = $qiv_credits * ($_GET["gold"] / $qiv_money);

    // if Allow Fractional Credits is disabled then cost must be a whole number
    $credit_cost = ( ( !$credits_fractional ) ? ceil($credit_cost) : $credit_cost );

    // multiply by quantity desired
    $credit_cost = $credit_cost * $_GET["want"];
  }

  $output .= '
          <table class="top_hidden">
            <tr>
              <td>
                <div class="half_frame fieldset_border center center_text">
                  <span class="legend">'.lang("questitem", "approvecost").'</span>
                  <table>';

  if ( $total > $char["gold"] )
  {
    // Localization
    $poor = lang("questitem", "insufficientfunds");
    $poor = str_replace("%1", '<b>'.$char["name"].'</b>', $poor);
    $poor = str_replace("%2", '<span id="qiv_insuffiecient_funds">'.$_GET["want"].'</span>', $poor);
    $poor = str_replace("%3", '<b>'.$item["name1"].'</b>', $poor);

    $output .= '
                    <tr>
                      <td colspan="3">';
    $output .= $poor;
    $output .= '
                      </td>
                    </tr>';
  }
  else
  {
    // Localization
    $purchase = lang("questitem", "purchase");
    $purchase = str_replace("%1", '<span id="qiv_approve_quantity">'.$_GET["want"].'</span>', $purchase);
    $purchase = str_replace("%2", '<b>'.$item["name1"].'</b>', $purchase);
    $gold_display = $cg.'<img src="img/gold.gif" alt="gold" style="position: relative; bottom: -6px;" /> '
                    .$cs.'<img src="img/silver.gif" alt="silver" style="position: relative; bottom: -6px;" /> '
                    .$cc.'<img src="img/copper.gif" alt="copper" style="position: relative; bottom: -6px;" />';
    $purchase = str_replace("%3", $gold_display, $purchase);

    $output .= '
                    <tr>
                      <td colspan="3">';
    $output .= $purchase;
    $output .= '
                      </td>
                    </tr>';
  }

  if ( ( $total > $char["gold"] ) && ( ( $credit_cost > $credits ) && ( $credits >= 0 ) ) )
  {
    $output .= '
                    <tr>
                      <td colspan="3">
                        <span>'.lang("questitem", "and").'</span>
                      </td>
                    </tr>';
  }
  else
  {
    $output .= '
                    <tr>
                      <td colspan="3">
                        <span>'.lang("questitem", "or").'</span>
                      </td>
                    </tr>';
  }

  if ( $credits >= 0 )
  {
    if ( $credit_cost > $credits )
    {
      // Localization
      $poor = lang("questitem", "insufficient_credits");
      $poor = str_replace("%1", '<span id="qiv_insufficient_funds">'.$_GET["want"].'</span>', $poor);
      $poor = str_replace("%2", '<b>'.$item["name1"].'</b>', $poor);

      $output .= '
                    <tr>
                      <td colspan="3">';
      $output .= $poor;
      $output .= '
                      </td>
                    </tr>';
    }
    else
    {
      // Localization
      $purchase = lang("questitem", "credits_purchase");
      $purchase = str_replace("%1", '<span id="qiv_approve_quantity">'.$_GET["want"].'</span>', $purchase);
      $purchase = str_replace("%2", '<b>'.$item["name1"].'</b>', $purchase);
      $purchase = str_replace("%3", $credit_cost, $purchase);

      $output .= '
                    <tr>
                      <td colspan="3">';
      $output .= $purchase;
      $output .= '
                      </td>
                    </tr>';
    }
  }
  else
  {
    // Unlimited Credits
    $output .= '
                    <tr>
                      <td colspan="3">
                        <span>'.lang("global", "credits_unlimited").'</span>
                      </td>
                    </tr>';

    // to make deciding whether to display the Use Credits button easier,
    // we'll fake our credit balance...
    $credits = $credit_cost;
  }

  if ( ( $total > $char["gold"] ) && ( $credit_cost > $credits ) )
  {
    $output .= '
                    <tr>
                      <td align="left">';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def", 130);
    $output .= '
                      </td>
                    </tr>';
  }
  else
  {
    $output .= '
                    <tr>';
    if ( $total <= $char["gold"] )
    {
      $output .= '
                      <td>';
      makebutton(lang("questitem", "submit_money"), "questitem_vendor.php?action=purchase&amp;mode=money&amp;char=".$char["name"]."&amp;item=".$item["entry"]."&amp;want=".$_GET["want"]."&amp;total=".$total."\" type=\"def", 180);
      $output .= '
                      </td>';
    }
    else
    {
      $output .= '
                      <td></td>';
    }

    if ( $credit_cost <= $credits )
    {
      $output .= '
                      <td>';
      makebutton(lang("questitem", "submit_credits"), "questitem_vendor.php?action=purchase&amp;mode=credits&amp;char=".$char["name"]."&amp;item=".$item["entry"]."&amp;want=".$_GET["want"]."&amp;total=".$credit_cost."\" type=\"def", 180);
      $output .= '
                      </td>';
    }
    else
    {
      $output .= '
                      <td></td>';
    }

    $output .= '
                      <td>';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def", 130);
    $output .= '
                      </td>
                    </tr>';
  }

  $output .= '
                  </table>
                </div>
              </td>
            </tr>
          </table>';
}


//########################################################################################################################
// CHARGE THE CHARACTER OR ACCOUNT AND SEND THE ITEM
//########################################################################################################################
function purchase()
{
  global $world_db, $characters_db, $realm_id, $user_name, $output, $action_permission, $user_lvl,
    $locales_search_option, $from_char, $stationary, $sql, $core;

  valid_login($action_permission["view"]);

  if ( empty($_GET["item"]) )
    redirect("questitem_vendor.php?error=1");
  if ( empty($_GET["total"]) )
    redirect("questitem_vendor.php?error=1");
  if ( empty($_GET["want"]) )
    redirect("questitem_vendor.php?error=1");

  $mode = $_GET["mode"];

  if ( $core == 1 )
    $iquery = "SELECT * FROM items "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
              "WHERE items.entry='".$_GET["item"]."'";
  else
    $iquery = "SELECT * FROM item_template "
                .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
              "WHERE item_template.entry='".$_GET["item"]."'";
  $iresult = $sql["world"]->query($iquery);
  $item = $sql["world"]->fetch_assoc($iresult);

  // Localization
  if ( $locales_search_option != 0 )
  {
    if ( $core == 1 )
      $item["name1"] = $item["name"];
    else
      $item["name1"] = $item["name_loc".$locales_search_option];
  }
  else
    $item["name1"] = $item["name1"];

  if ( $core == 1 )
    $cquery = "SELECT * FROM characters WHERE name='".$_GET["char"]."'";
  else
    $cquery = "SELECT *, money AS gold FROM characters WHERE name='".$_GET["char"]."'";
  $cresult = $sql["char"]->query($cquery);
  $char = $sql["char"]->fetch_assoc($cresult);

  if ( $mode == "money" )
  {
    $char_money = $char["gold"];
    $char_money = $char_money - $_GET["total"];

    if ( $core == 1 )
      $money_query = "UPDATE characters SET gold='".$char_money."' WHERE guid='".$char["guid"]."'";
    else
      $money_query = "UPDATE characters SET money='".$char_money."' WHERE guid='".$char["guid"]."'";

    $money_result = $sql["char"]->query($money_query);
  }
  else
  {
    // get our credit balance
    $cr_query = "SELECT Credits FROM config_accounts WHERE Login='".$user_name."'";
    $cr_result = $sql["mgr"]->query($cr_query);
    $cr_result = $sql["mgr"]->fetch_assoc($cr_result);
    $credits = $cr_result["Credits"];

    // we don't charge credits if the account is unlimited
    if ( $credits >= 0 )
      $credits = $credits - $_GET["total"];

    $money_query = "UPDATE config_accounts SET Credits='".$credits."' WHERE Login='".$user_name."'";

    $money_result = $sql["mgr"]->query($money_query);
  }

  if ( $core == 1 )
  {
    $mail_query = "INSERT INTO mailbox_insert_queue VALUES ('".$from_char."', '".$char["guid"]."', '".lang("questitem", "questitems")."', ".chr(34).$_GET["want"]."x ".$item["name1"].chr(34).", '".$stationary."', '0', '".$_GET["item"]."', '".$_GET["want"]."')";
    redirect("questitem_vendor.php&moneyresult=".$money_result);
  }
  else
  {
    // we need to be able to bypass mail.php's normal permissions to send mail
    $_SESSION["vendor_permission"] = 1;
    redirect("mail.php?action=send_mail&type=ingame_mail&to=".$char["name"]."&subject=".lang("questitem", "questitems")."&body=".$_GET["want"]."x ".$item["name"]."&group_sign==&group_send=gm_level&money=0&att_item=".$_GET["item"]."&att_stack=".$_GET["want"]."&redirect=questitem_vendor.php&moneyresult=".$money_result);
  }
}

function showresults()
{
  global $sql, $core;

  $mail_result = $sql["char"]->quote_smart($_GET["mailresult"]);
  $money_result = $sql["char"]->quote_smart($_GET["moneyresult"]);

  if ( $mail_result && $money_result )
    redirect("questitem_vendor.php?error=3");
  else
    redirect("questitem_vendor.php?error=2");
}


//########################################################################################################################
// MAIN
//########################################################################################################################
$err = ( ( isset($_GET["error"]) ) ? $_GET["error"] : NULL );

$output .= '
        <div class="bubble">
          <div class="top">';

switch ( $err )
{
  case 1:
    $output .= '
          <h1><span class="error">'.lang("global", "empty_fields").'</span></h1>';
    break;
  case 2:
    $output .= '
          <h1><span class="error">'.lang("questitem", "failed").'</span></h1>';
    break;
  case 3:
    $output .= '
          <h1>'.lang("questitem", "done").'</h1>';
    break;
  default: //no error
    $output .= '
          <h1>'.lang("questitem", "title").'</h1>';
}
unset($err);

$output .= "
        </div>";

// this is a pre-filter because mail from outside mail.php is priority
if ( $_GET["moneyresult"] )
  showresults();

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

switch ( $action )
{
  case "purchase":
    purchase();
    break;
  case "selected_quantity":
    approve();
    break;
  case "selected_item":
    select_quantity();
    break;
  case "selected_char":
    select_quest();
    break;
  case "selected_quest":
    select_item();
    break;
  default:
    show_list();
}

unset($action);
unset($action_permission);

require_once "footer.php";

?>
