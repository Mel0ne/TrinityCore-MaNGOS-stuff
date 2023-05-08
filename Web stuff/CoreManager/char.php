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
require_once "libs/item_lib.php";
require_once "libs/spell_lib.php";
require_once "libs/map_zone_lib.php";

valid_login($action_permission["view"]);

//########################################################################################################################
// SHOW GENERAL CHARACTERS INFO
//########################################################################################################################
function char_main()
{
  global $output,
    $realm_id, $logon_db, $characters_db, $world_db, $server, $corem_db, $site_encoding,
    $action_permission, $user_lvl, $user_name, $user_id, $locales_search_option,
    $base_datasite, $item_datasite, $spell_datasite, $showcountryflag, $timezone_offset, $sql, $core;

  // this page uses wowhead tooltops
  //wowhead_tt();

  // we need at either an id or a name or we would have nothing to show
  if ( empty($_GET["id"]) )
    if ( empty($_GET["name"]) )
      error(lang("global", "empty_fields"));

  // this is multi realm support, as of writing still under development
  // this page is already implementing it
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

  if ( empty($_GET["id"]) )
  {
    $name = $sql["char"]->quote_smart($_GET["name"]);
    if ( $core == 1 )
      $result = $sql["char"]->query("SELECT guid, acct, race FROM characters WHERE name='".$name."' LIMIT 1");
    else
      $result = $sql["char"]->query("SELECT guid, id AS acct, race FROM characters WHERE name='".$name."' LIMIT 1");
    $id_result = $sql["char"]->fetch_assoc($result);
    $id = $id_result["guid"];
  }
  else
  {
    $id = $sql["char"]->quote_smart($_GET["id"]);
  }

  if ( !is_numeric($id) )
    error(lang("global", "empty_fields"));

  if ( $core == 1 )
    $result = $sql["char"]->query("SELECT acct, race FROM characters WHERE guid='".$id."' LIMIT 1");
  else
    $result = $sql["char"]->query("SELECT account AS acct, race FROM characters WHERE guid='".$id."' LIMIT 1");

  if ( $sql["char"]->num_rows($result) )
  {
    //resrict by owner's gmlvl
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
    $view_mod = $s_fields["View_Mod_Sheet"];

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

    if ( $user_lvl || $server[$realmid]["both_factions"] )
    {
      $side_v = 0;
      $side_p = 0;
    }
    else
    {
      $side_p = ( ( in_array($sql["char"]->result($result, 0, "race"), array(2, 5, 6, 8, 10)) ) ? 1 : 2 );
      
      if ( $core == 1 )
        $result_1 = $sql["char"]->query("SELECT race FROM characters WHERE acct='".$user_id."' LIMIT 1");
      else
        $result_1 = $sql["char"]->query("SELECT race FROM characters WHERE account='".$user_id."' LIMIT 1");

      if ( $sql["char"]->num_rows($result) )
        $side_v = ( ( in_array($sql["char"]->result($result_1, 0, "race"), array(2, 5, 6, 8, 10)) ) ? 1 : 2 );
      else
        $side_v = 0;
      unset($result_1);
    }

    if ( ( $view_override ) || ( ( $user_lvl >= gmlevel($owner_gmlvl) ) && ( ( $side_v === $side_p ) || !$side_v ) ) )
    {
      if ( $core == 1 )
      {
        $result = $sql["char"]->query("SELECT guid, name, race, class, level, zoneid, mapid, online, gender,
          SUBSTRING_INDEX(SUBSTRING_INDEX(playedtime, ' ', 2), ' ', -1) AS totaltime,
          acct, data, timestamp, xp 
          FROM characters WHERE guid='".$id."'");
      }
      elseif ( $core == 2 )
      {
        $result = $sql["char"]->query("SELECT guid, name, race, class, level, zone AS zoneid, map AS mapid, 
          online, gender, totaltime, account AS acct, logout_time AS timestamp, health, 
					power1, power2, power3, power4, power5, power6, power7, xp,
          arenaPoints, totalHonorPoints, totalKills
          FROM characters WHERE guid='".$id."'");
      }
      else
      {
        $result = $sql["char"]->query("SELECT guid, name, race, class, level, zone AS zoneid, map AS mapid, 
          online, gender, totaltime, account AS acct, logout_time AS timestamp, health, 
					power1, power2, power3, power4, power5, power6, power7, xp, arenaPoints, totalHonorPoints, totalKills
          FROM characters WHERE guid='".$id."'");
      }
      $char = $sql["char"]->fetch_assoc($result);

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

      if ( $core == 1 )
      {
        $char_data = $char["data"];
        if ( empty($char_data) )
          $char_data = str_repeat("0;", PLAYER_END);
        $char_data = explode(";",$char_data);
      }
      else
      {
        $query = "SELECT * FROM characters LEFT JOIN character_stats ON characters.guid=character_stats.guid WHERE characters.guid='".$id."'";
        $char_data_result = $sql["char"]->query($query);
        $char_data_fields = $sql["char"]->fetch_assoc($char_data_result);

        $char_data[PLAYER_BLOCK_PERCENTAGE] = ( ( isset($char_data_fields["blockPct"]) ) ? $char_data_fields["blockPct"] : '&nbsp;' );
        $char_data[PLAYER_DODGE_PERCENTAGE] = ( ( isset($char_data_fields["dodgePct"]) ) ? $char_data_fields["dodgePct"] : '&nbsp;' );
        $char_data[PLAYER_PARRY_PERCENTAGE] = ( ( isset($char_data_fields["parryPct"]) ) ? $char_data_fields["parryPct"] : '&nbsp;' );
        $char_data[PLAYER_CRIT_PERCENTAGE] = ( ( isset($char_data_fields["critPct"]) ) ? $char_data_fields["critPct"] : '&nbsp;' );
        $char_data[PLAYER_RANGED_CRIT_PERCENTAGE] = ( ( isset($char_data_fields["rangedCritPct"]) ) ? $char_data_fields["rangedCritPct"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXDAMAGE] = ( ( isset($char_data_fields["attackPower"]) ) ? $char_data_fields["attackPower"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MINDAMAGE] = ( ( isset($char_data_fields["attackPower"]) ) ? $char_data_fields["attackPower"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXRANGEDDAMAGE] = ( ( isset($char_data_fields["rangedAttackPower"]) ) ? $char_data_fields["rangedAttackPower"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MINRANGEDDAMAGE] = ( ( isset($char_data_fields["rangedAttackPower"]) ) ? $char_data_fields["rangedAttackPower"] : '&nbsp;' );
        $char_data[PLAYER_SPELL_CRIT_PERCENTAGE1] = ( ( isset($char_data_fields["spellCritPct"]) ) ? $char_data_fields["spellCritPct"] : '&nbsp;' );
        $char_data[PLAYER_FIELD_MOD_DAMAGE_DONE_POS] = ( ( isset($char_data_fields["spellPower"]) ) ? $char_data_fields["spellPower"] : '&nbsp;' );
        $char_data[UNIT_FIELD_STAT0] = ( ( isset($char_data_fields["strength"]) ) ? $char_data_fields["strength"] : '&nbsp;' );
        $char_data[UNIT_FIELD_STAT1] = ( ( isset($char_data_fields["agility"]) ) ? $char_data_fields["agility"] : '&nbsp;' );
        $char_data[UNIT_FIELD_STAT2] = ( ( isset($char_data_fields["stamina"]) ) ? $char_data_fields["stamina"] : '&nbsp;' );
        $char_data[UNIT_FIELD_STAT3] = ( ( isset($char_data_fields["intellect"]) ) ? $char_data_fields["intellect"] : '&nbsp;' );
        $char_data[UNIT_FIELD_STAT4] = ( ( isset($char_data_fields["spirit"]) ) ? $char_data_fields["spirit"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES] = ( ( isset($char_data_fields["armor"]) ) ? $char_data_fields["armor"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES + 1] = ( ( isset($char_data_fields["resHoly"]) ) ? $char_data_fields["resHoly"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES + 2] = ( ( isset($char_data_fields["resArcane"]) ) ? $char_data_fields["resArcane"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES + 3] = ( ( isset($char_data_fields["resFire"]) ) ? $char_data_fields["resFire"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES + 4] = ( ( isset($char_data_fields["resNature"]) ) ? $char_data_fields["resNature"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES + 5] = ( ( isset($char_data_fields["resFrost"]) ) ? $char_data_fields["resFrost"] : '&nbsp;' );
        $char_data[UNIT_FIELD_RESISTANCES + 6] = ( ( isset($char_data_fields["resShadow"]) ) ? $char_data_fields["resShadow"] : '&nbsp;' );
        $char_data[UNIT_FIELD_HEALTH] = ( ( isset($char["health"]) ) ? $char["health"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXHEALTH] = ( ( isset($char_data_fields["maxhealth"]) ) ? $char_data_fields["maxhealth"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER1] = ( ( isset($char["power1"]) ) ? $char["power1"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER2] = ( ( isset($char["power2"]) ) ? $char["power2"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER3] = ( ( isset($char["power3"]) ) ? $char["power3"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER4] = ( ( isset($char["power4"]) ) ? $char["power4"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER5] = ( ( isset($char["power5"]) ) ? $char["power5"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER6] = ( ( isset($char["power6"]) ) ? $char["power6"] : '&nbsp;' );
        $char_data[UNIT_FIELD_POWER7] = ( ( isset($char["power7"]) ) ? $char["power7"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER1] = ( ( isset($char_data_fields["maxpower1"]) ) ? $char_data_fields["maxpower1"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER2] = ( ( isset($char_data_fields["maxpower2"]) ) ? $char_data_fields["maxpower2"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER3] = ( ( isset($char_data_fields["maxpower3"]) ) ? $char_data_fields["maxpower3"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER4] = ( ( isset($char_data_fields["maxpower4"]) ) ? $char_data_fields["maxpower4"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER5] = ( ( isset($char_data_fields["maxpower5"]) ) ? $char_data_fields["maxpower5"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER6] = ( ( isset($char_data_fields["maxpower6"]) ) ? $char_data_fields["maxpower6"] : '&nbsp;' );
        $char_data[UNIT_FIELD_MAXPOWER7] = ( ( isset($char_data_fields["maxpower7"]) ) ? $char_data_fields["maxpower7"] : '&nbsp;' );
        $char_data[PLAYER_FIELD_MOD_HEALING_DONE_POS] = "ERR";
        $char_data[PLAYER_FIELD_COMBAT_RATING_1+5] = "ERR";
        $char_data[PLAYER_FIELD_COMBAT_RATING_1+17] = "ERR";
        $char_data[PLAYER_FIELD_COMBAT_RATING_1+6] = "ERR";
        $char_data[PLAYER_FIELD_COMBAT_RATING_1+7] = "ERR";
        $char_data[PLAYER_EXPERTISE] = "ERR";
        $char_data[PLAYER_OFFHAND_EXPERTISE] = "ERR";
        $char_data[PLAYER_FIELD_HONOR_CURRENCY] = ( ( isset($char["totalHonorPoints"]) ) ? $char["totalHonorPoints"] : '&nbsp;' );
        $char_data[PLAYER_FIELD_ARENA_CURRENCY] = ( ( isset($char["arenaPoints"]) ) ? $char["arenaPoints"] : '&nbsp;' );
        $char_data[PLAYER_FIELD_LIFETIME_HONORBALE_KILLS] = ( ( isset($char["totalKills"]) ) ? $char["totalKills"] : '&nbsp;' );
      }

      if ( $core == 1 )
      {
        $guild_id = $sql["char"]->result($sql["char"]->query("SELECT guildid FROM guild_data WHERE playerid='".$char["guid"]."'"), 0);
        $guild_rank = $sql["char"]->result($sql["char"]->query("SELECT guildRank FROM guild_data WHERE playerid='".$char["guid"]."'"), 0);
        $guild_name = $sql["char"]->result($sql["char"]->query("SELECT guildName FROM guilds WHERE guildid='".$guild_id."'"));
      }
      else
      {
        $guild_id = $sql["char"]->result($sql["char"]->query("SELECT guildid FROM guild_member WHERE guid='".$char["guid"]."'"), 0);
        $guild_rank = $sql["char"]->result($sql["char"]->query("SELECT rank AS guildRank FROM guild_member WHERE guid='".$char["guid"]."'"), 0);
        $guild_name = $sql["char"]->result($sql["char"]->query("SELECT name AS guildName FROM guild WHERE guildid='".$guild_id."'"));
      }

      $online = ( ( $char["online"] ) ? lang("char", "online") : lang("char", "offline") );

      if ( $guild_id )
      {
        //$guild_name = $sql["char"]->result($sql["char"]->query('SELECT name FROM guild WHERE guildid ='.$char_data[CHAR_DATA_OFFSET_GUILD_ID].''), 0, 'name');
        $guild_name = '<a href="guild.php?action=view_guild&amp;realm='.$realmid.'&amp;error=3&amp;id='.$guild_id.'" >'.$guild_name.'</a>';
        $mrank = $guild_rank;
        if ( $core == 1 )
          $guild_rank = $sql["char"]->result($sql["char"]->query("SELECT rankname FROM guild_ranks WHERE guildid='".$guild_id."' AND rankId='".$mrank."'"), 0, "rankname");
        else
          $guild_rank = $sql["char"]->result($sql["char"]->query("SELECT rname AS rankname FROM guild_rank WHERE guildid='".$guild_id."' AND rid='".$mrank."'"), 0, "rankname");
      }
      else
      {
        $guild_name = lang("global", "none");
        $guild_rank = lang("global", "none");
      }

      if ( $core == 1 )
      {
        $block           = unpack("f", pack("L", $char_data[PLAYER_BLOCK_PERCENTAGE]));
        $block           = round($block[1],2);
        $dodge           = unpack("f", pack("L", $char_data[PLAYER_DODGE_PERCENTAGE]));
        $dodge           = round($dodge[1],2);
        $parry           = unpack("f", pack("L", $char_data[PLAYER_PARRY_PERCENTAGE]));
        $parry           = round($parry[1],2);
        $crit            = unpack("f", pack("L", $char_data[PLAYER_CRIT_PERCENTAGE]));
        $crit            = round($crit[1],2);
        $ranged_crit     = unpack("f", pack("L", $char_data[PLAYER_RANGED_CRIT_PERCENTAGE]));
        $ranged_crit     = round($ranged_crit[1],2);
        $maxdamage       = unpack("f", pack("L", $char_data[UNIT_FIELD_MAXDAMAGE]));
        $maxdamage       = round($maxdamage[1],0);
        $mindamage       = unpack("f", pack("L", $char_data[UNIT_FIELD_MINDAMAGE]));
        $mindamage       = round($mindamage[1],0);
        $maxrangeddamage = unpack("f", pack("L", $char_data[UNIT_FIELD_MAXRANGEDDAMAGE]));
        $maxrangeddamage = round($maxrangeddamage[1],0);
        $minrangeddamage = unpack("f", pack("L", $char_data[UNIT_FIELD_MINRANGEDDAMAGE]));
        $minrangeddamage = round($minrangeddamage[1],0);
      }
      else
      {
        $block           = $char_data[PLAYER_BLOCK_PERCENTAGE];
        $block           = round($block,2);
        $dodge           = $char_data[PLAYER_DODGE_PERCENTAGE];
        $dodge           = round($dodge,2);
        $parry           = $char_data[PLAYER_PARRY_PERCENTAGE];
        $parry           = round($parry,2);
        $crit            = $char_data[PLAYER_CRIT_PERCENTAGE];
        $crit            = round($crit,2);
        $ranged_crit     = $char_data[PLAYER_RANGED_CRIT_PERCENTAGE];
        $ranged_crit     = round($ranged_crit,2);
        $maxdamage       = $char_data[UNIT_FIELD_MAXDAMAGE];
        $maxdamage       = round($maxdamage,0);
        $mindamage       = $char_data[UNIT_FIELD_MINDAMAGE];
        $mindamage       = round($mindamage,0);
        $maxrangeddamage = $char_data[UNIT_FIELD_MAXRANGEDDAMAGE];
        $maxrangeddamage = round($maxrangeddamage,0);
        $minrangeddamage = $char_data[UNIT_FIELD_MINRANGEDDAMAGE];
        $minrangeddamage = round($minrangeddamage,0);
      }

      if ( $core == 1 )
      {
        $spell_crit = 100;
        for ( $i=0; $i<6; ++$i )
        {
          $temp = unpack("f", pack("L", $char_data[PLAYER_SPELL_CRIT_PERCENTAGE1+1+$i]));
          if ( $temp[1] < $spell_crit )
            $spell_crit = $temp[1];
        }
        $spell_crit = round($spell_crit,2);
      }
      else
      {
        $spell_crit = $char_data[PLAYER_SPELL_CRIT_PERCENTAGE1];
        $spell_crit = round($spell_crit,2);
      }

      if ( $core == 1 )
      {
        $spell_damage = 9999;
        for ( $i=0; $i<6; ++$i )
        {
          if ( $char_data[PLAYER_FIELD_MOD_DAMAGE_DONE_POS+1+$i] < $spell_damage )
            $spell_damage = $char_data[PLAYER_FIELD_MOD_DAMAGE_DONE_POS+1+$i];
        }
      }
      else
      {
        $spell_damage = $char_data[PLAYER_FIELD_MOD_DAMAGE_DONE_POS];
      }

      $spell_heal = $char_data[PLAYER_FIELD_MOD_HEALING_DONE_POS];

      // this_is_junk: PLAYER_FIELD_COMBAT_RATING_1 +5, +6, and +7 seem to have the same value as +5
      //               I'm not sure which of these fields is which hit rating. :/
      $spell_hit = $char_data[PLAYER_FIELD_COMBAT_RATING_1+5];

      // this_is_junk: PLAYER_FIELD_COMBAT_RATING_1 +18 and +19 seem to have the same value as +5
      //               I'm not sure which of these fields is really spell haste. :/
      $spell_haste = $char_data[PLAYER_FIELD_COMBAT_RATING_1+17];

      // this_is_junk: PLAYER_FIELD_COMBAT_RATING_1 +5, +6, and +7 seem to have the same value as +5
      //               I'm not sure which of these fields is which hit rating. :/
      $ranged_hit = $char_data[PLAYER_FIELD_COMBAT_RATING_1+6];

      // this_is_junk: PLAYER_FIELD_COMBAT_RATING_1 +5, +6, and +7 seem to have the same value as +5
      //               I'm not sure which of these fields is which hit rating. :/
      $melee_hit = $char_data[PLAYER_FIELD_COMBAT_RATING_1+7];

      $expertise  = $char_data[PLAYER_EXPERTISE]." / ".$char_data[PLAYER_OFFHAND_EXPERTISE];

      //if ( $core == 1 )
      //{
        /*$EQU_HEAD      = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 0];
        $EQU_NECK      = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 2];
        $EQU_SHOULDER  = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 4];
        $EQU_SHIRT     = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 6];
        $EQU_CHEST     = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 8];
        $EQU_BELT      = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 10];
        $EQU_LEGS      = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 12];
        $EQU_FEET      = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 14];
        $EQU_WRIST     = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 16];
        $EQU_GLOVES    = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 18];
        $EQU_FINGER1   = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 20];
        $EQU_FINGER2   = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 22];
        $EQU_TRINKET1  = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 24];
        $EQU_TRINKET2  = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 26];
        $EQU_BACK      = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 28];
        $EQU_MAIN_HAND = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 30];
        $EQU_OFF_HAND  = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 32];
        $EQU_RANGED    = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 34];
        $EQU_TABARD    = $char_data[PLAYER_FIELD_INV_SLOT_HEAD + 36];*/
      //}
      //else
      //{
      $world_db_name = $world_db[$realm_id]["name"];

      if ( $core == 1 )
      {
        $char_equip_query = "SELECT *, 
          playeritems.entry AS item_template, randomprop as property, enchantments AS enchantment, flags
          FROM playeritems WHERE ownerguid='".$id."' AND containerslot=-1";
      }
      elseif ( $core == 2 )
      {
        $char_equip_query = "SELECT *,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 11), ' ', -1) AS creator,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 23), ' ', -1) AS enchantment,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 60), ' ', -1) AS property,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 62), ' ', -1) AS durability,
          SUBSTRING_INDEX(SUBSTRING_INDEX(item_instance.data, ' ', 22), ' ', -1) AS flags
          FROM character_inventory
            LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
          WHERE character_inventory.guid='".$id."' AND character_inventory.bag=0";
      }
      else
      {
        $char_equip_query = "SELECT *,
          creatorGuid AS creator, enchantments AS enchantment,
          randomPropertyId AS property, durability, flags,
          itemEntry AS item_template
          FROM character_inventory
            LEFT JOIN item_instance ON character_inventory.item=item_instance.guid
          WHERE character_inventory.guid='".$id."' AND character_inventory.bag=0";
      }

      $char_equip_result = $sql["char"]->query($char_equip_query);

      while ( $equip_row = $sql["char"]->fetch_assoc($char_equip_result) )
      {
        switch ( $equip_row["slot"] )
        {
          case 0:
          {
            $EQU_HEAD = $equip_row["item_template"];
            $EQU_HEAD_ROW = $equip_row;
            break;
          }
          case 1:
          {
            $EQU_NECK = $equip_row["item_template"];
            $EQU_NECK_ROW = $equip_row;
            break;
          }
          case 2:
          {
            $EQU_SHOULDER = $equip_row["item_template"];
            $EQU_SHOULDER_ROW = $equip_row;
            break;
          }
          case 3:
          {
            $EQU_SHIRT = $equip_row["item_template"];
            $EQU_SHIRT_ROW = $equip_row;
            break;
          }
          case 4:
          {
            $EQU_CHEST = $equip_row["item_template"];
            $EQU_CHEST_ROW = $equip_row;
            break;
          }
          case 5:
          {
            $EQU_BELT = $equip_row["item_template"];
            $EQU_BELT_ROW = $equip_row;
            break;
          }
          case 6:
          {
            $EQU_LEGS = $equip_row["item_template"];
            $EQU_LEGS_ROW = $equip_row;
            break;
          }
          case 7:
          {
            $EQU_FEET = $equip_row["item_template"];
            $EQU_FEET_ROW = $equip_row;
            break;
          }
          case 8:
          {
            $EQU_WRIST = $equip_row["item_template"];
            $EQU_WRIST_ROW = $equip_row;
            break;
          }
          case 9:
          {
            $EQU_GLOVES = $equip_row["item_template"];
            $EQU_GLOVES_ROW = $equip_row;
            break;
          }
          case 10:
          {
            $EQU_FINGER1 = $equip_row["item_template"];
            $EQU_FINGER1_ROW = $equip_row;
            break;
          }
          case 11:
          {
            $EQU_FINGER2 = $equip_row["item_template"];
            $EQU_FINGER2_ROW = $equip_row;
            break;
          }
          case 12:
          {
            $EQU_TRINKET1 = $equip_row["item_template"];
            $EQU_TRINKET1_ROW = $equip_row;
            break;
          }
          case 13:
          {
            $EQU_TRINKET2 = $equip_row["item_template"];
            $EQU_TRINKET2_ROW = $equip_row;
            break;
          }
          case 14:
          {
            $EQU_BACK = $equip_row["item_template"];
            $EQU_BACK_ROW = $equip_row;
            break;
          }
          case 15:
          {
            $EQU_MAIN_HAND = $equip_row["item_template"];
            $EQU_MAIN_HAND_ROW = $equip_row;
            break;
          }
          case 16:
          {
            $EQU_OFF_HAND = $equip_row["item_template"];
            $EQU_OFF_HAND_ROW = $equip_row;
            break;
          }
          case 17:
          {
            $EQU_RANGED = $equip_row["item_template"];
            $EQU_RANGED_ROW = $equip_row;
            break;
          }
          case 18:
          {
            $EQU_TABARD = $equip_row["item_template"];
            $EQU_TABARD_ROW = $equip_row;
            break;
          }
        }
      }
      //}

      $equiped_items = array
      (
         1 => array("", ( ( $EQU_HEAD )      ? get_item_icon($EQU_HEAD)      : 0 ), ( ( $EQU_HEAD )      ? get_item_border($EQU_HEAD)      : 0 ), $EQU_HEAD_ROW),
         2 => array("", ( ( $EQU_NECK )      ? get_item_icon($EQU_NECK)      : 0 ), ( ( $EQU_NECK )      ? get_item_border($EQU_NECK)      : 0 ), $EQU_NECK_ROW),
         3 => array("", ( ( $EQU_SHOULDER )  ? get_item_icon($EQU_SHOULDER)  : 0 ), ( ( $EQU_SHOULDER )  ? get_item_border($EQU_SHOULDER)  : 0 ), $EQU_SHOULDER_ROW),
         4 => array("", ( ( $EQU_SHIRT )     ? get_item_icon($EQU_SHIRT)     : 0 ), ( ( $EQU_SHIRT )     ? get_item_border($EQU_SHIRT)     : 0 ), $EQU_SHIRT_ROW),
         5 => array("", ( ( $EQU_CHEST )     ? get_item_icon($EQU_CHEST)     : 0 ), ( ( $EQU_CHEST )     ? get_item_border($EQU_CHEST)     : 0 ), $EQU_CHEST_ROW),
         6 => array("", ( ( $EQU_BELT )      ? get_item_icon($EQU_BELT)      : 0 ), ( ( $EQU_BELT )      ? get_item_border($EQU_BELT)      : 0 ), $EQU_BELT_ROW),
         7 => array("", ( ( $EQU_LEGS )      ? get_item_icon($EQU_LEGS)      : 0 ), ( ( $EQU_LEGS )      ? get_item_border($EQU_LEGS)      : 0 ), $EQU_LEGS_ROW),
         8 => array("", ( ( $EQU_FEET )      ? get_item_icon($EQU_FEET)      : 0 ), ( ( $EQU_FEET )      ? get_item_border($EQU_FEET)      : 0 ), $EQU_FEET_ROW),
         9 => array("", ( ( $EQU_WRIST )     ? get_item_icon($EQU_WRIST)     : 0 ), ( ( $EQU_WRIST )     ? get_item_border($EQU_WRIST)     : 0 ), $EQU_WRIST_ROW),
        10 => array("", ( ( $EQU_GLOVES )    ? get_item_icon($EQU_GLOVES)    : 0 ), ( ( $EQU_GLOVES )    ? get_item_border($EQU_GLOVES)    : 0 ), $EQU_GLOVES_ROW),
        11 => array("", ( ( $EQU_FINGER1 )   ? get_item_icon($EQU_FINGER1)   : 0 ), ( ( $EQU_FINGER1 )   ? get_item_border($EQU_FINGER1)   : 0 ), $EQU_FINGER1_ROW),
        12 => array("", ( ( $EQU_FINGER2 )   ? get_item_icon($EQU_FINGER2)   : 0 ), ( ( $EQU_FINGER2 )   ? get_item_border($EQU_FINGER2)   : 0 ), $EQU_FINGER2_ROW),
        13 => array("", ( ( $EQU_TRINKET1 )  ? get_item_icon($EQU_TRINKET1)  : 0 ), ( ( $EQU_TRINKET1 )  ? get_item_border($EQU_TRINKET1)  : 0 ), $EQU_TRINKET1_ROW),
        14 => array("", ( ( $EQU_TRINKET2 )  ? get_item_icon($EQU_TRINKET2)  : 0 ), ( ( $EQU_TRINKET2 )  ? get_item_border($EQU_TRINKET2)  : 0 ), $EQU_TRINKET2_ROW),
        15 => array("", ( ( $EQU_BACK )      ? get_item_icon($EQU_BACK)      : 0 ), ( ( $EQU_BACK )      ? get_item_border($EQU_BACK)      : 0 ), $EQU_BACK_ROW),
        16 => array("", ( ( $EQU_MAIN_HAND ) ? get_item_icon($EQU_MAIN_HAND) : 0 ), ( ( $EQU_MAIN_HAND ) ? get_item_border($EQU_MAIN_HAND) : 0 ), $EQU_MAIN_HAND_ROW),
        17 => array("", ( ( $EQU_OFF_HAND )  ? get_item_icon($EQU_OFF_HAND)  : 0 ), ( ( $EQU_OFF_HAND )  ? get_item_border($EQU_OFF_HAND)  : 0 ), $EQU_OFF_HAND_ROW),
        18 => array("", ( ( $EQU_RANGED )    ? get_item_icon($EQU_RANGED)    : 0 ), ( ( $EQU_RANGED )    ? get_item_border($EQU_RANGED)    : 0 ), $EQU_RANGED_ROW),
        19 => array("", ( ( $EQU_TABARD )    ? get_item_icon($EQU_TABARD)    : 0 ), ( ( $EQU_TABARD )    ? get_item_border($EQU_TABARD)    : 0 ), $EQU_TABARD_ROW)
      );

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

      $view_pets_override = false;
      if ( $s_fields["View_Mod_Pets"] > 0 )
      {
        if ( $s_fields["View_Mod_Pets"] == 1 )
          ;// TODO: Add friends limit
        elseif ( $s_fields["View_Mod_Pets"] == 2 )
        {
          // only registered users may view this tab
          if ( $user_lvl > -1 )
            $view_pets_override = true;
        }
      }
      else
      {
        if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
          $view_pets_override = true;
      }

      $view_rep_override = false;
      if ( $s_fields["View_Mod_Rep"] > 0 )
      {
        if ( $s_fields["View_Mod_Rep"] == 1 )
          ;// TODO: Add friends limit
        elseif ( $s_fields["View_Mod_Rep"] == 2 )
        {
          // only registered users may view this tab
          if ( $user_lvl > -1 )
            $view_rep_override = true;
        }
      }
      else
      {
        if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
          $view_rep_override = true;
      }

      $view_skill_override = false;
      if ( $s_fields["View_Mod_Skill"] > 0 )
      {
        if ( $s_fields["View_Mod_Skill"] == 1 )
          ;// TODO: Add friends limit
        elseif ( $s_fields["View_Mod_Skill"] == 2 )
        {
          // only registered users may view this tab
          if ( $user_lvl > -1 )
            $view_skill_override = true;
        }
      }
      else
      {
        if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
          $view_skill_override = true;
      }

      $view_pvp_override = false;
      if ( $s_fields["View_Mod_PvP"] > 0 )
      {
        if ( $s_fields["View_Mod_PvP"] == 1 )
          ;// TODO: Add friends limit
        elseif ( $s_fields["View_Mod_PvP"] == 2 )
        {
          // only registered users may view this tab
          if ( $user_lvl > -1 )
            $view_pvp_override = true;
        }
      }
      else
      {
        if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
          $view_pvp_override = true;
      }

      $output .= '
          <!-- start of char.php -->
          <div class="tab">
            <ul>
              <li class="selected"><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "char_sheet").'</a></li>';

      if ( $view_inv_override )
        $output .= '
              <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "inventory").'</a></li>';

      if ( $view_talent_override )
        $output .= '
              '.( ( $char["level"] < 10 ) ? '' : '<li><a href="char_talent.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "talents").'</a></li>' ).'';

      if ( $view_achieve_override )
        $output .= '
              <li><a href="char_achieve.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "achievements").'</a></li>';

      if ( $view_quest_override )
        $output .= '
              <li><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "quests").'</a></li>';

      if ( $view_friends_override )
        $output .= '
              <li><a href="char_friends.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "friends").'</a></li>';

      if ( $view_view_override )
        $output .= '
              <li><a href="char_view.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "view").'</a></li>';

      $output .= '
            </ul>
          </div>';

      if ( ( $view_override ) || ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
      {
        $output .= '
          <div class="tab_content center">
            <div class="tab">
              <ul>
                <li class="selected"><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "char_sheet").'</a></li>';

        if ( ( char_get_class_name($char["class"]) === "Hunter" ) && ( $view_pets_override ) )
          $output .= '
                <li><a href="char_pets.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "pets").'</a></li>';

        if ( $view_rep_override )
          $output .= '
                <li><a href="char_rep.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "reputation").'</a></li>';

        if ( $view_skill_override )
          $output .= '
                <li><a href="char_skill.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "skills").'</a></li>';

        if ( $view_pvp_override )
          $output .= '
                <li><a href="char_pvp.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "pvp").'</a></li>';

        if ( ( $owner_name == $user_name ) || ( $user_lvl >= get_page_permission("insert", "char_mail.php") ) )
          $output .= '
                <li><a href="char_mail.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "mail").'</a></li>';

        $output .= '
              </ul>
            </div>';
      }
      else
        $output .= '
            <div class="tab_content center">
              <div class="tab">
              </div>';

      $output .= '
              <div class="tab_content2 center">
                <table class="lined" id="char_character_sheet">
                  <tr>
                    <td colspan="2">
                      <div>
                        <img src="'.char_get_avatar_img($char["level"], $char["gender"], $char["race"], $char["class"], 0).'" alt="avatar" />
                      </div>
                      <div>';
      // this_is_junk: auras are stored in a string in the characters table.
      // not sure how to query a string as though it were a record
      if ( $core == 1 )
        ;
      else
        $a_results = $sql["char"]->query("SELECT DISTINCT spell FROM character_aura WHERE guid='".$id."'");

      if ( $sql["char"]->num_rows($a_results) )
      {
        while ( $aura = $sql["char"]->fetch_assoc($a_results) )
        {
                 $output .= '
                        <a class="char_icon_padding" href="'.$base_datasite.$spell_datasite.$aura["spell"].'" rel="external">
                          <img src="'.spell_get_icon($aura["spell"]).'" alt="'.$aura["spell"].'" width="24" height="24" />
                        </a>';
        }
      }
      $output .= '
                      </div>
                    </td>
                    <td colspan="4">
                      <span class="bold">
                        '.htmlentities($char["name"], ENT_COMPAT, $site_encoding).' -
                        <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                        <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                       - '.lang("char", "level_short").char_get_level_color($char["level"]).'
                      </span>
                      <br />'.lang("char", "location").': '.get_map_name($char["mapid"]).' - '.get_zone_name($char["zoneid"]).'
                      <br />'.lang("char", "honor_points").': '.$char_data[PLAYER_FIELD_HONOR_CURRENCY].' | '.lang("char", "arena_points").': '.$char_data[PLAYER_FIELD_ARENA_CURRENCY].' | '.lang("char", "honor_kills").': '.$char_data[PLAYER_FIELD_LIFETIME_HONORBALE_KILLS].'
                      <br />'.lang("char", "guild").': '.$guild_name.' | '.lang("char", "rank").': '.htmlentities($guild_rank, ENT_COMPAT, $site_encoding).'
                      <br />'.lang("char", "online").': '.( ( $char["online"] ) ? '<img src="img/up.gif" onmousemove="oldtoolTip(\''.lang("char", "online").'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="online" />' : '<img src="img/down.gif" onmousemove="oldtoolTip(\''.lang("char", "offline").'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="offline" />' );
      if ( $showcountryflag )
      {
        require_once 'libs/misc_lib.php';
        $country = misc_get_country_by_account($char["acct"]);
        $output .= ' | '.lang("global", "country").': '.( ( $country["code"] ) ? '<img src="img/flags/'.$country["code"].'.png" onmousemove="oldtoolTip(\''.($country["country"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />' : '-' );
        unset($country);
      }
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 6%;">';
      if ( $equiped_items[1][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_HEAD.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'HEAD'.'\');" onmouseout="HideTooltip(\'_b'.'HEAD'.'\');">
                        <img src="'.$equiped_items[1][1].'" class="'.$equiped_items[1][2].'" alt="Head" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[1][3]["bag"].'&slot='.$equiped_items[1][3]["slot"].'&item='.$equiped_items[1][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[1][3]["item_template"]);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'HEAD'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[1][3]["enchantment"], $equiped_items[1][3]["property"], $equiped_items[1][3]["creator"], $equiped_items[1][3]["durability"], $equiped_items[1][3]["flags"]).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_head.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" align="center" style="width: 50%;">
                      <div class="gradient_p" id="char_hp_name">'.lang("item", "health").':</div>
                      <div class="gradient_pp" id="char_hp_value">'.$char_data[UNIT_FIELD_HEALTH].'/'.$char_data[UNIT_FIELD_MAXHEALTH].'</div>';
      if ( $char["class"] == 11 ) //druid
        $output .= '
                      <br />
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "mana").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.$char_data[UNIT_FIELD_POWER1].'/'.$char_data[UNIT_FIELD_MAXPOWER1].'</div>';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" align="center" style="width: 50%;">';
      if ( $char["class"] == 1 ) // warrior
      {
        $output .= '
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "rage").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.($char_data[UNIT_FIELD_POWER2]/10).'/'.($char_data[UNIT_FIELD_MAXPOWER2]/10).'</div>';
      }
      elseif ( $char["class"] == 4 ) // rogue
      {
        $output .= '
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "energy").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.$char_data[UNIT_FIELD_POWER4].'/'.$char_data[UNIT_FIELD_MAXPOWER4].'</div>';
      }
      elseif ( $char["class"] == 6 ) // death knight
      {
        $output .= '
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "runic").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.($char_data[UNIT_FIELD_POWER7]/10).'/'.($char_data[UNIT_FIELD_MAXPOWER7]/10).'</div>';
      }
      elseif ( $char["class"] == 11 ) // druid
      {
        $output .= '
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "mana").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.$char_data[UNIT_FIELD_POWER1].'/'.$char_data[UNIT_FIELD_MAXPOWER1].'</div>
                      <br />
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "rage").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.($char_data[UNIT_FIELD_POWER2]/10).'/'.($char_data[UNIT_FIELD_MAXPOWER2]/10).'</div>';
      }
      elseif ( ( $char["class"] == 2 ) || // paladin
               ( $char["class"] == 3 ) || // hunter
               ( $char["class"] == 5 ) || // priest
               ( $char["class"] == 7 ) || // shaman
               ( $char["class"] == 8 ) || // mage
               ( $char["class"] == 9 ) )  // warlock
      {
        $output .= '
                      <div class="gradient_p" id="char_energy_name">'.lang("item", "mana").':</div>
                      <div class="gradient_pp" id="char_energy_value">'.$char_data[UNIT_FIELD_POWER1].'/'.$char_data[UNIT_FIELD_MAXPOWER1].'</div>';
      }
      $output .= '
                    </td>
                    <td style="width: 6%;">';
      if ( $equiped_items[10][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_GLOVES.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'GLOVES'.'\');" onmouseout="HideTooltip(\'_b'.'GLOVES'.'\');">
                        <img src="'.$equiped_items[10][1].'" class="'.$equiped_items[10][2].'" alt="Gloves" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[10][3]["bag"].'&slot='.$equiped_items[10][3]["slot"].'&item='.$equiped_items[10][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[10][3]["item_template"]);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'GLOVES'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[10][3]["enchantment"], $equiped_items[10][3]["property"], $equiped_items[10][3]["creator"], $equiped_items[10][3]["durability"], $equiped_items[10][3]["flags"]).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_gloves.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[2][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_NECK.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'NECK'.'\');" onmouseout="HideTooltip(\'_b'.'NECK'.'\');">
                        <img src="'.$equiped_items[2][1].'" class="'.$equiped_items[2][2].'" alt="Neck" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[2][3]["bag"].'&slot='.$equiped_items[2][3]["slot"].'&item='.$equiped_items[2][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[2][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'NECK'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[2][3]['enchantment'], $equiped_items[2][3]['property'], $equiped_items[2][3]['creator'], $equiped_items[2][3]['durability'], $equiped_items[2][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_neck.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" rowspan="3" align="center" style="width: 50%;">
                      <div class="gradient_p">
                        '.lang("item", "strength").':<br />
                        '.lang("item", "agility").':<br />
                        '.lang("item", "stamina").':<br />
                        '.lang("item", "intellect").':<br />
                        '.lang("item", "spirit").':<br />
                        '.lang("item", "armor").':
                      </div>
                      <div class="gradient_pp">
                        '.$char_data[UNIT_FIELD_STAT0].'<br />
                        '.$char_data[UNIT_FIELD_STAT1].'<br />
                        '.$char_data[UNIT_FIELD_STAT2].'<br />
                        '.$char_data[UNIT_FIELD_STAT3].'<br />
                        '.$char_data[UNIT_FIELD_STAT4].'<br />
                        '.$char_data[UNIT_FIELD_RESISTANCES].'
                      </div>
                    </td>
                    <td class="half_line" colspan="2" rowspan="3" align="center" style="width: 50%;">
                      <div class="gradient_p">
                        '.lang("item", "res_holy").':<br />
                        '.lang("item", "res_arcane").':<br />
                        '.lang("item", "res_fire").':<br />
                        '.lang("item", "res_nature").':<br />
                        '.lang("item", "res_frost").':<br />
                        '.lang("item", "res_shadow").':
                      </div>
                      <div class="gradient_pp">
                        '.$char_data[UNIT_FIELD_RESISTANCES + 1].'<br />
                        '.$char_data[UNIT_FIELD_RESISTANCES + 2].'<br />
                        '.$char_data[UNIT_FIELD_RESISTANCES + 3].'<br />
                        '.$char_data[UNIT_FIELD_RESISTANCES + 4].'<br />
                        '.$char_data[UNIT_FIELD_RESISTANCES + 5].'<br />
                        '.$char_data[UNIT_FIELD_RESISTANCES + 6].'
                      </div>
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[6][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_BELT.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'BELT'.'\');" onmouseout="HideTooltip(\'_b'.'BELT'.'\');">
                        <img src="'.$equiped_items[6][1].'" class="'.$equiped_items[6][2].'" alt="Belt" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[6][3]["bag"].'&slot='.$equiped_items[6][3]["slot"].'&item='.$equiped_items[6][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[6][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'BELT'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[6][3]['enchantment'], $equiped_items[6][3]['property'], $equiped_items[6][3]['creator'], $equiped_items[6][3]['durability'], $equiped_items[6][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_waist.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[3][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_SHOULDER.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'SHOULDER'.'\');" onmouseout="HideTooltip(\'_b'.'SHOULDER'.'\');">
                        <img src="'.$equiped_items[3][1].'" class="'.$equiped_items[3][2].'" alt="Shoulder" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[3][3]["bag"].'&slot='.$equiped_items[3][3]["slot"].'&item='.$equiped_items[3][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[3][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'SHOULDER'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[3][3]['enchantment'], $equiped_items[3][3]['property'], $equiped_items[3][3]['creator'], $equiped_items[3][3]['durability'], $equiped_items[3][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_shoulder.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[7][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_LEGS.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'LEGS'.'\');" onmouseout="HideTooltip(\'_b'.'LEGS'.'\');">
                        <img src="'.$equiped_items[7][1].'" class="'.$equiped_items[7][2].'" alt="Legs" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[7][3]["bag"].'&slot='.$equiped_items[7][3]["slot"].'&item='.$equiped_items[7][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[7][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'LEGS'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[7][3]['enchantment'], $equiped_items[7][3]['property'], $equiped_items[7][3]['creator'], $equiped_items[7][3]['durability'], $equiped_items[7][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_legs.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[15][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_BACK.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'BACK'.'\');" onmouseout="HideTooltip(\'_b'.'BACK'.'\');">
                        <img src="'.$equiped_items[15][1].'" class="'.$equiped_items[15][2].'" alt="Back" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[15][3]["bag"].'&slot='.$equiped_items[15][3]["slot"].'&item='.$equiped_items[15][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[15][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'BACK'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[15][3]['enchantment'], $equiped_items[15][3]['property'], $equiped_items[15][3]['creator'], $equiped_items[15][3]['durability'], $equiped_items[15][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_chest_back.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[8][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_FEET.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'FEET'.'\');" onmouseout="HideTooltip(\'_b'.'FEET'.'\');">
                        <img src="'.$equiped_items[8][1].'" class="'.$equiped_items[8][2].'" alt="Feet" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[8][3]["bag"].'&slot='.$equiped_items[8][3]["slot"].'&item='.$equiped_items[8][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[8][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'FEET'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[8][3]['enchantment'], $equiped_items[8][3]['property'], $equiped_items[8][3]['creator'], $equiped_items[8][3]['durability'], $equiped_items[8][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_feet.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[5][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_CHEST.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'CHEST'.'\');" onmouseout="HideTooltip(\'_b'.'CHEST'.'\');">
                        <img src="'.$equiped_items[5][1].'" class="'.$equiped_items[5][2].'" alt="Chest" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[5][3]["bag"].'&slot='.$equiped_items[5][3]["slot"].'&item='.$equiped_items[5][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[5][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'CHEST'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[5][3]['enchantment'], $equiped_items[5][3]['property'], $equiped_items[5][3]['creator'], $equiped_items[5][3]['durability'], $equiped_items[5][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_chest_back.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" style="width: 50%;">
                      <div class="gradient_p" id="char_melee_name">
                        '.lang("char", "melee_d").':<br />
                        '.lang("char", "melee_ap").':<br />
                        '.lang("char", "melee_hit").':<br />
                        '.lang("char", "melee_crit").':<br />
                        '.lang("char", "expertise").':<br />
                      </div>
                      <div class="gradient_pp" id="char_melee_value">
                        '.$mindamage.'-'.$maxdamage.'<br />
                        '.($char_data[UNIT_FIELD_ATTACK_POWER]+$char_data[UNIT_FIELD_ATTACK_POWER_MODS]).'<br />
                        '.$melee_hit.'<br />
                        '.$crit.'%<br />
                        '.$expertise.'<br />
                      </div>
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" style="width: 50%;">
                      <div class="gradient_p">
                        '.lang("char", "spell_d").':<br />
                        '.lang("char", "spell_heal").':<br />
                        '.lang("char", "spell_hit").':<br />
                        '.lang("char", "spell_crit").':<br />
                        '.lang("char", "spell_haste").'
                      </div>
                      <div class="gradient_pp">
                        '.$spell_damage.'<br />
                        '.$spell_heal.'<br />
                        '.$spell_hit.'<br />
                        '.$spell_crit.'%<br />
                        '.$spell_haste.'
                      </div>
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[11][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_FINGER1.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'FINGER1'.'\');" onmouseout="HideTooltip(\'_b'.'FINGER1'.'\');">
                        <img src="'.$equiped_items[11][1].'" class="'.$equiped_items[11][2].'" alt="Finger1" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[11][3]["bag"].'&slot='.$equiped_items[11][3]["slot"].'&item='.$equiped_items[11][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[11][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'FINGER1'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[11][3]['enchantment'], $equiped_items[11][3]['property'], $equiped_items[11][3]['creator'], $equiped_items[11][3]['durability'], $equiped_items[11][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_finger.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[4][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_SHIRT.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'SHIRT'.'\');" onmouseout="HideTooltip(\'_b'.'SHIRT'.'\');">
                        <img src="'.$equiped_items[4][1].'" class="'.$equiped_items[4][2].'" alt="Shirt" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[4][3]["bag"].'&slot='.$equiped_items[4][3]["slot"].'&item='.$equiped_items[4][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[4][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'SHIRT'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[4][3]['enchantment'], $equiped_items[4][3]['property'], $equiped_items[4][3]['creator'], $equiped_items[4][3]['durability'], $equiped_items[4][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_shirt.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[12][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_FINGER2.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'FINGER2'.'\');" onmouseout="HideTooltip(\'_b'.'FINGER2'.'\');">
                        <img src="'.$equiped_items[12][1].'" class="'.$equiped_items[12][2].'" alt="Finger2" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[12][3]["bag"].'&slot='.$equiped_items[12][3]["slot"].'&item='.$equiped_items[12][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[12][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'FINGER2'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[12][3]['enchantment'], $equiped_items[12][3]['property'], $equiped_items[12][3]['creator'], $equiped_items[12][3]['durability'], $equiped_items[12][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else $output .= '
                      <img src="img/INV/INV_empty_finger.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[19][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_TABARD.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'TABARD'.'\');" onmouseout="HideTooltip(\'_b'.'TABARD'.'\');">
                        <img src="'.$equiped_items[19][1].'" class="'.$equiped_items[19][2].'" alt="Tabard" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[19][3]["bag"].'&slot='.$equiped_items[19][3]["slot"].'&item='.$equiped_items[19][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[19][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'TABARD'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[19][3]['enchantment'], $equiped_items[19][3]['property'], $equiped_items[19][3]['creator'], $equiped_items[19][3]['durability'], $equiped_items[19][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else $output .= '
                      <img src="img/INV/INV_empty_tabard.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" style="width: 50%;">
                      <div class="gradient_p">
                        '.lang("char", "dodge").':<br />
                        '.lang("char", "parry").':<br />
                        '.lang("char", "block").':
                      </div>
                      <div class="gradient_pp">
                        '.$dodge.'%<br />
                        '.$parry.'%<br />
                        '.$block.'%
                      </div>
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" style="width: 50%;">
                      <div class="gradient_p" id="char_ranged_name">
                        '.lang("char", "ranged_d").':<br />
                        '.lang("char", "ranged_ap").':<br />
                        '.lang("char", "ranged_hit").':<br />
                        '.lang("char", "ranged_crit").':<br />
                      </div>
                      <div class="gradient_pp" id="char_ranged_value">
                        '.$minrangeddamage.'-'.$maxrangeddamage.'<br />
                        '.($char_data[UNIT_FIELD_RANGED_ATTACK_POWER]+$char_data[UNIT_FIELD_RANGED_ATTACK_POWER_MODS]).'<br />
                        '.$ranged_hit.'<br />
                        '.$ranged_crit.'%<br />
                      </div>
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[13][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_TRINKET1.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'TRINKET1'.'\');" onmouseout="HideTooltip(\'_b'.'TRINKET1'.'\');">
                        <img src="'.$equiped_items[13][1].'" class="'.$equiped_items[13][2].'" alt="Trinket1" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[13][3]["bag"].'&slot='.$equiped_items[13][3]["slot"].'&item='.$equiped_items[13][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[13][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'TRINKET1'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[13][3]['enchantment'], $equiped_items[13][3]['property'], $equiped_items[13][3]['creator'], $equiped_items[13][3]['durability'], $equiped_items[13][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_trinket.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td style="width: 1%;">';
      if ( $equiped_items[9][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_WRIST.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'WRIST'.'\');" onmouseout="HideTooltip(\'_b'.'WRIST'.'\');">
                        <img src="'.$equiped_items[9][1].'" class="'.$equiped_items[9][2].'" alt="Wrist" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[9][3]["bag"].'&slot='.$equiped_items[9][3]["slot"].'&item='.$equiped_items[9][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[9][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'WRIST'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[9][3]['enchantment'], $equiped_items[9][3]['property'], $equiped_items[9][3]['creator'], $equiped_items[9][3]['durability'], $equiped_items[9][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_wrist.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 1%;">';
      if ( $equiped_items[14][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_TRINKET2.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'TRINKET2'.'\');" onmouseout="HideTooltip(\'_b'.'TRINKET2'.'\');">
                        <img src="'.$equiped_items[14][1].'" class="'.$equiped_items[14][2].'" alt="Trinket2" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[14][3]["bag"].'&slot='.$equiped_items[14][3]["slot"].'&item='.$equiped_items[14][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[14][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'TRINKET2'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[14][3]['enchantment'], $equiped_items[14][3]['property'], $equiped_items[14][3]['creator'], $equiped_items[14][3]['durability'], $equiped_items[14][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_trinket.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td></td>
                    <td style="width: 15%;">';
      if ( $equiped_items[16][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_MAIN_HAND.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'MAIN_HAND'.'\');" onmouseout="HideTooltip(\'_b'.'MAIN_HAND'.'\');">
                        <img src="'.$equiped_items[16][1].'" class="'.$equiped_items[16][2].'" alt="MainHand" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[16][3]["bag"].'&slot='.$equiped_items[16][3]["slot"].'&item='.$equiped_items[16][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[16][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'MAIN_HAND'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[16][3]['enchantment'], $equiped_items[16][3]['property'], $equiped_items[16][3]['creator'], $equiped_items[16][3]['durability'], $equiped_items[16][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_main_hand.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 15%;">';
      if ( $equiped_items[17][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_OFF_HAND.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'OFF_HAND'.'\');" onmouseout="HideTooltip(\'_b'.'OFF_HAND'.'\');">
                        <img src="'.$equiped_items[17][1].'" class="'.$equiped_items[17][2].'" alt="OffHand" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[17][3]["bag"].'&slot='.$equiped_items[17][3]["slot"].'&item='.$equiped_items[17][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[17][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'OFF_HAND'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[17][3]['enchantment'], $equiped_items[17][3]['property'], $equiped_items[17][3]['creator'], $equiped_items[17][3]['durability'], $equiped_items[17][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_off_hand.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 15%;">';
      if ( $equiped_items[18][1] )
      {
        $output .= '
                      <a class="char_icon_padding" href="'.$base_datasite.$item_datasite.$EQU_RANGED.'" rel="external" onmouseover="ShowTooltip(this,\'_b'.'RANGED'.'\');" onmouseout="HideTooltip(\'_b'.'RANGED'.'\');">
                        <img src="'.$equiped_items[18][1].'" class="'.$equiped_items[18][2].'" alt="Ranged" />
                      </a>';

        if ( $mode )
          $output .= '
                      <div style="position: relative;">
                        <a href="char.php?action=delete_item&id='.$id.'&bag='.$equiped_items[18][3]["bag"].'&slot='.$equiped_items[18][3]["slot"].'&item='.$equiped_items[18][3]["item_template"].'&mode='.$mode.'" id="ch_item_delete">
                          <img src="img/aff_cross.png" />
                        </a>
                      </div>';

        // build a tooltip object for this item
        $i_fields = get_item_info($equiped_items[18][3]['item_template']);

        $output .= '
                      <div class="item_tooltip" id="tooltip_b'.'RANGED'.'">
                        <table>
                          <tr>
                            <td>
                              '.get_item_tooltip($i_fields, $equiped_items[18][3]['enchantment'], $equiped_items[18][3]['property'], $equiped_items[18][3]['creator'], $equiped_items[18][3]['durability'], $equiped_items[18][3]['flags']).'
                            </td>
                          </tr>
                        </table>
                      </div>';
      }
      else
        $output .= '
                      <img src="img/INV/INV_empty_ranged.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td style="width: 15%;"></td>
                    <td></td>
                  </tr>';
      if ( ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
      {
        // if the character is still leveling, show an experience bar
        if ( $char["level"] < 80 )
        {
          $xp_query = "SELECT * FROM xp_to_level WHERE level='".$char["level"]."'";
          $xp_result = $sql["mgr"]->query($xp_query);
          $xp_fields = $sql["mgr"]->fetch_assoc($xp_result);
          $xp_to_level = $xp_fields["xp_for_next_level"];
          
          $output .= '
                    <tr>
                      <td colspan="6" class="bar xp_bar" style="background-position: '.(round(580*$char["xp"]/$xp_to_level)-580).'px;">
                        '.lang("char", "exp").": ".$char["xp"]." / ".$xp_to_level.'
                      </td>
                    </tr>';
        }

        //total time played
        $tot_time = $char["totaltime"];
        $tot_days = (int)($tot_time/86400);
        $tot_time = $tot_time - ($tot_days*86400);
        $total_hours = (int)($tot_time/3600);
        $tot_time = $tot_time - ($total_hours*3600);
        $total_min = (int)($tot_time/60);
      
        $time_offset = $timezone_offset * 3600;
      
        if ( $char["timestamp"] <> 0 )
          $lastseen = date("F j, Y @ Hi", $char["timestamp"] + $time_offset);
        else
          $lastseen = '-';

        $output .= '
                  <tr>
                    <td colspan="6">
                      '.lang("char", "tot_play_time").': '.$tot_days.' '.lang("char", "days").' '.$total_hours.' '.lang("char", "hours").' '.$total_min.' '.lang("char", "min").'
                    </td>
                  </tr>';

        $output .= '
                  <tr>
                    <td colspan="6">
                      '.lang("char", "lastseen").': '.$lastseen.'
                    </td>
                  </tr>';
      }
      $output .= '
                </table>
              </div>
              <br />
            </div>
            <br />
            <table class="hidden center">
              <tr>
                <td>';
      // button to user account page, user account page has own security
      makebutton(lang("char", "chars_acc"), 'user.php?action=edit_user&amp;acct='.$owner_acc_id.'', 130);
      $output .= '
                </td>
                <td>';

      // only higher level GM with delete access can edit character
      //  character edit allows removal of character items, so delete permission is needed
      if ( ( $user_lvl >= $owner_gmlvl ) && ( $user_lvl >= $action_permission["delete"] ) )
      {
        makebutton(lang("char", "edit_button"), 'char_edit.php?id='.$id.'&amp;realm='.$realmid, 130);
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
      // show Delete Mode / View Mode button depending on current mode
      if ( $mode )
        makebutton(lang("char", "viewmode"), 'char.php?id='.$id.'&amp;realm='.$realmid.'&amp;mode=0" type="def', 130);
      else
        makebutton(lang("char", "deletemode"), 'char.php?id='.$id.'&amp;realm='.$realmid.'&amp;mode=1" type="def', 130);
      $output .= '
                </td>
                <td>';
      // only GM with update permission can send mail, mail can send items, so update permission is needed
      if ( $user_lvl >= $action_permission["update"] )
      {
        makebutton(lang("char", "send_mail"), 'mail.php?type=ingame_mail&amp;to='.$char["name"], 130);
        $output .= '
                </td>';
      }
      else
      {
        $output .= '
                </td>';
      }
      $output .= '
              </tr>
              <tr>
                <td>';
      makebutton(lang("global", "back"), 'javascript:window.history.back()" type="def', 130);
      $output .= '
                </td>
              </tr>
            </table>
            <br />
          <!-- end of char.php -->';
    }
    else
      ;//error($lang_char["no_permission"]);
  }
  else
    error(lang("char", "no_char_found"));

}

function get_item_info($item)
{
  global $sql, $core, $locales_search_option;

  if ( $core == 1 )
    $i_query = "SELECT 
      *, name1 AS name, quality AS Quality, inventorytype AS InventoryType, 
      socket_color_1 AS socketColor_1, socket_color_2 AS socketColor_2, socket_color_3 AS socketColor_3,
      requiredlevel AS RequiredLevel, allowableclass AS AllowableClass,
      sellprice AS SellPrice, itemlevel AS ItemLevel
      FROM items "
      .( ( $locales_search_option != 0 ) ? "LEFT JOIN items_localized ON (items_localized.entry=items.entry AND language_code='".$locales_search_option."') " : " " ).
      "WHERE items.entry='".$item."'";
  else
    $i_query = "SELECT * FROM item_template "
          .( ( $locales_search_option != 0 ) ? "LEFT JOIN locales_item ON locales_item.entry=item_template.entry " : " " ).
        "WHERE item_template.entry='".$item."'";

  $i_result = $sql["world"]->query($i_query);
  $i_fields = $sql["world"]->fetch_assoc($i_result);

  return $i_fields;
}


//#############################################################################
// DELETE ITEM FORM
//#############################################################################
function delete_item()
{
  global $output, $action_permission;

  valid_login($action_permission["delete"]);

  $output .= '
          <div id="ch_item_delete_wrap" class="center center_text">
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
            <br />
            <br />
            <table id="ch_item_delete_table" class="hidden center">
              <tr>
                <td>';
  makebutton(lang("global", "yes"), 'char.php?action=dodelete_item&amp;id='.$_GET["id"].'&amp;bag='.$_GET["bag"].'&amp;slot='.$_GET["slot"].'&amp;item='.$_GET["item"].'&amp;mode='.$_GET["mode"].'" type="wrn', 130);
  makebutton(lang("global", "no"), 'char.php?id='.$_GET["id"].'&amp;mode='.$_GET["mode"].'" type="def', 130);
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
  $cid = ( ( isset($_GET["id"]) ) ? $sql["char"]->quote_smart($_GET["id"]) : NULL );
  $bag = ( ( isset($_GET["bag"]) ) ? $sql["char"]->quote_smart($_GET["bag"]) : NULL );
  $slot = ( ( isset($_GET["slot"]) ) ? $sql["char"]->quote_smart($_GET["slot"]) : NULL );
  $item = ( ( isset($_GET["item"]) ) ? $sql["char"]->quote_smart($_GET["item"]) : NULL );

  if ( ( !isset($cid) ) || ( !isset($bag) ) || ( !isset($slot) ) || ( !isset($item) ) )
    redirect("index.php");

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

  redirect("char.php?id=".$cid."&mode=".$_GET["mode"]);
}


//########################################################################################################################
// MAIN
//########################################################################################################################

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

$output .= '
      <div class="bubble">';

if ( $action == "delete_item" )
  delete_item();
elseif ( $action == "dodelete_item" )
  dodelete_item();
else
  char_main();

unset($action_permission);

require_once "footer.php";


?>
