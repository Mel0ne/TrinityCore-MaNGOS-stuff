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
// SHOW CHARACTERS QUESTS
//########################################################################################################################
function char_quest()
{
  global $output, $realm_id, $world_db, $logon_db, $characters_db, $action_permission, $locales_search_option,
    $site_encoding, $user_lvl, $user_name, $base_datasite, $quest_datasite, $itemperpage, $sql, $core;

  if ( empty($_GET["id"]) )
    error(lang("global", "empty_fields"));

  if ( empty($_GET["realm"]) )
    $realmid = $realm_id;
  else
  {
    $realmid = $sql["logon"]->quote_smart($_GET["realm"]);
    if ( is_numeric($realmid) )
      $sql["char"]->connect($characters_db[$realmid]['addr'], $characters_db[$realmid]['user'], $characters_db[$realmid]['pass'], $characters_db[$realmid]['name'], $characters_db[$realmid]["encoding"]);
    else
      $realmid = $realm_id;
  }

  $id = $sql["char"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    $id = 0;

  //==========================$_GET and SECURE=================================
  $start = ( ( isset($_GET["start"]) ) ? $sql["char"]->quote_smart($_GET["start"]) : 0 );
  if ( !is_numeric($start) )
    $start = 0;

  $order_by = ( ( isset($_GET["order_by"]) ) ? $sql["char"]->quote_smart($_GET["order_by"]) : 1 );
  if ( !is_numeric($order_by) )
    ;
  else
    $order_by = 1;

  $dir = ( ( isset($_GET["dir"]) ) ? $sql["char"]->quote_smart($_GET["dir"]) : 0 );
  if ( !preg_match('/^[01]{1}$/', $dir) )
    $dir = 0;

  $order_dir = ( ( $dir ) ? 'ASC' : 'DESC' );
  $dir = ( ( $dir ) ? 0 : 1 );
  //==========================$_GET and SECURE end=============================

  if ( $core == 1 )
    $result = $sql["char"]->query("SELECT acct, name, race, class, level, gender
      FROM characters WHERE guid='".$id."' LIMIT 1");
  else
    $result = $sql["char"]->query("SELECT account AS acct, name, race, class, level, gender
      FROM characters WHERE guid='".$id."' LIMIT 1");

  if ( $sql["char"]->num_rows($result) )
  {
    $char = $sql["char"]->fetch_assoc($result);

    // we get user permissions first
    $owner_acc_id = $sql["char"]->result($result, 0, 'acct');

    if ( $core == 1 )
      $result = $sql["logon"]->query("SELECT login FROM accounts WHERE acct='".$char["acct"]."'");
    else
      $result = $sql["logon"]->query("SELECT username AS login FROM account WHERE id='".$char["acct"]."'");

    $owner_name = $sql["logon"]->result($result, 0, 'login');
      
    $s_query = "SELECT *, SecurityLevel AS gm FROM config_accounts WHERE Login='".$owner_name."'";
    $s_result = $sql["mgr"]->query($s_query);
    $s_fields = $sql["mgr"]->fetch_assoc($s_result);
    $owner_gmlvl = $s_fields["gm"];
    $view_mod = $s_fields["View_Mod_Quest"];

    if ( $owner_gmlvl >= 1073741824 )
      $owner_gmlvl -= 1073741824;

    // owner configured overrides
    $view_override = false;
    if ( $view_mod > 0 )
    {
      if ( $view_mod == 1 )
        ;// TODO: Add friends limit
      elseif ( $view_mod == 2 )
      {
        // only registered users may view this page
        if ( $user_lvl > -1 )
          $view_override = true;
      }
    }

    // visibility overrides for specific tabs
    $view_inv_override = false;
    if ( $s_fields["View_Mod_Inv"] > 0 )
    {
      if ( $s_fields["View_Mod_Inv"] == 1 )
        ;// TODO: Add friends limit
      elseif ( $s_fields["View_Mod_Inv"] == 2 )
      {
        // only registered users may view this tab
        if ( $user_lvl > -1 )
          $view_inv_override = true;
      }
    }
    else
    {
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
        $view_inv_override = true;
    }

    $view_talent_override = false;
    if ( $s_fields["View_Mod_Talent"] > 0 )
    {
      if ( $s_fields["View_Mod_Talent"] == 1 )
        ;// TODO: Add friends limit
      elseif ( $s_fields["View_Mod_Talent"] == 2 )
      {
        // only registered users may view this tab
        if ( $user_lvl > -1 )
          $view_talent_override = true;
      }
    }
    else
    {
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
        $view_talent_override = true;
    }

    $view_achieve_override = false;
    if ( $s_fields["View_Mod_Achieve"] > 0 )
    {
      if ( $s_fields["View_Mod_Achieve"] == 1 )
        ;// TODO: Add friends limit
      elseif ( $s_fields["View_Mod_Achieve"] == 2 )
      {
        // only registered users may view this tab
        if ( $user_lvl > -1 )
          $view_achieve_override = true;
      }
    }
    else
    {
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
        $view_achieve_override = true;
    }

    $view_friends_override = false;
    if ( $s_fields["View_Mod_Friends"] > 0 )
    {
      if ( $s_fields["View_Mod_Friends"] == 1 )
        ;// TODO: Add friends limit
      elseif ( $s_fields["View_Mod_Friends"] == 2 )
      {
        // only registered users may view this tab
        if ( $user_lvl > -1 )
          $view_friends_override = true;
      }
    }
    else
    {
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
        $view_friends_override = true;
    }

    $view_view_override = false;
    if ( $s_fields["View_Mod_View"] > 0 )
    {
      if ( $s_fields["View_Mod_View"] == 1 )
        ;// TODO: Add friends limit
      elseif ( $s_fields["View_Mod_View"] == 2 )
      {
        // only registered users may view this tab
        if ( $user_lvl > -1 )
          $view_view_override = true;
      }
    }
    else
    {
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
        $view_view_override = true;
    }

    if ( ( $view_override ) || ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
    {
      $output .= '
            <div class="tab">
              <ul>
                <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "char_sheet").'</a></li>';

      if ( $view_inv_override )
        $output .= '
                <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "inventory").'</a></li>';

      if ( $view_talent_override )
        $output .= '
                '.( ( $char["level"] < 10 ) ? '' : '<li><a href="char_talent.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "talents").'</a></li>' ).'';

      if ( $view_achieve_override )
        $output .= '
                <li><a href="char_achieve.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "achievements").'</a></li>';

      $output .= '
                <li class="selected"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "quests").'</a></li>';

      if ( $view_friends_override )
        $output .= '
                <li><a href="char_friends.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "friends").'</a></li>';

      if ( $view_view_override )
        $output .= '
                <li><a href="char_view.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "view").'</a></li>';

      $output .= '
              </ul>
            </div>
            <div class="tab_content center center_text">
              <span class="bold">
                '.htmlentities($char["name"], ENT_COMPAT, $site_encoding).' -
                <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" /> - '.lang("char", "level_short").char_get_level_color($char["level"]).'
              </span>
              <br />
              <br />
              <table class="lined" id="ch_que_questsA">
                <tr>
                  <th style="width: 10%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=0&amp;dir='.$dir.'"'.( ( $order_by == 0 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "quest_id").'</a></th>
                  <th style="width: 7%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=1&amp;dir='.$dir.'"'.( ( $order_by == 1 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "quest_level").'</a></th>
                  <th style="width: 78%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=2&amp;dir='.$dir.'"'.( ( $order_by == 2 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "quest_title").'</a></th>
                  <th style="width: 5%;"><img src="img/aff_qst.png" width="14" height="14" alt="" /></th>
                </tr>';

      if ( $core == 1 )
        $result = $sql["char"]->query("SELECT quest_id, completed
                                       FROM questlog
                                       WHERE player_guid='".$id."'");
      elseif ( $core == 2 )
        $result = $sql["char"]->query("SELECT quest AS quest_id, status AS completed
                                      FROM character_queststatus
                                      WHERE guid='".$id."' AND rewarded=0 AND status<>0");
      else
        $result = $sql["char"]->query("SELECT quest AS quest_id, status AS completed
                                      FROM character_queststatus
                                      WHERE guid='".$id."' AND status<>0");

      $quests_1 = array();
      $quests_3 = array();

      if ( $sql["char"]->num_rows($result) )
      {
        while ( $quest = $sql["char"]->fetch_assoc($result) )
        {
          $deplang = get_lang_id();

          if ( $core == 1 )
            $query1 = $sql["char"]->query("SELECT *, questlevel, Title AS Title1 FROM `".$world_db[$realmid]['name']."`.quests "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN `".$world_db[$realmid]['name']."`.quests_localized ON (quests_localized.entry=quests.entry AND language_code='".$locales_search_option."' ) " : " " ).
                "WHERE quests.entry='".$quest["quest_id"]."'");
          elseif ( $core == 2 )
            $query1 = $sql["char"]->query("SELECT *, QuestLevel AS questlevel, Title AS Title1 FROM `".$world_db[$realmid]['name']."`.quest_template "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN `".$world_db[$realmid]['name']."`.locales_quest ON locales_quest.entry=quest_template.entry " : " " ).
                "WHERE quest_template.entry='".$quest["quest_id"]."'");
          else
            $query1 = $sql["char"]->query("SELECT *, Level AS questlevel, Title AS Title1 FROM `".$world_db[$realmid]['name']."`.quest_template "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN `".$world_db[$realmid]['name']."`.locales_quest ON locales_quest.entry=quest_template.Id " : " " ).
                "WHERE quest_template.Id='".$quest["quest_id"]."'");

          $quest_info = $sql["char"]->fetch_assoc($query1);

          // Localization
          if ( $locales_search_option == 0 )
            $quest_info["title"] = $quest_info["Title1"];
          else
          {
            if ( $core == 1 )
              $quest_info["title"] = $quest_info["Title"];
            else
              $quest_info["title"] = $quest_info["Title_loc".$locales_search_option];
          }

          if ( $quest["completed"] == 1 )
            array_push($quests_1, array($quest["quest_id"], $quest_info["questlevel"], $quest_info["title"], $quest["rewarded"]));
          else
            array_push($quests_3, array($quest["quest_id"], $quest_info["questlevel"], $quest_info["title"]));
        }
        unset($quest);
        unset($quest_info);
        aasort($quests_1, $order_by, $dir);
        $orderby = $order_by;
        if ( $orderby > 2 )
          $orderby = 1;
        aasort($quests_3, $orderby, $dir);
        $all_record = count($quests_1);

        foreach ( $quests_3 as $data )
        {
          $output .= '
                <tr>
                  <td>'.$data[0].'</td>
                  <td>('.$data[1].')</td>
                  <td align="left"><a href="'.$base_datasite.$quest_datasite.$data[0].'" rel="external">'.htmlentities($data[2], ENT_COMPAT, $site_encoding).'</a></td>
                  <td><img src="img/aff_qst.png" width="14" height="14" alt="" /></td>
                </tr>';
        }
        unset($quest_3);
        if ( count($quests_1) )
        {
          $output .= '
              </table>
              <table class="hidden" id="ch_que_questsB">
                <tr align="right">
                  <td>';
          $output .= generate_pagination('char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by='.$order_by.'&amp;dir='.( ( $dir ) ? 0 : 1 ), $all_record, $itemperpage, $start);
          $output .= '
                  </td>
                </tr>
              </table>
              <table class="lined" id="ch_que_questsC">
                <tr>
                  <th style="width: 10%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=0&amp;dir='.$dir.'"'.( ( $order_by == 0 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "quest_id").'</a></th>
                  <th style="width: 7%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=1&amp;dir='.$dir.'"'.( ( $order_by == 1 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "quest_level").'</a></th>
                  <th style="width: 68%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=2&amp;dir='.$dir.'"'.( ( $order_by == 2 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "quest_title").'</a></th>
                  <th style="width: 10%;"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=3&amp;dir='.$dir.'"'.( ( $order_by == 3 ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("char", "rewarded").'</a></th>
                  <th style="width: 5%;"><img src="img/aff_tick.png" width="14" height="14" alt="" /></th>
                </tr>';
          $i = 0;
          foreach ( $quests_1 as $data )
          {
            if ( ( $i < ($start+$itemperpage) ) && ( $i >= $start ) )
            {
              $output .= '
                <tr>
                  <td>'.$data[0].'</td>
                  <td>('.$data[1].')</td>
                  <td align="left"><a href="'.$base_datasite.$quest_datasite.$data[0].'" rel="external">'.htmlentities($data[2], ENT_COMPAT, $site_encoding).'</a></td>
                  <td><img src="img/aff_'.( ( $data[3] ) ? 'tick' : 'qst' ).'.png" width="14" height="14" alt="" /></td>
                  <td><img src="img/aff_tick.png" width="14" height="14" alt="" /></td>
                </tr>';
            }
            $i++;
          }
          unset($data);
          unset($quest_1);
          $output .= '
                <tr align="right">
                  <td colspan="5">';
          $output .= generate_pagination('char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by='.$order_by.'&amp;dir='.( ( $dir ) ? 0 : 1 ), $all_record, $itemperpage, $start);
          $output .= '
                  </td>
                </tr>';
        }
      }
      else
        $output .= '
                <tr>
                  <td colspan="4"><p>'.lang("char", "no_act_quests").'</p></td>
                </tr>';
      //---------------Page Specific Data Ends here----------------------------
      //---------------Character Tabs Footer-----------------------------------
      $output .= '
              </table>
            </div>
            <br />
            <table class="hidden center">
              <tr>
                <td>';
      // button to user account page, user account page has own security
      makebutton(lang("char", "chars_acc"), 'user.php?action=edit_user&amp;id='.$owner_acc_id.'', 130);
      $output .= '
                </td>
                <td>';

      // only higher level GM with delete access can edit character
      //  character edit allows removal of character items, so delete permission is needed
      if ( ( $user_lvl > $owner_gmlvl ) && ( $user_lvl >= $action_permission["delete"] ) )
      {
                  //makebutton($lang_char["edit_button"], 'char_edit.php?id='.$id.'&amp;realm='.$realmid.'', 130);
        $output .= '
                </td>
                <td>';
      }
      // only higher level GM with delete access, or character owner can delete character
      if ( ( ( $user_lvl > $owner_gmlvl ) && ( $user_lvl >= $action_permission["delete"] ) ) || ( $owner_name === $user_name ) )
      {
        makebutton(lang("char", "del_char"), 'char_list.php?action=del_char_form&amp;check%5B%5D='.$id.'" type="wrn', 130);
        $output .= '
                </td>
                <td>';
      }
      // only GM with update permission can send mail, mail can send items, so update permission is needed
      if ( $user_lvl >= $action_permission["update"] )
      {
        makebutton(lang("char", "send_mail"), 'mail.php?type=ingame_mail&amp;to='.$char["name"].'', 130);
        $output .= '
                </td>
                <td>';
      }
      makebutton(lang("global", "back"), 'javascript:window.history.back()" type="def', 130);
      $output .= '
                </td>
              </tr>
            </table>
            <br />
          <!-- end of char_quest.php -->';
    }
    else
      error(lang("char", "no_permission"));
  }
  else
    error(lang("char", "no_char_found"));

}


//########################################################################################################################
// MAIN
//########################################################################################################################

//$action = (isset($_GET["action"])) ? $_GET["action"] : NULL;

$output .= '
      <div class="bubble">';

char_quest();

unset($action_permission);

require_once "footer.php";


?>
