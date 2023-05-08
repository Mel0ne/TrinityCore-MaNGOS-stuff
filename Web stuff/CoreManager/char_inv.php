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


// page header, and any additional required libraries
require_once "header.php";
require_once "libs/char_lib.php";
require_once "libs/item_lib.php";

// minimum permission to view page
valid_login($action_permission["view"]);

//#############################################################################
// SHOW INV. AND BANK ITEMS
//#############################################################################
function char_inv()
{
  global $output, $realm_id, $characters_db, $world_db, $corem_db, $site_encoding,
    $action_permission, $user_lvl, $user_name, $locales_search_option, $base_datasite,
    $item_datasite, $sql, $core;

  // this page uses wowhead tooltops
  //wowhead_tt();

  $cid = $_GET["id"];
  
  // we need at least an id or we would have nothing to show
  // also, make sure id is numeric to prevent SQL injection
  if ( ( empty($_GET["id"]) ) || ( !is_numeric($cid) ) )
    error(lang("global", "empty_fields"));

  // this is multi realm support, as of writing still under development
  //  this page is already implementing it
  if ( empty($_GET["realm"]) )
    $realmid = $realm_id;
  else
  {
    $realmid = $sql["logon"]->quote_smart($_GET["realm"]);
    if ( is_numeric($realmid) )
      $sql["char"]->connect($characters_db[$realmid]["addr"], $characters_db[$realmid]["user"], $characters_db[$realmid]["pass"], $characters_db[$realmid]["name"], $characters_db[$realmid]["encoding"]);
    else
      $realmid = $realm_id;
  }

  //-------------------SQL Injection Prevention--------------------------------
  // no point going further if we don have a valid ID
  // this_is_junk: char.php doesn't post account.   Why is this even here?
  //$acct = $sql["char"]->quote_smart($_GET["acct"]);
  //if (is_numeric($acct));
  //else error($lang_global["empty_fields"]);

  // getting character data from database
  if ( $core == 1 )
    $result = $sql["char"]->query("SELECT acct, name, race, class, level, gender, gold, online
      FROM characters WHERE guid='".$cid."' LIMIT 1");
  else
    $result = $sql["char"]->query("SELECT account AS acct, name, race, class, level, gender, money AS gold, online
      FROM characters WHERE guid='".$cid."' LIMIT 1");

  // no point going further if character does not exist
  if ( $sql["char"]->num_rows($result) )
  {
    $char = $sql["char"]->fetch_assoc($result);

    // we get user permissions first
    $owner_acc_id = $sql["char"]->result($result, 0, "acct");
    if ( $core == 1 )
      $query = $sql["logon"]->query("SELECT login FROM accounts WHERE acct='".$owner_acc_id."'");
    else
      $query = $sql["logon"]->query("SELECT username as login FROM account WHERE id='".$owner_acc_id."'");
    $owner_name = $sql["logon"]->result($query, 0, "login");

    $s_query = "SELECT *, SecurityLevel AS gm FROM config_accounts WHERE Login='".$owner_name."'";
    $s_result = $sql["mgr"]->query($s_query);
    $s_fields = $sql["mgr"]->fetch_assoc($s_result);
    $owner_gmlvl = $s_fields["gm"];
    $view_mod = $s_fields["View_Mod_Inv"];

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

    $view_quest_override = false;
    if ( $s_fields["View_Mod_Quest"] > 0 )
    {
      if ( $s_fields["View_Mod_Quest"] == 1 )
        ;// TODO: Add friends limit
      elseif ( $s_fields["View_Mod_Quest"] == 2 )
      {
        // only registered users may view this tab
        if ( $user_lvl > -1 )
          $view_quest_override = true;
      }
    }
    else
    {
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
        $view_quest_override = true;
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

    // find out what mode we're in View or Delete (0 = View, 1 = Delete)
    $mode = ( ( isset($_GET["mode"]) ) ? $_GET["mode"] : 0 );
    // only the character's owner or a GM with Delete privs can enter Delete Mode
    if ( $owner_name != $user_name )
      if ( $user_lvl < $action_permission["delete"] )
        $mode = 0;
    else
      $mode = $mode;

    // View Mode is only availble on characters that are offline
    if ( $char["online"] != 0 )
      $mode = 0;

    // check user permission
    if ( ( $view_override ) || ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
    {
      // main data that we need for this page, character inventory
      if ( $core == 1 )
        $result = $sql["char"]->query("SELECT 
          containerslot, slot, entry, enchantments AS enchantment, randomprop AS property, count, flags
          FROM playeritems WHERE ownerguid='".$cid."' ORDER BY containerslot, slot");
      elseif ( $core == 2 )
        $result = $sql["char"]->query("SELECT 
          bag, slot, item_template AS entry, item, 
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 11), ' ', -1) AS creator,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 23), ' ', -1) AS enchantment, 
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 60), ' ', -1) AS property, 
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 15), ' ', -1) AS count,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 62), ' ', -1) AS durability,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 22), ' ', -1) AS flags
          FROM character_inventory LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
          WHERE character_inventory.guid='".$cid."' ORDER BY bag, slot");
      else
        $result = $sql["char"]->query("SELECT 
          bag, slot, itemEntry AS entry, item, 
          creatorGuid AS creator,
          enchantments AS enchantment, 
          randomPropertyId AS property, 
          count, durability, flags
          FROM character_inventory 
            LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
          WHERE character_inventory.guid='".$cid."' ORDER BY bag, slot");

      //---------------Page Specific Data Starts Here--------------------------
      // lets start processing first before we display anything
      //  we have lots to do for inventory

      // character bags, 1 main + 4 additional
      $bag = array
      (
        0 => array(),
        1 => array(),
        2 => array(),
        3 => array(),
        4 => array()
      );

      // character bank, 1 main + 7 additional
      $bank = array
      (
        0 => array(),
        1 => array(),
        2 => array(),
        3 => array(),
        4 => array(),
        5 => array(),
        6 => array(),
        7 => array()
      );

      // this is where we will put items that are in main bag
      $bag_id = array();
      // this is where we will put items that are in main bank
      $bank_bag_id = array();
      // this is where we will put items that are in character bags, 4 arrays, 1 for each
      $equiped_bag_id = array(0, 0, 0, 0, 0);
      // this is where we will put items that are in bank bangs, 7 arrays, 1 for each
      $equip_bnk_bag_id = array(0, 0, 0, 0, 0, 0, 0, 0);
      // we load the things in each bag slot
      while ( $slot = $sql["char"]->fetch_assoc($result) )
      {
        if ( $core == 1 )
        {
          if ( ( $slot["containerslot"] == -1 ) && ( $slot["slot"] > 18 ) )
          {
            if ( $slot["slot"] < 23 ) // SLOT 19 TO 22 (Bags)
            {
              $bag_id[$slot["slot"]] = ($slot["slot"]-18);
              $equiped_bag_id[$slot["slot"]-18] = array($slot["entry"],
                $sql["world"]->result($sql["world"]->query("SELECT containerslots FROM items
                  WHERE entry='".$slot["entry"]."'"), 0, "containerslots"), $slot["count"]);
            }
            elseif ( $slot["slot"] < 39 ) // SLOT 23 TO 38 (BackPack)
            {
              $i_query = "SELECT 
                *, description AS description1, name1 AS name, quality AS Quality, inventorytype AS InventoryType, 
                socket_color_1 AS socketColor_1, socket_color_2 AS socketColor_2, socket_color_3 AS socketColor_3,
                requiredlevel AS RequiredLevel, allowableclass AS AllowableClass,
                sellprice AS SellPrice, itemlevel AS ItemLevel
                FROM items "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                "WHERE items.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              if ( isset($bag[0][$slot["slot"]-23]) )
                $bag[0][$slot["slot"]-23][0]++;
              else
                $bag[0][$slot["slot"]-23] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
            elseif ( $slot["slot"] < 67 ) // SLOT 39 TO 66 (Bank)
            {
              $i_query = "SELECT
                *, description AS description1, name1 AS name, quality AS Quality, inventorytype AS InventoryType, 
                socket_color_1 AS socketColor_1, socket_color_2 AS socketColor_2, socket_color_3 AS socketColor_3,
                requiredlevel AS RequiredLevel, allowableclass AS AllowableClass,
                sellprice AS SellPrice, itemlevel AS ItemLevel
                FROM items "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                "WHERE items.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              $bank[0][$slot["slot"]-39] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
            elseif ( $slot["slot"] < 74 ) // SLOT 67 TO 73 (Bank Bags)
            {
              $bank_bag_id[$slot["slot"]] = ($slot["slot"]-66);
              $equip_bnk_bag_id[$slot["slot"]-66] = array($slot["entry"], 
                $sql["world"]->result($sql["world"]->query("SELECT containerslots FROM items
                  WHERE entry='".$slot["entry"]."'"), 0, "containerslots"), $slot["count"]);
            }
          }
          else
          {
            // Bags
            if ( isset($bag_id[$slot["containerslot"]]) )
            {
              $i_query = "SELECT
                *, description AS description1, name1 AS name, quality AS Quality, inventorytype AS InventoryType, 
                socket_color_1 AS socketColor_1, socket_color_2 AS socketColor_2, socket_color_3 AS socketColor_3,
                requiredlevel AS RequiredLevel, allowableclass AS AllowableClass,
                sellprice AS SellPrice, itemlevel AS ItemLevel
                FROM items "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                "WHERE items.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              if ( isset($bag[$bag_id[$slot["containerslot"]]][$slot["slot"]]) )
                $bag[$bag_id[$slot["containerslot"]]][$slot["slot"]][1]++;
              else
                $bag[$bag_id[$slot["containerslot"]]][$slot["slot"]] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
            // Bank Bags
            elseif ( isset($bank_bag_id[$slot["containerslot"]]) )
            {
              $i_query = "SELECT
                *, description AS description1, name1 AS name, quality AS Quality, inventorytype AS InventoryType, 
                socket_color_1 AS socketColor_1, socket_color_2 AS socketColor_2, socket_color_3 AS socketColor_3,
                requiredlevel AS RequiredLevel, allowableclass AS AllowableClass,
                sellprice AS SellPrice, itemlevel AS ItemLevel
                FROM items "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
                "WHERE items.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              $bank[$bank_bag_id[$slot["containerslot"]]][$slot["slot"]] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
          }
        }
        else
        {
          if ( ( $slot["bag"] == 0 ) && ( $slot["slot"] > 18 ) )
          {
            if ( $slot["slot"] < 23 ) // SLOT 19 TO 22 (Bags)
            {
              $bag_id[$slot["item"]] = ($slot["slot"]-18);
              $equiped_bag_id[$slot["slot"]-18] = array($slot["entry"],
                $sql["world"]->result($sql["world"]->query("SELECT ContainerSlots FROM item_template
                  WHERE entry='".$slot["entry"]."'"), 0, "containerslots"), $slot["count"]);
            }
            elseif ( $slot["slot"] < 39 ) // SLOT 23 TO 38 (BackPack)
            {
              $i_query = "SELECT *, description AS description1 FROM item_template "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
                "WHERE item_template.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              if ( isset($bag[0][$slot["slot"]-23]) )
                $bag[0][$slot["slot"]-23][0]++;
              else
                $bag[0][$slot["slot"]-23] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
            elseif ( $slot["slot"] < 67 ) // SLOT 39 TO 66 (Bank)
            {
              $i_query = "SELECT *, description AS description1 FROM item_template "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
                "WHERE item_template.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              $bank[0][$slot["slot"]-39] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
            elseif ( $slot["slot"] < 74 ) // SLOT 67 TO 73 (Bank Bags)
            {
              $bank_bag_id[$slot["item"]] = ($slot["slot"]-66);
              $equip_bnk_bag_id[$slot["slot"]-66] = array($slot["entry"], 
                $sql["world"]->result($sql["world"]->query('SELECT ContainerSlots FROM item_template
                  WHERE entry = '.$slot["entry"].''), 0, "ContainerSlots"), $slot["count"]);
            }
          }
          else
          {
            // Bags
            if ( isset($bag_id[$slot["bag"]]) )
            {
              $i_query = "SELECT *, description AS description1 FROM item_template "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
                "WHERE item_template.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              if ( isset($bag[$bag_id[$slot["bag"]]][$slot["slot"]]) )
                $bag[$bag_id[$slot["bag"]]][$slot["slot"]][1]++;
              else
                $bag[$bag_id[$slot["bag"]]][$slot["slot"]] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
            // Bank Bags
            elseif ( isset($bank_bag_id[$slot["bag"]]) )
            {
              $i_query = "SELECT *, description AS description1 FROM item_template "
                  .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
                "WHERE item_template.entry='".$slot["entry"]."'";

              $i_result = $sql["world"]->query($i_query);
              $i = $sql["world"]->fetch_assoc($i_result);

              $bank[$bank_bag_id[$slot["bag"]]][$slot["slot"]] = array($slot["entry"], 0, $slot["count"], $i, $slot["enchantment"], $slot["property"], $slot["creator"], $slot["durability"], $slot["flags"], $slot["bag"], $slot["slot"]);
            }
          }
        }
      }
      unset($slot);
      unset($bag_id);
      unset($bank_bag_id);
      unset($result);

      //------------------------Character Tabs---------------------------------
      // we start with a lead of 10 spaces,
      //  because last line of header is an opening tag with 8 spaces
      //  keep html indent in sync, so debuging from browser source would be easy to read
      $output .= '
          <!-- start of char_inv.php -->
            <div class="tab">
              <ul>
                <li><a href="char.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "char_sheet").'</a></li>';

      $output .= '
                <li class="selected"><a href="char_inv.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "inventory").'</a></li>';

      if ( $view_talent_override )
        $output .= '
                '.(($char["level"] < 10) ? '' : '<li><a href="char_talent.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "talents").'</a></li>').'';

      if ( $view_achieve_override )
        $output .= '
                <li><a href="char_achieve.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "achievements").'</a></li>';

      if ( $view_quest_override )
        $output .= '
                <li><a href="char_quest.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "quests").'</a></li>';

      if ( $view_friends_override )
        $output .= '
                <li><a href="char_friends.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "friends").'</a></li>';

      if ( $view_view_override )
        $output .= '
                <li><a href="char_view.php?id='.$cid.'&amp;realm='.$realmid.'">'.lang("char", "view").'</a></li>';

      $output .= '
              </ul>
            </div>
            <div class="tab_content center" id="ch_inv_bags_wrap">
              <span class="bold">
                '.htmlentities($char["name"], ENT_COMPAT, $site_encoding).' -
                <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif"
                  onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                <img src="img/c_icons/'.$char["class"].'.gif"
                  onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" /> - '.lang("char", "level_short").char_get_level_color($char["level"]).'
              </span>
              <br />
              <br />
              <table class="lined" id="ch_inv_bags">
                <tr>';

      //---------------Page Specific Data Starts Here--------------------------

      // equipped bags
      for ( $i = 4; $i > 0; --$i )
      {
        $output .= '
                  <th>';
        if ( $equiped_bag_id[$i] )
        {
          $output .= '
                    <a href="'.$base_datasite.$item_datasite.$equiped_bag_id[$i][0].'" rel="external">
                      <img class="bag_icon" src="'.get_item_icon($equiped_bag_id[$i][0]).'" alt="" />
                    </a>
                    '.lang("item", "bag").' '.$i.'<br />
                    <span class="small">'.$equiped_bag_id[$i][1].' '.lang("item", "slots").'</span>';
        }
        $output .= '
                  </th>';
      }
      $output .= '
                </tr>
                <tr>';

      // equipped bag slots
      for ( $t = 4; $t > 0; --$t )
      {
        // this_is_junk: style left hardcoded because it's calculated.
        $output .= '
                  <td align="center">
                    <div class="bag" style="width: '.(4*43).'px; height: '.(ceil($equiped_bag_id[$t][1]/4)*41).'px;">';
        $dsp = $equiped_bag_id[$t][1]%4;

        if ( $dsp )
          $output .= '
                      <div class="no_slot"></div>';

        foreach ( $bag[$t] as $pos => $item )
        {
          // this_is_junk: style left hardcoded because it's calculated.
          $item[2] = ( ( $item[2] == 1 ) ? '' : $item[2] );
          $output .= '
                      <div class="bag_slot" style="left: '.((($pos+$dsp)%4*43)+4).'px; top: '.((floor(($pos+$dsp)/4)*41)+4).'px;">
                        <a href="'.$base_datasite.$item_datasite.$item[0].'" rel="external" onmouseover="ShowTooltip(this,\'_b'.$t.'p'.$pos.(($pos+$dsp)%4*42).'x'.(floor(($pos+$dsp)/4)*41).'\');" onmouseout="HideTooltip(\'_b'.$t.'p'.$pos.(($pos+$dsp)%4*42).'x'.(floor(($pos+$dsp)/4)*41).'\');">
                          <img src="'.get_item_icon($item[0]).'" alt="" class="inv_icon" />
                        </a>';
          if ( $mode )
            $output .= '
                        <div>
                          <a href="char_inv.php?action=delete_item&amp;id='.$cid.'&amp;bag='.$item[9].'&amp;slot='.$item[10].'&amp;item='.$item[0].'&amp;mode='.$mode.'">
                            <img src="img/aff_cross.png" class="ch_inv_delete" alt="" />
                          </a>
                        </div>';
          else
            $output .= '
                        <div class="ch_inv_quantity_shadow">'.$item[2].'</div>
                        <div class="ch_inv_quantity">'.$item[2].'</div>';
          $output .= '
                      </div>';
          // build a tooltip object for this item
          $output .= '
                      <div class="item_tooltip" id="tooltip_b'.$t.'p'.$pos.(($pos+$dsp)%4*42).'x'.(floor(($pos+$dsp)/4)*41).'" style="left: '.((($pos+$dsp)%4*42)-129).'px; top: '.((floor(($pos+$dsp)/4)*41)+42).'px;">
                        <table>
                          <tr>
                            <td>'.get_item_tooltip($item[3], $item[4], $item[5], $item[6], $item[7], $item[8]).'</td>
                          </tr>
                        </table>
                      </div>';
        }
        $output .= '
                    </div>
                  </td>';
      }
      unset($equiped_bag_id);
      // this_is_junk: style left hardcoded because it's calculated.
      $output .= '
                </tr>
                <tr>
                  <th colspan="2" align="left">
                    <img class="bag_icon" src="'.get_item_icon(3960).'" alt="" id="ch_backpack_icon_margin" />
                    <span id="ch_backpack_name_margin">'.lang("char", "backpack").'</span>
                  </th>
                  <th colspan="2">
                    '.lang("char", "bank_items").'
                  </th>
                </tr>
                <tr>
                  <td colspan="2" style="height: 220px; text-align: center;">
                    <div class="bag" id="ch_backpack" style="width: '.(4*43).'px; height: '.(ceil(16/4)*41).'px;">';

      // inventory items
      foreach ( $bag[0] as $pos => $item )
      {
        // this_is_junk: style left hardcoded because it's calculated.
        $item[2] = ( ( $item[2] == 1 ) ? '' : $item[2] );
        $output .= '
                      <div class="bag_slot" style="left: '.(($pos%4*43)+4).'px; top: '.((floor($pos/4)*41)+4).'px;">
                        <a href="'.$base_datasite.$item_datasite.$item[0].'" rel="external" onmouseover="ShowTooltip(this,\'_b'.$t.'p'.$pos.($pos%4*42).'x'.(floor($pos/4)*41).'\');" onmouseout="HideTooltip(\'_b'.$t.'p'.$pos.($pos%4*42).'x'.(floor($pos/4)*41).'\');">
                          <img src="'.get_item_icon($item[0]).'" class="inv_icon" alt="" />
                        </a>';
          if ( $mode )
            $output .= '
                        <div>
                          <a href="char_inv.php?action=delete_item&amp;id='.$cid.'&amp;bag='.$item[9].'&amp;slot='.$item[10].'&amp;item='.$item[0].'&amp;mode='.$mode.'">
                            <img src="img/aff_cross.png" class="ch_inv_delete" alt="" />
                          </a>
                        </div>';
          else
            $output .= '
                        <div class="ch_inv_quantity_shadow">'.$item[2].'</div>
                        <div class="ch_inv_quantity">'.$item[2].'</div>';
          $output .= '
                      </div>';
        // build a tooltip object for this item
        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.$t.'p'.$pos.($pos%4*42).'x'.(floor($pos/4)*41).'" style="left: '.(($pos%4*42)-129).'px; top: '.((floor($pos/4)*41)+42).'px;">
                        <table>
                          <tr>
                            <td>'.get_item_tooltip($item[3], $item[4], $item[5], $item[6], $item[7], $item[8]).'</td>
                          </tr>
                        </table>
                      </div>';
      }
      unset($bag);
      $output .= '
                    </div>
                    <div id="ch_money">
                      <b>
                        '.substr($char["gold"],  0, -4).'<img src="img/gold.gif" alt="gold" style="position: relative; bottom: -6px;" />
                        '.substr($char["gold"], -4,  2).'<img src="img/silver.gif" alt="silver" style="position: relative; bottom: -6px;" />
                        '.substr($char["gold"], -2).'<img src="img/copper.gif" alt="copper" style="position: relative; bottom: -6px;" />
                      </b>
                    </div>
                  </td>
                  <td colspan="2" align="center">
                    <div class="bag bank" style="width: '.((7*43)+2).'px; height: '.(ceil(24/7)*41).'px;">';

      // bank items
      foreach ( $bank[0] as $pos => $item )
      {
        // this_is_junk: style left hardcoded because it's calculated.
        $item[2] = ( ( $item[2] == 1 ) ? '' : $item[2] );
        $output .= '
                      <div class="bag_slot" style="left: '.(($pos%7*43)+4).'px; top: '.((floor($pos/7)*41)+4).'px;">
                        <a href="'.$base_datasite.$item_datasite.$item[0].'" rel="external" onmouseover="ShowTooltip(this,\'_bbp'.$pos.($pos%7*43).'x'.(floor($pos/7)*41).'\');" onmouseout="HideTooltip(\'_bbp'.$pos.($pos%7*43).'x'.(floor($pos/7)*41).'\');">
                          <img src="'.get_item_icon($item[0]).'" class="inv_icon" alt="" />
                        </a>';
          if ( $mode )
            $output .= '
                        <div>
                          <a href="char_inv.php?action=delete_item&amp;id='.$cid.'&amp;bag='.$item[9].'&amp;slot='.$item[10].'&amp;item='.$item[0].'&amp;mode='.$mode.'">
                            <img src="img/aff_cross.png" class="ch_inv_delete" alt="" />
                          </a>
                        </div>';
          else
            $output .= '
                        <div class="ch_inv_quantity_shadow">'.$item[2].'</div>
                        <div class="ch_inv_quantity">'.$item[2].'</div>';
           $output .= '
                      </div>';
        // build a tooltip object for this item
        $output .= '
                      <div class="item_tooltip" id="tooltip_bbp'.$pos.($pos%7*43).'x'.(floor($pos/7)*41).'" style="left: '.(($pos%7*43)-129).'px; top: '.((floor($pos/7)*41)+42).'px;">
                        <table>
                          <tr>
                            <td>'.get_item_tooltip($item[3], $item[4], $item[5], $item[6], $item[7], $item[8]).'</td>
                          </tr>
                        </table>
                      </div>';
      }
      $output .= '
                    </div>
                  </td>
                </tr>
                <tr>';

      // equipped bank bags, first 4
      for ( $i = 1; $i < 5; ++$i )
      {
        $output .= '
                  <th>';
        if ( $equip_bnk_bag_id[$i] )
        {
          $output .= '
                    <a href="'.$base_datasite.$item_datasite.$equip_bnk_bag_id[$i][0].'" rel="external">
                      <img class="bag_icon" src="'.get_item_icon($equip_bnk_bag_id[$i][0]).'" alt="" />
                    </a>
                    '.lang("item", "bag").' '.$i.'<br />
                    <span class="small">'.$equip_bnk_bag_id[$i][1].' '.lang("item", "slots").'</span>';
        }
        $output .= '
                  </th>';
      }
      $output .= '
                </tr>
                <tr>';

      // equipped bank bag slots
      for ( $t = 1; $t < 8; ++$t )
      {
        // equipped bank bags, last 3
        if ( $t === 5 )
        {
          $output .= '
                </tr>
                <tr>';
          for ( $i = 5; $i < 8; ++$i )
          {
            $output .= '
                  <th>';
            if ( $equip_bnk_bag_id[$i] )
            {
              $output .= '
                    <a href="'.$base_datasite.$item_datasite.$equip_bnk_bag_id[$i][0].'" rel="external">
                      <img class="bag_icon" src="'.get_item_icon($equip_bnk_bag_id[$i][0]).'" alt="" />
                    </a>
                    '.lang("item", "bag").' '.$i.'<br />
                    <span class="small">'.$equip_bnk_bag_id[$i][1].' '.lang("item", "slots").'</span>';
            }
            $output .= '
                  </th>';
          }
          $output .= '
                  <th>
                  </th>
                </tr>
                <tr>';
        }
        // this_is_junk: style left hardcoded because it's calculated.
        $output .= '
                  <td align="center">
                    <div class="bag bank" style="width: '.((4*43)+2).'px; height: '.(ceil($equip_bnk_bag_id[$t][1]/4)*41).'px;">';
        $dsp = $equip_bnk_bag_id[$t][1]%4;
        if ( $dsp )
          $output .= '
                      <div class="no_slot"></div>';
        foreach ( $bank[$t] as $pos => $item )
        {
          // this_is_junk: style left hardcoded because it's calculated.
          $item[2] = ( ( $item[2] == 1 ) ? '' : $item[2] );
          $output .= '
                      <div class="bag_slot" style="left: '.((($pos+$dsp)%4*43)+4).'px; top: '.((floor(($pos+$dsp)/4)*41)+4).'px;">
                        <a href="'.$base_datasite.$item_datasite.$item[0].'" rel="external" onmouseover="ShowTooltip(this,\'_bb'.$t.'p'.$pos.(($pos+$dsp)%4*43).'x'.(floor(($pos+$dsp)/4)*41).'\');" onmouseout="HideTooltip(\'_bb'.$t.'p'.$pos.(($pos+$dsp)%4*43).'x'.(floor(($pos+$dsp)/4)*41).'\');">
                          <img src="'.get_item_icon($item[0]).'" class="inv_icon" alt="" />
                        </a>';
          if ( $mode )
            $output .= '
                        <div>
                          <a href="char_inv.php?action=delete_item&amp;id='.$cid.'&amp;bag='.$item[9].'&amp;slot='.$item[10].'&amp;item='.$item[0].'&amp;mode='.$mode.'">
                            <img src="img/aff_cross.png" class="ch_inv_delete" alt="" />
                          </a>
                        </div>';
          else
            $output .= '
                        <div class="ch_inv_quantity_shadow">'.$item[2].'</div>
                        <div class="ch_inv_quantity">'.$item[2].'</div>';
          $output .= '
                      </div>';
                      // build a tooltip object for this item
                      $output .= '
                      <div class="item_tooltip" id="tooltip_bb'.$t.'p'.$pos.(($pos+$dsp)%4*43).'x'.(floor(($pos+$dsp)/4)*41).'" style="left: '.((($pos+$dsp)%4*43)-129).'px; top: '.((floor(($pos+$dsp)/4)*41)+42).'px;">
                        <table>
                          <tr>
                            <td>'.get_item_tooltip($item[3], $item[4], $item[5], $item[6], $item[7], $item[8]).'</td>
                          </tr>
                        </table>
                      </div>';
        }

        $output .= '
                    </div>
                  </td>';
      }
      unset($equip_bnk_bag_id);
      unset($bank);
      $output .= '
                  <td><div class="bag bank"></div></td>';
      //---------------Page Specific Data Ends here----------------------------
      //---------------Character Tabs Footer-----------------------------------
      $output .= '
                </tr>
              </table>
            </div>
            <br />
            <table class="hidden">
              <tr>
                <td>';
      // button to user account page, user account page has own security
      makebutton(lang("char", "chars_acc"), 'user.php?action=edit_user&amp;id='.$owner_acc_id.'', 130);
      $output .= '
                </td>
                <td>';

      // show Delete Mode / View Mode button depending on current mode
      if ( $mode )
        makebutton(lang("char", "viewmode"), 'char_inv.php?id='.$cid.'&amp;realm='.$realmid.'&amp;mode=0" type="def', 130);
      else
        makebutton(lang("char", "deletemode"), 'char_inv.php?id='.$cid.'&amp;realm='.$realmid.'&amp;mode=1" type="def', 130);
      $output .= '
                </td>
                <td>';
      // only higher level GM with delete access can edit character
      //  character edit allows removal of character items, so delete permission is needed
      if ( ( $user_lvl > $owner_gmlvl ) && ( $user_lvl >= $action_permission["delete"] ) )
      {
                  //makebutton($lang_char["edit_button"], 'char_edit.php?id='.$cid.'&amp;realm='.$realmid.'', 130);
        $output .= '
                </td>
                <td>';
      }
      // only higher level GM with delete access, or character owner can delete character
      if ( ( ($user_lvl > $owner_gmlvl) && ($user_lvl >= $action_permission["delete"]) ) || ($owner_name === $user_name) )
      {
        makebutton(lang("char", "del_char"), 'char_list.php?action=del_char_form&amp;check%5B%5D='.$cid.'" type="wrn', 130);
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
          <!-- end of char_inv.php -->';
    }
    else
      error(lang("char", "no_permission"));
  }
  else
    error(lang("char", "no_char_found"));

}


//#############################################################################
// DELETE ITEM FORM
//#############################################################################
function delete_item()
{
  global $output, $action_permission;

  valid_login($action_permission["delete"]);

  $output .= '
          <div>
            <img src="img/warn_red.gif" width="48" height="48" alt="" />
              <h1>
                <span class="error">'.lang("global", "are_you_sure").'</span>
              </h1>
              <br />
              <span class="bold">'.
                lang("char", "thisitem").'
                <br />'.
                lang("global", "will_be_erased").'
              </span>
              <br /><br />
              <table width="300" class="hidden">
                <tr>
                  <td>';
  makebutton(lang("global", "yes"), 'char_inv.php?action=dodelete_item&amp;id='.$_GET["id"].'&amp;bag='.$_GET["bag"].'&amp;slot='.$_GET["slot"].'&amp;item='.$_GET["item"].'&amp;mode='.$_GET["mode"].'" type="wrn', 130);
  makebutton(lang("global", "no"), 'char_inv.php?id='.$_GET["id"].'&amp;mode='.$_GET["mode"].'" type="def', 130);
  $output .= '
                  </td>
                </tr>
              </table>
            </div>';
}


//#############################################################################
// DELETE ITEM
//#############################################################################
function dodelete_item()
{
  global $output, $action_permission, $sql, $core;

  valid_login($action_permission["delete"]);

  // get our variables
  $cid = $sql["char"]->quote_smart($_GET["id"]);
  $bag = $sql["char"]->quote_smart($_GET["bag"]);
  $slot = $sql["char"]->quote_smart($_GET["slot"]);
  $item = $sql["char"]->quote_smart($_GET["item"]);

  if ( ( !isset($cid) ) || ( !isset($bag) ) || ( !isset($slot) ) || ( !isset($item) ) )
    redirect("char_inv.php");

  if ( $core == 1 )
    $query = "DELETE FROM playeritems WHERE ownerguid='".$cid."' AND entry='".$item."' AND containerslot='".$bag."' AND slot='".$slot."'";
  elseif ( $core == 2 )
  {
    $query = "SELECT item FROM character_inventory WHERE guid='".$cid."' AND item_template='".$item."' AND bag='".$bag."' AND slot='".$slot."'";
    $result = $sql["char"]->query($query);
    $result = $sql["char"]->fetch_assoc($result);
    $item_guid = $result["item"];

    $query = "DELETE FROM character_inventory WHERE item='".$item_guid."'";
    $query2 = "DELETE FROM item_instance WHERE guid='".$item_guid."';";
  }
  else
  {
    $query = "SELECT item FROM character_inventory
                LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
              WHERE character_inventory.guid='".$cid."' AND itemEntry='".$item."' AND bag='".$bag."' AND slot='".$slot."'";
    $result = $sql["char"]->query($query);
    $result = $sql["char"]->fetch_assoc($result);
    $item_guid = $result["item"];

    $query = "DELETE FROM character_inventory WHERE item='".$item_guid."'";
    $query2 = "DELETE FROM item_instance WHERE guid='".$item_guid."';";
  }

  $result = $sql["char"]->query($query);
  if ( isset($query2) )
    $result = $sql["char"]->query($query2);

  redirect("char_inv.php?id=".$cid."&mode=".$_GET["mode"]);
}


//#############################################################################
// MAIN
//#############################################################################

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

$output .= '
      <div class="bubble">';

if ( $action == "delete_item" )
  delete_item();
elseif ( $action == "dodelete_item" )
  dodelete_item();
else
  char_inv();

unset($action_permission);

require_once "footer.php";


?>
