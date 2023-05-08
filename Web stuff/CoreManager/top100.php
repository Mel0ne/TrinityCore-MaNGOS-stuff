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

function top100($realm_id)
{
  global $output, $logon_db, $characters_db, $dbc_db, $server, $itemperpage, $developer_test_mode,
    $multi_realm_mode, $sql, $core, $site_encoding, $n_realms;

  $sql["char"]->connect($characters_db[$realm_id]["addr"], $characters_db[$realm_id]["user"], $characters_db[$realm_id]["pass"], $characters_db[$realm_id]["name"], $characters_db[$realm_id]["encoding"]);

  //==========================$_GET and SECURE========================
  $type = ( (  isset($_GET["type"])) ? $sql["char"]->quote_smart($_GET["type"]) : "level" );
  if ( !preg_match("/^[_[:lower:]]{1,10}$/", $type) )
    $type = "level";

  $start = ( ( isset($_GET["start"]) ) ? $sql["char"]->quote_smart($_GET["start"]) : 0 );
  if ( !is_numeric($start) )
    $start = 0;

  $order_by = ( (  isset($_GET["order_by"]) ) ? $sql["char"]->quote_smart($_GET["order_by"]) : "level" );
  if ( !preg_match("/^[_[:lower:]]{1,14}$/", $order_by) )
    $order_by = "level";

  // Top 100 should sort DESC by default...
  $dir = ( ( isset($_GET["dir"]) ) ? $sql["char"]->quote_smart($_GET["dir"]) : 0 );
  if ( !preg_match("/^[01]{1}$/", $dir) )
    $dir = 1;

  $order_dir = ( ( $dir ) ? "ASC" : "DESC" );
  $dir = ( ( $dir ) ? 0 : 1 );
  //==========================$_GET and SECURE end========================

  $type_list = array("level", "stat", "defense", "attack", "resist", "crit_hit", "pvp");
  if ( !in_array($type, $type_list) )
    $type = "level";

  $result = $sql["char"]->query("SELECT count(*) FROM characters");
  $all_record = $sql["char"]->result($result, 0);
  $all_record = ( ( $all_record < 100 ) ? $all_record : 100 );

  if ( $core == 1)
  {
    // this_is_junk: rage and runic are both stored *10
    $result = $sql["char"]->query("SELECT guid, name, race, class, gender, level, online, gold,
      SUBSTRING_INDEX(SUBSTRING_INDEX(playedtime, ' ', 2), ' ', -1) AS totaltime,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_GUILDID+1)."), ';', -1) AS UNSIGNED) AS gname,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXHEALTH+1)."), ';', -1) AS UNSIGNED) AS health,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXPOWER1+1)."), ';', -1) AS UNSIGNED) AS mana,
     (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXPOWER2+1)."), ';', -1) AS UNSIGNED) DIV 10) AS rage,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXPOWER4+1)."), ';', -1) AS UNSIGNED) AS energy,
     (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXPOWER7+1)."), ';', -1) AS UNSIGNED) DIV 10) AS runic,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_STAT0+1)."), ';', -1) AS UNSIGNED) AS str,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_STAT1+1)."), ';', -1) AS UNSIGNED) AS agi,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_STAT2+1)."), ';', -1) AS UNSIGNED) AS sta,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_STAT3+1)."), ';', -1) AS UNSIGNED) AS intel,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_STAT4+1)."), ';', -1) AS UNSIGNED) AS spi,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+1)."), ';', -1) AS UNSIGNED) AS armor,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_BLOCK_PERCENTAGE+1)."), ';', -1) AS UNSIGNED) AS block,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_DODGE_PERCENTAGE+1)."), ';', -1) AS UNSIGNED) AS dodge,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_PARRY_PERCENTAGE+1)."), ';', -1) AS UNSIGNED) AS parry,
     (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_ATTACK_POWER+1)."), ';', -1) AS UNSIGNED)
    + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_ATTACK_POWER_MODS+1)."), ';', -1) AS UNSIGNED)) AS ap,
     (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RANGED_ATTACK_POWER+1)."), ';', -1) AS UNSIGNED)
    + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RANGED_ATTACK_POWER_MODS+1)."), ';', -1) AS UNSIGNED)) AS ranged_ap,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MINDAMAGE+1)."), ';', -1) AS UNSIGNED) AS min_dmg,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXDAMAGE+1)."), ';', -1) AS UNSIGNED) AS max_dmg,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MINRANGEDDAMAGE+1)."), ';', -1) AS UNSIGNED) AS min_ranged_dmg,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_MAXRANGEDDAMAGE+1)."), ';', -1) AS UNSIGNED) AS max_ranged_dmg,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_EXPERTISE+1)."), ';', -1) AS UNSIGNED) AS expertise,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_OFFHAND_EXPERTISE+1)."), ';', -1) AS UNSIGNED) AS off_expertise,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+1+1)."), ';', -1) AS UNSIGNED) AS holy,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+2+1)."), ';', -1) AS UNSIGNED) AS fire,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+3+1)."), ';', -1) AS UNSIGNED) AS nature,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+4+1)."), ';', -1) AS UNSIGNED) AS frost,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+5+1)."), ';', -1) AS UNSIGNED) AS shadow,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(UNIT_FIELD_RESISTANCES+6+1)."), ';', -1) AS UNSIGNED) AS arcane,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_CRIT_PERCENTAGE+1)."), ';', -1) AS UNSIGNED) AS melee_crit,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_RANGED_CRIT_PERCENTAGE+1)."), ';', -1) AS UNSIGNED) AS range_crit,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_FIELD_COMBAT_RATING_1+7)."), ';', -1) AS UNSIGNED) AS melee_hit,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_FIELD_COMBAT_RATING_1+6)."), ';', -1) AS UNSIGNED) AS range_hit,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_FIELD_COMBAT_RATING_1+5)."), ';', -1) AS UNSIGNED) AS spell_hit,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_FIELD_HONOR_CURRENCY+1)."), ';', -1) AS UNSIGNED) AS honor,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_FIELD_LIFETIME_HONORBALE_KILLS+1)."), ';', -1) AS UNSIGNED) AS kills,
      CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, ';', ".(PLAYER_FIELD_ARENA_CURRENCY+1)."), ';', -1) AS UNSIGNED) AS arena,
      IFNULL((SELECT SUM(points) FROM character_achievement LEFT JOIN `".$dbc_db["name"]."`.achievement ON `".$dbc_db["name"]."`.achievement.id=character_achievement.achievement WHERE character_achievement.guid=characters.guid),0) AS ach_points
      FROM characters 
      ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
  }
  elseif ( $core == 2 )
  {
    $query = "SELECT characters.guid, characters.name, characters.race, characters.class, characters.gender, characters.level, 
              characters.totaltime, characters.online, characters.money AS gold, health,
              power1 AS mana,
              power2 AS rage,
              power4 AS energy,
              power7 AS runic,
							characters.arenaPoints AS arena, characters.totalHonorPoints AS honor, characters.totalKills AS kills,
              strength AS str,
              agility AS agi,
              stamina AS sta,
              intellect AS intel,
              spirit AS spi,
              armor AS armor,
              blockPct AS block,
              dodgePct AS dodge,
              parryPct AS parry,
              attackPower AS ap,
              rangedAttackPower AS ranged_ap,
              power2 AS min_dmg,
              power3 AS max_dmg,
              power4 AS min_ranged_dmg,
              power5 AS max_ranged_dmg,
              power6 AS expertise,
              power7 AS off_expertise,
              resHoly AS holy,
              resFire AS fire,
              resNature AS nature,
              resFrost AS frost,
              resShadow AS shadow,
              resArcane AS arcane,
              critPct AS melee_crit,
              rangedCritPct AS range_crit,
              power1 AS melee_hit,
              power2 AS range_hit,
              power3 AS spell_hit,
              IFNULL((SELECT SUM(points) FROM character_achievement LEFT JOIN `".$dbc_db["name"]."`.achievement ON `".$dbc_db["name"]."`.achievement.id=character_achievement.achievement WHERE character_achievement.guid=characters.guid),0) AS ach_points
              FROM characters
              LEFT JOIN character_stats ON character_stats.guid=characters.guid
              ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage;
    $result = $sql["char"]->query($query);
  }
  else
  {
    $query = "SELECT characters.guid, characters.name, race, class, gender, level, 
              totaltime, online, money AS gold, health,
              power1 AS mana,
              power2 AS rage,
              power4 AS energy,
              power7 AS runic,
							arenaPoints AS arena, totalHonorPoints AS honor, totalKills AS kills,
              strength AS str,
              agility AS agi,
              stamina AS sta,
              intellect AS intel,
              spirit AS spi,
              armor AS armor,
              blockPct AS block,
              dodgePct AS dodge,
              parryPct AS parry,
              attackPower AS ap,
              rangedAttackPower AS ranged_ap,
              power2 AS min_dmg,
              power3 AS max_dmg,
              power4 AS min_ranged_dmg,
              power5 AS max_ranged_dmg,
              power6 AS expertise,
              power7 AS off_expertise,
              resHoly AS holy,
              resFire AS fire,
              resNature AS nature,
              resFrost AS frost,
              resShadow AS shadow,
              resArcane AS arcane,
              critPct AS melee_crit,
              rangedCritPct AS range_crit,
              power1 AS melee_hit,
              power2 AS range_hit,
              power3 AS spell_hit,
              IFNULL((SELECT SUM(points) FROM character_achievement LEFT JOIN `".$dbc_db["name"]."`.achievement ON `".$dbc_db["name"]."`.achievement.id=character_achievement.achievement WHERE character_achievement.guid=characters.guid),0) AS ach_points
              FROM characters
              LEFT JOIN character_stats ON character_stats.guid=characters.guid
              ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage;
    $result = $sql["char"]->query($query);
  }

  //==========================top tage navigaion starts here========================
  $output .= '
          <div class="tab">
            <ul>
              <li'.( ( $type == "level" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'">
                  '.lang("top", "general").'
                </a>
              </li>
              <li'.( ( $type == "stat" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'&amp;type=stat&amp;order_by=health">
                  '.lang("top", "stats").'
                </a>
              </li>
              <li'.( ( $type == "defense" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'&amp;type=defense&amp;order_by=armor">
                  '.lang("top", "defense").'
                </a>
              </li>
              <li'.( ( $type == "resist" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'&amp;type=resist&amp;order_by=holy">
                  '.lang("top", "resist").'
                </a>
              </li>
              <li'.( ( $type == "attack" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'&amp;type=attack&amp;order_by=ap">
                  '.lang("top", "melee").'
                </a>
              </li>
              <li'.( ( $type == "crit_hit" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'&amp;type=crit_hit&amp;order_by=ranged_ap">
                  '.lang("top", "ranged").'
                </a>
              </li>
              <li'.( ( $type == "pvp" ) ? ' class="selected"' : '' ).'>
                <a href="top100.php?n_realms='.$n_realms.'&amp;start='.$start.'&amp;type=pvp&amp;order_by=honor">
                  '.lang("top", "pvp").'
                </a>
              </li>
            </ul>
          </div>
          <div class="tab_content center">
            <table class="top_hidden" id="top100_realms">';
  $output .= '
              <tr>
                <td align="right">Total: '.$all_record.'</td>
                <td align="right" style="width: 25%;">';
  $output .= generate_pagination('top100.php?type='.$type.'&amp;order_by='.$order_by.'&amp;dir='.( ( $dir  ) ? 0 : 1).'', $all_record, $itemperpage, $start);
  $output .= '
                </td>
              </tr>
            </table>';
  //==========================top tage navigaion ENDS here ========================
  $output .= '
            <table class="lined" id="'.( ( $type == "level" ) ? 'top100_mainlist_wide' : 'top100_mainlist' ).'">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 14%;">'.lang("top", "name").'</th>
                <th style="width: 5%;">'.lang("top", "race").'</th>
                <th style="width: 5%;">'.lang("top", "class").'</th>
                <th style="width: 8%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=level&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "level" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "level").'</a></th>';
  if ( $type == "level" )
  {
    $output .= '
                <th style="width: 5%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=ach_points&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "ach_points" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "ach_points").'</a></th>
                <th style="width: 22%;">'.lang("top", "guild").'</th>
                <th style="width: 20%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=gold&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "gold" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "money").'</a></th>
                <th style="width: 20%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=totaltime&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "totaltime" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "time_played").'</a></th>';
  }
  elseif ( $type == "stat" )
  {
    $output .= '
                <th style="width: 11%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=health&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "health" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "health").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=mana&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "mana" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "mana").'</a></th>
                <th style="width: 9%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=str&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "str" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "str").'</a></th>
                <th style="width: 8%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=agi&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "agi" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "agi").'</a></th>
                <th style="width: 8%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=sta&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "sta" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "sta").'</a></th>
                <th style="width: 8%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=intel&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "intel" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "intel").'</a></th>
                <th style="width: 8%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=spi&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "spi" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "spi").'</a></th>';
  }
  elseif ( $type == "defense" )
  {
    $output .= '
                <th style="width: 16%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=armor&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "armor" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "armor").'</a></th>
                <th style="width: 16%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=block&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "block" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "block").'</a></th>
                <th style="width: 15%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=dodge&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "dodge" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "dodge").'</a></th>
                <th style="width: 15%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=parry&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "parry" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "parry").'</a></th>';
  }
  elseif ( $type == "resist" )
  {
    $output .= '
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=holy&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "holy" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "holy").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=fire&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "fire" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "fire").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=nature&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "nature" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "nature").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=frost&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "frost" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "frost").'</a></th>
                <th style="width: 11%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=shadow&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "shadow" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "shadow").'</a></th>
                <th style="width: 11%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=arcane&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "arcane" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "arcane").'</a></th>';
  }
  elseif ( $type == "attack" )
  {
    $output .= '
                <th style="width: 20%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=ap&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "ap" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "ap").'</a></th>
                <th style="width: 6%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=min_dmg&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "min_dmg" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "min_dmg").'</a></th>
                <th style="width: 6%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=max_dmg&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "max_dmg" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "max_dmg").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=melee_crit&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "melee_crit" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "crit").'</a></th>
                <th style="width: 5%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=melee_hit&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "melee_hit" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "hit").'</a></th>
                <th style="width: 5%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=expertise&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "expertise" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "expertise").'</a></th>
                <th style="width: 9%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=off_expertise&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "off_expertise" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "off_expertise").'</a></th>';
  }
  elseif ( $type == "crit_hit" )
  {
    $output .= '
                <th style="width: 18%"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=ranged_ap&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "ranged_ap" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "ap").'</a></th>
                <th style="width: 12%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=min_ranged_dmg&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "min_ranged_dmg" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "min_dmg").'</a></th>
                <th style="width: 12%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=max_ranged_dmg&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "max_ranged_dmg" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "max_dmg").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=range_crit&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "range_crit" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "crit").'</a></th>
                <th style="width: 10%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=range_hit&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "range_hit" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "hit").'</a></th>';
  }
  elseif ( $type == "pvp" )
  {
    $output .= '
                <th style="width: 20%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=honor&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "honor" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "rank").'</a></th>
                <th style="width: 14%;">'.lang("top", "honor_points").'</th>
                <th style="width: 14%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=kills&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "kills" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "kills").'</a></th>
                <th style="width: 14%;"><a href="top100.php?n_realms='.$n_realms.'&amp;type='.$type.'&amp;order_by=arena&amp;start='.$start.'&amp;dir='.$dir.'"'.( ( $order_by == "arena" ) ? ' class="'.$order_dir.'"' : '' ).'>'.lang("top", "arena_points").'</a></th>';
  }
  $output .= '
              </tr>';
  $i = 0;
  while ( $char = $sql["char"]->fetch_assoc($result) )
  {
    // MaNGOS & Trinity don't save guild info on the character
    if ( $core != 1 )
    {
      $g_query = "SELECT * FROM guild_member WHERE guid='".$char["guid"]."'";
      $g_result = $sql["char"]->query($g_query);
      $guildinfo = $sql["char"]->fetch_assoc($g_result);

      $char["gname"] = $guildinfo["guildid"];
    }

    $output .= '
              <tr valign="top">
                <td>'.(++$i+$start).'</td>
                <td><a href="char.php?id='.$char["guid"].'&amp;realm='.$realm_id.'">'.htmlentities($char["name"], ENT_COMPAT, $site_encoding).'</a></td>
                <td>
                  <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" alt="'.char_get_race_name($char["race"]).'" onmousemove="toolTip(\''.char_get_race_name($char["race"]).'\', \'item_tooltip\')" onmouseout="toolTip()" />
                </td>
                <td>
                  <img src="img/c_icons/'.$char["class"].'.gif" alt="'.char_get_class_name($char["class"]).'" onmousemove="toolTip(\''.char_get_class_name($char["class"]).'\', \'item_tooltip\')" onmouseout="toolTip()" />
                </td>
                <td>'.char_get_level_color($char["level"]).'</td>';
    if ( $type == "level" )
    {
      if ( $core == 1 )
      {
        $guild_id = $sql["char"]->result($sql["char"]->query("SELECT guildid FROM guild_data WHERE playerid = '".$char["guid"]."'"), 0);
        $guild_name = $sql["char"]->result($sql["char"]->query("SELECT guildname FROM guilds WHERE guildid = '".$guild_id."'"), 0);
      }
      else
      {
        $guild_id = $sql["char"]->result($sql["char"]->query("SELECT guildid FROM guild_member WHERE guid = '".$char["guid"]."'"), 0);
        $guild_name = $sql["char"]->result($sql["char"]->query("SELECT name AS guildname FROM guild WHERE guildid = '".$guild_id."'"), 0);
      }
      $days  = floor(round($char["totaltime"]/3600)/24);
      $hours = round($char["totaltime"]/3600)-($days*24);
      $time = '';
      if ( $days )
        $time .= $days.' days ';
      if ( $hours )
        $time .= $hours.' hours';

      $output .= '
                <td>'.$char["ach_points"].'</td>
                <td><a href="guild.php?action=view_guild&amp;realm='.$realm_id.'&amp;error=3&amp;id='.$guild_name.'">'.htmlentities($guild_name, ENT_COMPAT, $site_encoding).'</a></td>
                <td align="right">
                  '.substr($char["gold"],  0, -4).'<img src="img/gold.gif" alt="" style="position: relative; bottom: -6px;" />
                  '.substr($char["gold"], -4,  2).'<img src="img/silver.gif" alt="" style="position: relative; bottom: -6px;" />
                  '.substr($char["gold"], -2).'<img src="img/copper.gif" alt="" style="position: relative; bottom: -6px;" />
                </td>
                <td align="right">'.$time.'</td>';
    }
    elseif ( $type == "stat" )
    {
      switch ( $char["class"] )
      {
         case 1: // Warrior
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["rage"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 2: //Paladin
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 3: //Hunter
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 4: //Rogue
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["energy"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 5: //Priest
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 6: //Death Knight
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["runic"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 7: //Shaman
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 8: //Mage
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 9: //Warlock
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
         case 7: //Druid
           $output .= '
                <td>'.$char["health"].'</td>
                <td>'.$char["mana"].'</td>
                <td>'.$char["str"].'</td>
                <td>'.$char["agi"].'</td>
                <td>'.$char["sta"].'</td>
                <td>'.$char["intel"].'</td>
                <td>'.$char["spi"].'</td>';
           break;
       }
           
    }
    elseif ( $type == "defense" )
    {
      $block = unpack("f", pack("L", $char["block"]));
      $block = round($block[1],2);
      $dodge = unpack("f", pack("L", $char["dodge"]));
      $dodge = round($dodge[1],2);
      $parry = unpack("f", pack("L", $char["parry"]));
      $parry = round($parry[1],2);

      $output .= '
                <td>'.$char["armor"].'</td>
                <td>'.$block.'%</td>
                <td>'.$dodge.'%</td>
                <td>'.$parry.'%</td>';
    }
    elseif ( $type == "resist" )
    {
      $output .= '
                <td>'.$char["holy"].'</td>
                <td>'.$char["fire"].'</td>
                <td>'.$char["nature"].'</td>
                <td>'.$char["frost"].'</td>
                <td>'.$char["shadow"].'</td>
                <td>'.$char["arcane"].'</td>';
    }
    elseif ( $type == "attack" )
    {
      $melee = unpack("f", pack("L", $char["melee_crit"]));
      $melee = round($melee[1],2);
      $mindamage = unpack("f", pack("L", $char["min_dmg"]));
      $mindamage = round($mindamage[1],0);
      $maxdamage = unpack("f", pack("L", $char["max_dmg"]));
      $maxdamage = round($maxdamage[1],0);

      $output .= '
                <td>'.$char["ap"].'</td>
                <td>'.$mindamage.'</td>
                <td>'.$maxdamage.'</td>
                <td>'.$melee.'%</td>
                <td>'.$char["melee_hit"].'</td>
                <td>'.$char["expertise"].'</td>
                <td>'.$char["off_expertise"].'</td>';
    }
    elseif ( $type == "crit_hit" )
    {
      $range = unpack("f", pack("L", $char["range_crit"]));
      $range = round($range[1],2);
      $minrangeddamage = unpack("f", pack("L", $char["min_ranged_dmg"]));
      $minrangeddamage = round($minrangeddamage[1],0);
      $maxrangeddamage = unpack("f", pack("L", $char["max_ranged_dmg"]));
      $maxrangeddamage = round($maxrangeddamage[1],0);

      $output .= '
                <td>'.$char["ranged_ap"].'</td>
                <td>'.$minrangeddamage.'</td>
                <td>'.$maxrangeddamage.'</td>
                <td>'.$range.'%</td>
                <td>'.$char["range_hit"].'</td>';
    }
    elseif ( $type == "pvp" )
    {
      $output .= '
                <td align="left"><img src="img/ranks/rank'.char_get_pvp_rank_id($char["honor"], char_get_side_id($char["race"])).'.gif" alt=""></img> '.char_get_pvp_rank_name($char["honor"], char_get_side_id($char["race"])).'</td>
                <td>'.$char["honor"].'</td>
                <td>'.$char["kills"].'</td>
                <td>'.$char["arena"].'</td>';
    }
    $output .= '
              </tr>';
  }
  $output .= '
            </table>
            <table class="top_hidden" id="top100_total_etc">
              <tr>
                <td align="right">Total: '.$all_record.'</td>
                <td align="right" style="width: 25%;">';
  $output .= generate_pagination('top100.php?type='.$type.'&amp;order_by='.$order_by.'&amp;dir='.( ( $dir ) ? 0 : 1 ).'', $all_record, $itemperpage, $start);
  unset($all_record);
  $output .= '
                </td>
              </tr>
            </table>
          </div>
          <br />';
}


//#############################################################################
// MAIN
//#############################################################################

$output .= '
      <div class="bubble">';

$n_realms = ( ( isset($_GET["n_realms"]) ) ? $_GET["n_realms"] : 1 );

if ( $n_realms > 1 )
{
  $realms = $sql["mgr"]->query("SELECT * FROM config_servers LIMIT 10");

  if ( ( $sql["mgr"]->num_rows($realms) > 1 ) && ( count($server) > 1 ) )
  {
    while ( $realm = $sql["mgr"]->fetch_assoc($realms) )
    {
      $output .= '
          <div class="top"><h1>Top 100 of '.$realm["Name"].'</h1></div>';
          top100($realm["Index"]);
    }
  }
  else
  {
    $output .= '
          <div class="top"><h1>'.lang("top", "top100").'</h1></div>';
    top100($realm_id);
  }
}
else
{
  $output .= '
          <div class="top"><h1>'.lang("top", "top100").'</h1></div>';
  top100($realm_id);
}

// add buttons to switch viewing between 1 and all realms
if ( $multi_realm_mode )
{
  $realms = $sql["mgr"]->query("SELECT COUNT(*) FROM config_servers");
  $tot_realms = $sql["mgr"]->result($realms, 0);

  if ( ( $tot_realms > 1 ) && ( count($server) > 1 ) )
  {
    $output .= '
          <div style="height: 30px; width: 284px; margin-left: auto; margin-right: auto;">';

      makebutton(lang("top", "view_all"), "top100.php?n_realms=".$tot_realms, 130);
      makebutton(lang("top", "view_this"), "top100.php?n_realms=1", 130);

      $output .= '
          </div>';
  }
}

unset($action);
unset($action_permission);

require_once "footer.php";

?>
