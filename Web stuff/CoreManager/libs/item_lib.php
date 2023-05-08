<?php
/*
    CoreManager, PHP Front End for ArcEmu, MaNGOS, and TrinityCore
    Copyright (C) 2010-2012  CoreManager Project
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

//#############################################################################
//get item set name by its id

 function get_itemset_name($id)
{
  global $corem_db, $sql;
  
  $itemset = $sql["dbc"]->fetch_row($sql["dbc"]->query("SELECT `ItemName` FROM `itemset` WHERE `ID`='".$id."' LIMIT 1"));
  return $itemset[0];
}


//#############################################################################
//generate item border from item_template.entry

function get_item_border($item_id)
{
  global $sql, $core;

  if ( $item_id )
  {
    if ( $core == 1 )
      $result = $sql["world"]->query("SELECT quality FROM items WHERE entry='".$item_id."'");
    else
      $result = $sql["world"]->query("SELECT Quality AS quality FROM item_template WHERE entry='".$item_id."'");
    $iborder = ( ( $sql["world"]->num_rows($result) == 1 ) ? $sql["world"]->result($result, 0, "quality") : "Quality: ".$iborder." Not Found" );

    return "icon_border_".$iborder;
  }
  else
    return "icon_border_0";
}


//#############################################################################
//get item name from item_template.entry

function get_item_name($item_id)
{
  global $world_db, $realm_id, $sql;

  if ( $item_id )
  {
    $deplang = get_lang_id();
    $result = $sql["world"]->query("SELECT name1 FROM items WHERE entry='".$item_id."'");
    $item_name = ( ( $sql["world"]->num_rows($result) == 1 ) ? $sql["world"]->result($result, 0, "name1") : "ItemID: ".$item_id." Not Found" );

    return $item_name;
  }
  else
    return NULL;
}


//#############################################################################
//get item icon - if icon not exists in item_icons folder D/L it from web.

function get_item_icon($itemid)
{
  global $corem_db, $world_db, $realm_id, $proxy_cfg, $get_icons_from_web, $item_icons,
         $sql, $core;

  if ( $core == 1 )
    $result = $sql["world"]->query("SELECT `displayid` FROM `items` WHERE `entry`='".$itemid."' LIMIT 1");
  else
    $result = $sql["world"]->query("SELECT `displayid` FROM `item_template` WHERE `entry`='".$itemid."' LIMIT 1");
  $displayid_record = $sql["world"]->fetch_assoc($result);
  $displayid = $displayid_record["displayid"];

  $result = $sql["dbc"]->query("SELECT `IconName` FROM itemdisplayinfo WHERE id='".$displayid."'");
  $icon_fields = $sql["dbc"]->fetch_assoc($result);
  return $item_icons."/".$icon_fields["IconName"].".png";
}


//#############################################################################
// get quality color

function get_item_quality_color($quality)
{
  switch ( $quality )
  {
    case 0:
    {
      return "#9d9d9d";
      break;
    }
    case 1:
    {
      return "#FFFFFF";
      break;
    }
    case 2:
    {
      return "#1eff00";
      break;
    }
    case 3:
    {
      return "#0070dd";
      break;
    }
    case 4:
    {
      return "#a335ee";
      break;
    }
    case 5:
    {
      return "#ff8000";
      break;
    }
    case 6:
    {
      return "#e5cc80";
      break;
    }
    case 7:
    {
      return "#e5cc80";
      break;
    }
  }
}


//#############################################################################
//generate item tooltip from item_template.entry

function get_item_tooltip($item, $ench, $prop, $creator, $durability, $flags)
{
  global $world_db, $realm_id, $locales_search_option, $sql, $core;

  if ( $item )
  {
    $tooltip = "";

    // properties
    if ( $core == 1 )
    {
      $query = "SELECT * FROM itemrandomproperties WHERE ID='".$prop."'";
      $result = $sql["dbc"]->query($query);
      $i_prop = $sql["dbc"]->fetch_assoc($result);
    }
    else
    {
      if ( $prop > 2147483647 )
      {
        // Random Suffix values are stored as "negative" integers
        // this_is_junk: the SpellItemEnchantment value pointed to by the fields in a
        // RandomSuffix have +i instead of a stated value.
        // I'm not sure yet how to translate that.
        // tempararily disabling showing Random Suffixes...
        //$prop = 4294967296 - $prop;
        $query = "SELECT * FROM itemrandomsuffix WHERE ID='".$prop."'";
        $result = $sql["dbc"]->query($query);
        $i_prop = $sql["dbc"]->fetch_assoc($result);
      }
      else
      {
        // Random Property values are stored as "positive" integers
        $query = "SELECT * FROM itemrandomproperties WHERE ID='".$prop."'";
        $result = $sql["dbc"]->query($query);
        $i_prop = $sql["dbc"]->fetch_assoc($result);
      }
    }

    // enchantment
    if ( $core == 1 )
    {
      // ArcEmu stores extra (and unneeded) enchantments
      // first, we get all the enchantments
      $temp_ench = explode(";", $ench);
      // then, we just need the enchantment in slot 0
      $ench = 0;
      foreach ( $temp_ench as $temp_row)
      {
        // it seems that we will sometimes get an extra array member. :/
        // we don't want it corrupting our data.
        if ( $temp_row != "" )
        {
          $temp = explode(",", $temp_row);

          if ( $temp[2] == 0 )
          {
              $ench = $temp[0];
          }
        }
      }
    }

    $query = "SELECT * FROM spellitemenchantment WHERE ID='".$ench."'";
    $result = $sql["dbc"]->query($query);
    $i_ench = $sql["dbc"]->fetch_assoc($result);

    // Localization
    if ( $locales_search_option != 0 )
    {
      if ( $core == 1 )
        $itemname = htmlspecialchars($item["name"], ENT_COMPAT, $site_encoding);
      else
        $itemname = htmlspecialchars($item["name_loc".$locales_search_option], ENT_COMPAT, $site_encoding);

      // if for some reason there is no localized item name, we'll use the English
      // (hopefully there will be one to show)
      if ( $itemname == "" )
        $itemname = htmlspecialchars($item["name"], ENT_COMPAT, $site_encoding);
    }
    else
      $itemname = htmlspecialchars($item["name"], ENT_COMPAT, $site_encoding);

    switch ( $item["Quality"] )
    {
      case 0: //Grey Poor
        $tooltip .= '<span style="color: #b2c2b9;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 1: //White Common
        $tooltip .= '<span style="color: white;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 2: //Green Uncommon
        $tooltip .= '<span style="color: #1eff00;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 3: //Blue Rare
        $tooltip .= '<span style="color: #0070dd;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 4: //Purple Epic
        $tooltip .= '<span style="color: #a335ee;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 5: //Orange Legendary
        $tooltip .= '<span style="color: orange;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 6: //Gold Artifact
        $tooltip .= '<span style="color: #e5cc80;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      case 7: //Gold Heirloom
        $tooltip .= '<span style="color: #e5cc80;" class="large">'.$itemname.' '.$i_prop["Name"].'</span><br />';
        break;
      default:
    }

    $tooltip .= '<span style="color: white;">';

    // bonding
    // first, we check if our item is already bound
    // if not, show how it will bind
    if ( ( $flags && 1 ) == 1 )
    {
      $tooltip .= lang("item", "bound").'<br />';
    }
    else
    {
      switch ( $item["bonding"] )
      {
        case 1: //Binds when Picked Up
          $tooltip .= lang("item", "bop").'<br />';
          break;
        case 2: //Binds when Equipped
          $tooltip .= lang("item", "boe").'<br />';
          break;
        case 3: //Binds when Used
          $tooltip .= lang("item", "bou").'<br />';
          break;
        case 4: //Quest Item
          $tooltip .= lang("item", "quest_item").'<br />';
          break;
        default:
      }
    }

    if ( $core == 1 )
    {
      if ( $item["Unique"] )
        $tooltip .= lang("item", "unique").'<br />';
    }
    else
    {
      if ( $item["maxcount"] )
        $tooltip .= lang("item", "unique").'<br />';
    }

    switch ( $item["InventoryType"] )
    {
      case 1:
        $tooltip .= lang("item", "head").' - ';
        break;
      case 2:
        $tooltip .= lang("item", "neck").' - ';
        break;
      case 3:
        $tooltip .= lang("item", "shoulder").' - ';
        break;
      case 4:
        $tooltip .= lang("item", "shirt").' - ';
        break;
      case 5:
        $tooltip .= lang("item", "chest").' - ';
        break;
      case 6:
        $tooltip .= lang("item", "belt").' - ';
        break;
      case 7:
        $tooltip .= lang("item", "legs").' - ';
        break;
      case 8:
        $tooltip .= lang("item", "feet").' - ';
        break;
      case 9:
        $tooltip .= lang("item", "wrist").' - ';
        break;
      case 10:
        $tooltip .= lang("item", "gloves").' - ';
        break;
      case 11:
        $tooltip .= lang("item", "finger").' - ';
        break;
      case 12:
        $tooltip .= lang("item", "trinket").' - ';
        break;
      case 13:
        $tooltip .= lang("item", "one_hand").' - ';
        break;
      case 14:
        $tooltip .= lang("item", "off_hand").' - ';
        break;
      case 16:
        $tooltip .= lang("item", "back").' - ';
        break;
      case 18:
        $tooltip .= lang("item", "bag").'<br />';
        break;
      case 19:
        $tooltip .= lang("item", "tabard").' - ';
        break;
      case 20:
        $tooltip .= lang("item", "robe").' - ';
        break;
      case 21:
        $tooltip .= lang("item", "main_hand").' - ';
        break;
      case 23:
        $tooltip .= lang("item", "tome").' - ';
        break;
      default:
    }
    switch ( $item["class"] )
    {
      case 0: //Consumable
        $tooltip .= lang("item", "consumable").'<br />';
        break;
      case 2: //Weapon
        switch ( $item["subclass"] )
        {
          case 0:
            $tooltip .= lang("item", "axe_1h").'<br />';
            break;
          case 1:
            $tooltip .= lang("item", "axe_2h").'<br />';
            break;
          case 2:
            $tooltip .= lang("item", "bow").'<br />';
            break;
          case 3:
            $tooltip .= lang("item", "rifle").'<br />';
            break;
          case 4:
            $tooltip .= lang("item", "mace_1h").'<br />';
            break;
          case 5:
            $tooltip .= lang("item", "mace_2h").'<br />';
            break;
          case 6:
            $tooltip .= lang("item", "polearm").'<br />';
            break;
          case 7:
            $tooltip .= lang("item", "sword_1h").'<br />';
            break;
          case 8:
            $tooltip .= lang("item", "sword_2h").'<br />';
            break;
          case 10:
            $tooltip .= lang("item", "staff").'<br />';
            break;
          case 11:
            $tooltip .= lang("item", "exotic_1h").'<br />';
            break;
          case 12:
            $tooltip .= lang("item", "exotic_2h").'<br />';
            break;
          case 13:
            $tooltip .= lang("item", "fist_weapon").'<br />';
            break;
          case 14:
            $tooltip .= lang("item", "misc_weapon").'<br />';
            break;
          case 15:
            $tooltip .= lang("item", "dagger").'<br />';
            break;
          case 16:
            $tooltip .= lang("item", "thrown").'<br />';
            break;
          case 17:
            $tooltip .= lang("item", "spear").'<br />';
            break;
          case 18:
            $tooltip .= lang("item", "crossbow").'<br />';
            break;
          case 19:
            $tooltip .= lang("item", "wand").'<br />';
            break;
          case 20:
            $tooltip .= lang("item", "fishing_pole").'<br />';
            break;
          default:
        }
        break;
      case 4: //Armor
        switch ( $item["subclass"] )
        {
          case 0:
            $tooltip .= lang("item", "misc").'<br />';
            break;
          case 1:
            $tooltip .= lang("item", "cloth").'<br />';
            break;
          case 2:
            $tooltip .= lang("item", "leather").'<br />';
            break;
          case 3:
            $tooltip .= lang("item", "mail").'<br />';
            break;
          case 4:
            $tooltip .= lang("item", "plate").'<br />';
            break;
          case 6:
            $tooltip .= lang("item", "shield").'<br />';
            break;
          default:
        }
        break;
      case 6: //Projectile
        switch ( $item["subclass"] )
        {
          case 2:
            $tooltip .= lang("item", "arrows").'<br />';
            break;
          case 3:
            $tooltip .= lang("item", "bullets").'<br />';
            break;
          default:
        }
        break;
      case 7: //Trade Goods
        switch ( $item["subclass"] )
        {
          case 0:
            $tooltip .= lang("item", "trade_goods").'<br />';
            break;
          case 1:
            $tooltip .= lang("item", "parts").'<br />';
            break;
          case 2:
            $tooltip .= lang("item", "explosives").'<br />';
            break;
          case 3:
            $tooltip .= lang("item", "devices").'<br />';
            break;
          default:
        }
        break;
      case 9: //Recipe
        switch ( $item["subclass"] )
        {
          case 0:
            $tooltip .= lang("item", "book").'<br />';
            break;
          case 1:
            $tooltip .= lang("item", "LW_pattern").'<br />';
            break;
          case 2:
            $tooltip .= lang("item", "tailoring_pattern").'<br />';
            break;
          case 3:
            $tooltip .= lang("item", "ENG_Schematic").'<br />';
            break;
          case 4:
            $tooltip .= lang("item", "BS_plans").'<br />';
            break;
          case 5:
            $tooltip .= lang("item", "cooking_recipe").'<br />';
            break;
          case 6:
            $tooltip .= lang("item", "alchemy_recipe").'<br />';
            break;
          case 7:
            $tooltip .= lang("item", "FA_manual").'<br />';
            break;
          case 8:
            $tooltip .= lang("item", "ench_formula").'<br />';
            break;
          case 9:
            $tooltip .= lang("item", "JC_formula").'<br />';
            break;
          default:
        }
        break;
      case 11: //Quiver
        switch ( $item["subclass"] )
        {
          case 2:
            $tooltip .= ' '.lang("item", "quiver").'<br />';
            break;
          case 3:
            $tooltip .= ' '.lang("item", "ammo_pouch").'<br />';
            break;
          default:
        }
        break;
      case 12: //Quest
        if ( $item["bonding"] != 4 )
          $tooltip .= lang("item", "quest_item").'<br />';
        break;
      case 13: //key
        switch ( $item["subclass"] )
        {
          case 0:
            $tooltip .= lang("item", "key").'<br />';
            break;
          case 1:
            $tooltip .= lang("item", "lockpick").'<br />';
            break;
          default:
        }
        break;
      default:
    }
    $tooltip .= ( ( $item["armor"] ) ? $item["armor"].' '.lang("item", "armor").'<br />' : '' );

    for ( $f = 37; $f <= 41; $f += 3 )
    {
      // why does it count so high if there are so few damage values?
      switch ( $f )
      {
        case 37:
        {
          $type = "dmg_type1";
          $min = "dmg_min1";
          $max = "dmg_max1";
          break;
        }
        case 40:
        {
          $type = "dmg_type2";
          $min = "dmg_min2";
          $max = "dmg_max2";
          break;
        }
      }

      $dmg_type = $item[$type];
      $min_dmg_value = $item[$min];
      $max_dmg_value = $item[$max];

      if ( $min_dmg_value && $max_dmg_value )
      {
        switch ( $dmg_type )
        {
          case 0: // Physical
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "damage").'<br />('.($item["delay"] ? round(((($min_dmg_value + $max_dmg_value) / 2) / ($item["delay"] / 1000)), 2): $min_dmg_value).' DPS)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.lang("item", "speed").' : '.(($item["delay"]) / 1000).'<br />';
            break;
          case 1: // Holy
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "holy_dmg").'<br />';
            break;
          case 2: // Fire
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "fire_dmg").'<br />';
            break;
          case 3: // Nature
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "nature_dmg").'<br />';
            break;
          case 4: // Frost
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "frost_dmg").'<br />';
            break;
          case 5: // Shadow
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "shadow_dmg").'<br />';
            break;
          case 6: // Arcane
            $tooltip .= $min_dmg_value.' - '.$max_dmg_value.' '.lang("item", "arcane_dmg").'<br />';
            break;
          default:
        }
      }
    }

    //basic status
    for ( $s = 0; $s <= 18; $s += 2 )
    {
      switch ( $s )
      {
        case 0:
        {
          $type = "stat_type1";
          $value = "stat_value1";
          break;
        }
        case 2:
        {
          $type = "stat_type2";
          $value = "stat_value2";
          break;
        }
        case 4:
        {
          $type = "stat_type3";
          $value = "stat_value3";
          break;
        }
        case 6:
        {
          $type = "stat_type4";
          $value = "stat_value4";
          break;
        }
        case 8:
        {
          $type = "stat_type5";
          $value = "stat_value5";
          break;
        }
        case 10:
        {
          $type = "stat_type6";
          $value = "stat_value6";
          break;
        }
        case 12:
        {
          $type = "stat_type7";
          $value = "stat_value7";
          break;
        }
        case 14:
        {
          $type = "stat_type8";
          $value = "stat_value8";
          break;
        }
        case 16:
        {
          $type = "stat_type9";
          $value = "stat_value9";
          break;
        }
        case 18:
        {
          $type = "stat_type10";
          $value = "stat_value10";
          break;
        }
      }

      $stat_value = $item[$value];
      if ( $item[$type] && $stat_value )
      {
        switch ( $item[$type] )
        {
          case 1:
            $tooltip .= '+'.$stat_value.' '.lang("item", "health").'<br />';
            break;
          case 2:
            $tooltip .= '+'.$stat_value.' '.lang("item", "mana").'<br />';
            break;
          case 3:
            $tooltip .= '+'.$stat_value.' '.lang("item", "agility").'<br />';
            break;
          case 4:
            $tooltip .= '+'.$stat_value.' '.lang("item", "strength").'<br />';
            break;
          case 5:
            $tooltip .= '+'.$stat_value.' '.lang("item", "intellect").'<br />';
            break;
          case 6:
            $tooltip .= '+'.$stat_value.' '.lang("item", "spirit").'<br />';
            break;
          case 7:
            $tooltip .= '+'.$stat_value.' '.lang("item", "stamina").'<br />';
            break;
          default:
            $flag_rating = 1;
        }
      }
    }

    if ( $item["holy_res"] )
      $tooltip .= $item["holy_res"].' '.lang("item", "res_holy").'<br />';
    if ( $item["arcane_res"] )
      $tooltip .= $item["arcane_res"].' '.lang("item", "res_arcane").'<br />';
    if ( $item["fire_res"] )
      $tooltip .= $item["fire_res"].' '.lang("item", "res_fire").'<br />';
    if ( $item["nature_res"] )
      $tooltip .= $item["nature_res"].' '.lang("item", "res_nature").'<br />';
    if ( $item["frost_res"] )
      $tooltip .= $item["frost_res"].' '.lang("item", "res_frost").'<br />';
    if ( $item["shadow_res"] )
      $tooltip .= $item["shadow_res"].' '.lang("item", "res_shadow").'<br />';

    //sockets
    for ( $p = 72; $p <= 74; $p++ )
    {
      switch ( $p )
      {
        case 72:
        {
          $sock = "socketColor_1";
          break;
        }
        case 73:
        {
          $sock = "socketColor_2";
          break;
        }
        case 74:
        {
          $sock = "socketColor_3";
          break;
        }
      }

      if ( $item[$sock] )
      {
        switch ( $item[$sock] )
        {
          case 1:
            $tooltip .= '<img class="item_tooltip_socket" src="img/socket_meta.gif" alt="" /><span style="color: gray;"> '.lang("item", "socket_meta").'</span><br />';
            break;
          case 2:
            $tooltip .= '<img class="item_tooltip_socket" src="img/socket_red.gif" alt="" /><span style="color: red;"> '.lang("item", "socket_red").'</span><br />';
            break;
          case 4:
            $tooltip .= '<img class="item_tooltip_socket" src="img/socket_yellow.gif" alt="" /><span style="color: yellow;"> '.lang("item", "socket_yellow").'</span><br />';
            break;
          case 8:
            $tooltip .= '<img class="item_tooltip_socket" src="img/socket_blue.gif" alt="" /><span style="color: blue;"> '.lang("item", "socket_blue").'</span><br />';
            break;
          default:
        }
      }
    }

    $tooltip .= '</span><span style="color: #1eff00;">';

    // random enchantments
    // we color them green because we don't add them to the normal stat values, yet.
    if ( $prop > 2147483647 )
      // tempararily disabling showing Random Suffixes...
      $tooltip .= '< Random Enchantment ><br />';
    else
    {
      if ( $i_prop )
      {
        $prop1 = $i_prop["SpellItemEnchantment_1"];
        $prop2 = $i_prop["SpellItemEnchantment_2"];
        $prop3 = $i_prop["SpellItemEnchantment_3"];
        $prop4 = $i_prop["SpellItemEnchantment_4"];
        $prop5 = $i_prop["SpellItemEnchantment_5"];

        $query = "SELECT * FROM spellitemenchantment 
          WHERE ".
          ( ( $prop1 ) ? "ID=".$prop1 : "" ).
          ( ( $prop2 ) ? " OR ID=".$prop2 : "" ).
          ( ( $prop3 ) ? " OR ID=".$prop3 : "" ).
          ( ( $prop4 ) ? " OR ID=".$prop4 : "" ).
          ( ( $prop5 ) ? " OR ID=".$prop5 : "" );
        $result = $sql["dbc"]->query($query);
        while ( $prop_row = $sql["dbc"]->fetch_assoc($result) )
        {
          $tooltip .= $prop_row["EnchantmentName"].'<br />';
        }
      }
    }

    $tooltip .= '</span><span style="color: #ffffff;">';
    
    if ( ( isset($durability) ) && ( $item["MaxDurability"] > 0 ) )
    {
      $tooltip .= lang("item", "durability").' '.$durability." / ".$item["MaxDurability"].'<br />';
    }

    //level requierment
    if ( $item["RequiredLevel"] > 1)
      $tooltip .= lang("item", "lvl_req").' '.$item["RequiredLevel"].'<br />';
    elseif ( $item["RequiredLevel"] == 0)
    {
      if ( $item["Quality"] == 7 )
        $tooltip .= lang("item", "lvl_req2").'<br />';// "Requires Level 1 to 80"
    }
    else
      ;// don't bother showing "Requires Level" for items that require level 1

    //item level
    if ( $item["ItemLevel"] )
      $tooltip .= lang("item", "itemlvl").' '.$item["ItemLevel"].'<br />';

    //allowable classes
    if ( ( $item["AllowableClass"] ) && ( $item["AllowableClass"] != -1 ) && ( $item["AllowableClass"] != 1503 ) )
    {
      $tooltip .= lang("item", "class").':';
      if ( $item["AllowableClass"] & 1 )
        $tooltip .= ' '.lang("id_tab", "warrior").' ';
      if ( $item["AllowableClass"] & 2 )
        $tooltip .= ' '.lang("id_tab", "paladin").' ';
      if ( $item["AllowableClass"] & 4 )
        $tooltip .= ' '.lang("id_tab", "hunter").' ';
      if ( $item["AllowableClass"] & 8 )
        $tooltip .= ' '.lang("id_tab", "rogue").' ';
      if ( $item["AllowableClass"] & 16 )
        $tooltip .= ' '.lang("id_tab", "priest").' ';
      if ( $item["AllowableClass"] & 64 )
        $tooltip .= ' '.lang("id_tab", "shaman").' ';
      if ( $item["AllowableClass"] & 128 )
        $tooltip .= ' '.lang("id_tab", "mage").' ';
      if ( $item["AllowableClass"] & 256 )
        $tooltip .= ' '.lang("id_tab", "warlock").' ';
      if ( $item["AllowableClass"] & 1024 )
        $tooltip .= ' '.lang("id_tab", "druid").' ';
      $tooltip .= '<br />';
    }

    //number of bag slots
    if ( $item["ContainerSlots"] )
      $tooltip .= ' '.$item["ContainerSlots"].' '.lang("item", "slots").'<br />';

    $tooltip .= '</span><span style="color: #1eff00;">';

    //created enchantments
    if ( $ench )
      $tooltip .= $i_ench["EnchantmentName"].'<br />';

    //Ratings additions.
    if ( isset($flag_rating) )
    {
      for( $s = 0; $s <= 18; $s += 2 )
      {
        switch ( $s )
        {
          case 0:
          {
            $type = "stat_type1";
            $value = "stat_value1";
            break;
          }
          case 2:
          {
            $type = "stat_type2";
            $value = "stat_value2";
            break;
          }
          case 4:
          {
            $type = "stat_type3";
            $value = "stat_value3";
            break;
          }
          case 6:
          {
            $type = "stat_type4";
            $value = "stat_value4";
            break;
          }
          case 8:
          {
            $type = "stat_type5";
            $value = "stat_value5";
            break;
          }
          case 10:
          {
            $type = "stat_type6";
            $value = "stat_value6";
            break;
          }
          case 12:
          {
            $type = "stat_type7";
            $value = "stat_value7";
            break;
          }
          case 14:
          {
            $type = "stat_type8";
            $value = "stat_value8";
            break;
          }
          case 16:
          {
            $type = "stat_type9";
            $value = "stat_value9";
            break;
          }
          case 18:
          {
            $type = "stat_type10";
            $value = "stat_value10";
            break;
          }
        }

        $stat_type = $item[$type];
        $stat_value = $item[$value];
        if ( $stat_type && $stat_value )
        {
          switch ( $stat_type )
          {
            case 12:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "DEFENCE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 13:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "DODGE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 14:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "PARRY_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 15:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "SHIELD_BLOCK_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 16:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "MELEE_HIT_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 17:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "RANGED_HIT_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 18:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "SPELL_HIT_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 19:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "MELEE_CS_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 20:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "RANGED_CS_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 21:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "SPELL_CS_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 22:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "MELEE_HA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 23:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "RANGED_HA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 24:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "SPELL_HA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 25:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "MELEE_CA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 26:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "RANGED_CA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 27:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "SPELL_CA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 28:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "MELEE_HASTE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 29:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "RANGED_HASTE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 30:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "SPELL_HASTE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 31:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "HIT_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 32:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "CS_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 33:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "HA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 34:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "CA_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 35:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "RESILIENCE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            case 36:
              $tooltip .= lang("item", "spell_equip").': '.lang("item", "improves").' '.lang("item", "HASTE_RATING").' '.lang("item", "rating_by").' '.$stat_value.'.<br />';
              break;
            default:
          }
        }
      }
    }
    //add equip spellid to status
    for ( $s1 = 27; $s1 <= 31; $s1++ )
    {
      switch ( $s1 )
      {
        case 27:
        {
          $spellid = "spellid_1";
          $trigger = "spelltrigger_1";
          $charges = "spellcharges_1";
          break;
        }
        case 28:
        {
          $spellid = "spellid_2";
          $trigger = "spelltrigger_2";
          $charges = "spellcharges_2";
          break;
        }
        case 29:
        {
          $spellid = "spellid_3";
          $trigger = "spelltrigger_3";
          $charges = "spellcharges_3";
          break;
        }
        case 30:
        {
          $spellid = "spellid_4";
          $trigger = "spelltrigger_4";
          $charges = "spellcharges_4";
          break;
        }
        case 31:
        {
          $spellid = "spellid_5";
          $trigger = "spelltrigger_5";
          $charges = "spellcharges_5";
          break;
        }
      }
      if ( $item[$spellid] )
      {
        switch ( $item[$trigger] )
        {
          case 0:
            $tooltip .= lang("item", "spell_use").': ';
            break;
          case 1:
            $tooltip .= lang("item", "spell_equip").': ';
            break;
          case 2:
            $tooltip .= lang("item", "spell_coh").': ';
            break;
          default:
        }

        $query = "SELECT Name FROM spell WHERE Id='".$item[$spellid]."'";
        $result = $sql["dbc"]->query($query);
        $spell = $sql["dbc"]->fetch_assoc($result);
        $spell = $spell["Name"];

        $tooltip .= ' '.$spell.'<br />';
        if ( $item[$spellid] )
        {
          if ( $item[$charges] > 1 )
            $tooltip.= "".abs($item[$charges]).' '.lang("item", "charges").'.<br />';
        }
      }
    }

    $tooltip .= '</span>';

    if ( $item["itemset"] )
    {
      include_once("id_tab.php");
      $tooltip .= '<span style="color: orange;">'.lang("item", "item_set").' : '.get_itemset_name($item["itemset"]).' ('.$item["itemset"].')</span><br />';
    }
    if ( $item["description1"] )
    {
      // Localization
      if ( $locales_search_option != 0 )
        if ( $core == 1 )
          $item["description"] = $item["description"];
        else
          $item["description"] = $item["description_loc".$locales_search_option];
      else
        $item["description"] = $item["description1"];

      $tooltip .= '<span style="color: orange;">\''.str_replace("\"", " '", $item["description"]).'\'</span><br />';
    }
      
    if ( $creator )
    {
      if ( !$core == 1 )
      {
        $c_query = "SELECT name FROM characters WHERE guid='".$creator."'";
        $c_result = $sql["char"]->query($c_query);
        $c_field = $sql["char"]->fetch_assoc($c_result);
        $creator = $c_field["name"];
      }

      $tooltip .= '<span style="color: #1eff00;">&lt; '.lang("item", "madeby").' '.$creator.' &gt;</span><br />';
    }

    if ( $item["SellPrice"] )
    {
      // pad the sell price
      $SellPrice = str_pad($item["SellPrice"], 4, "0", STR_PAD_LEFT);

      // break it into gold, silver, and copper
      $pg = substr($SellPrice,  0, -4);
      if ( $pg == "" )
        $pg = 0;
      $ps = substr($SellPrice, -4,  2);
      if ( ( $ps == "" ) || ( $ps == "00" ) )
        $ps = 0;
      $pc = substr($SellPrice, -2);
      if ( ( $pc == "" ) || ( $pc == "00" ) )
        $pc = 0;

      // convert the strings into numbers
      $pg = floor($pg);
      $ps = floor($ps);
      $pc = floor($pc);

      $tooltip .= lang("item", "sellprice").': ';
      if ( $pg )
        $tooltip .= $pg.'<img class="item_tooltip_price" src="img/gold.gif" alt="" style="position: relative; bottom: -6px;" />';
      if ( $ps )
        $tooltip .= $ps.'<img class="item_tooltip_price" src="img/silver.gif" alt="" style="position: relative; bottom: -6px;" />';
      if ( $pc )
        $tooltip .= $pc.'<img class="item_tooltip_price" src="img/copper.gif" alt="" style="position: relative; bottom: -6px;" />';
    }
    else
    {
      $tooltip .= lang("item", "nosellprice");
    }

    return $tooltip;
  }
  else
    return(NULL);
}


?>
