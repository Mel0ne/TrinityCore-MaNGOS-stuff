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

global $output, $characters_db, $realm_id, $itemperpage, $core, $site_encoding;

$output .= '
      <div class="bubble">';

$start = ( ( isset($_GET["start"]) ) ? $sql["char"]->quote_smart($_GET["start"]) : 0 );
$order_by = ( ( isset($_GET["order_by"]) ) ? $sql["char"]->quote_smart($_GET["order_by"]) :"honor" );

$dir = ( ( isset($_GET["dir"]) ) ? $sql["logon"]->quote_smart($_GET["dir"]) : 1 );
if ( !preg_match('/^[01]{1}$/', $dir) )
  $dir = 1;

$order_dir = ( ( $dir ) ? "ASC" : "DESC" );
$dir = ( ( $dir ) ? 0 : 1 ) ;

if ( $core == 1 )
  $query = $sql["char"]->query("SELECT
        guid, name, race, class,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`characters`.`data`, ';', ".(PLAYER_FIELD_HONOR_CURRENCY+1)."), ';', -1) AS UNSIGNED) AS honor ,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ';', ".(PLAYER_FIELD_LIFETIME_HONORBALE_KILLS+1)."), ';', -1) AS UNSIGNED) AS kills,
        level,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ';', ".(PLAYER_FIELD_ARENA_CURRENCY+1)."), ';', -1) AS UNSIGNED) AS arena,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`characters`.`data`, ';', ".(PLAYER_GUILDID+1)."), ';', -1) AS UNSIGNED) AS GNAME,
        gender
        FROM `characters`
        WHERE race IN (1, 3, 4, 7, 11)
        ORDER BY ".$order_by." ".$order_dir." LIMIT 25;");
else
  $query = $sql["char"]->query("SELECT characters.guid, characters.name, race, class, 
        totalHonorPoints AS honor, totalKills AS kills, level, arenaPoints AS arena, 
        guildid AS GNAME, gender 
        FROM `characters`, guild_member 
        WHERE race IN (1, 3, 4, 7, 11) AND guild_member.guid=characters.guid 
        ORDER BY ".$order_by." ".$order_dir." LIMIT 25;");
  

$this_page = $sql["char"]->num_rows($query);

$output .= '
        <script type="text/javascript">
          answerbox.btn_ok="'.lang("global", "yes_low").'";
          answerbox.btn_cancel="'.lang("global", "no").'";
        </script>
        <div class="fieldset_border honor_faction">
          <span class="honor_faction_icon"><img src="img/alliance.gif" alt="" /></span>
          <table class="lined" id="honor_alliance_ranks">
            <tr class="bold">
              <td colspan="11" class="hidden">'.lang("honor", "allied").' '.lang("honor", "browse_honor").'</td>
            </tr>
            <tr>
              <th style="width: 20%;">'.lang("honor", "guid").'</th>
              <th style="width: 7%;">'.lang("honor", "race").'</th>
              <th style="width: 7%;">'.lang("honor", "class").'</th>
              <th style="width: 7%;">
                <a href="honor.php?order_by=level&amp;dir='.$dir.'"'.( ( $order_by == "level" ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "level").'</a>
              </th>
              <th style="width: 7%;">
                <a href="honor.php?order_by=honor&amp;dir='.$dir.'"'.( ( $order_by == "honor" ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "honor").'</a>
              </th>
              <th style="width: 11%;">
                <a href="honor.php?order_by=honor&amp;dir='.$dir.'"'.( ( $order_by == "honor" ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "honor_points").'</a>
              </th>
              <th style="width: 7%;">
                <a href="honor.php?order_by=kills&amp;dir='.$dir.'"'.( ( $order_by == "kills" ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "kills").'</a>
              </th>
              <th style="width: 11%;">
                <a href="honor.php?order_by=arena&amp;dir='.$dir.'"'.( ( $order_by == "arena" ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "arena_points_short").'</a>
              </th>
              <th style="width: 24%;">'.lang("honor", "guild")."</th>
            </tr>";

while ( $char = $sql["char"]->fetch_assoc($query) ) 
{
  if ( $core == 1 )
  {
    $guild_id = $sql["char"]->result($sql["char"]->query("SELECT guildid FROM guild_data WHERE playerid='".$char["guid"]."'"), 0);
    $guild_name = $sql["char"]->result($sql["char"]->query("SELECT guildname FROM guilds WHERE guildid='".$guild_id."'"), 0);
  }
  else
  {
    $guild_query = "SELECT name FROM guild WHERE guildid=".$char["GNAME"];
    $guild_name = $sql["char"]->fetch_assoc($sql["char"]->query($guild_query));
    $guild_name = $guild_name["name"];
  }

  $output .= '
            <tr>
              <td>
                <a href="char.php?id='.$char["guid"].'">'.htmlentities($char["name"], ENT_COMPAT, $site_encoding).'</a>
              </td>
              <td>
                <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
              </td>
              <td>
                <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
              </td>
              <td>'.char_get_level_color($char["level"]).'</td>
              <td>
                <span onmouseover="oldtoolTip(\''.char_get_pvp_rank_name($char["honor"], char_get_side_id($char["race"])).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" class="honor_tooltip">
                  <img src="img/ranks/rank'.char_get_pvp_rank_id($char["honor"], char_get_side_id($char["race"])).'.gif" alt="" />
                </span>
              </td>
              <td>'.$char["honor"].'</td>
              <td>'.$char["kills"].'</td>
              <td>'.$char["arena"].'</td>
              <td>
                <a href="guild.php?action=view_guild&amp;error=3&amp;id='.$char["GNAME"].'">'.htmlentities($guild_name, ENT_COMPAT, $site_encoding).'</a>
              </td>
            </tr>';
}

$output .= '
          </table>
          <br />
        </div>
        <br />';

if ( $core == 1 )
  $query = $sql["char"]->query("SELECT
        guid, name, race, class,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`characters`.`data`, ' ', ".(PLAYER_FIELD_HONOR_CURRENCY+1)."), ' ', -1) AS UNSIGNED) AS honor ,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(PLAYER_FIELD_LIFETIME_HONORBALE_KILLS+1)."), ' ', -1) AS UNSIGNED) AS kills,
        level,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(PLAYER_FIELD_ARENA_CURRENCY+1)."), ' ', -1) AS UNSIGNED) AS arena,
        CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`characters`.`data`, ' ', ".(PLAYER_GUILDID+1)."), ' ', -1) AS UNSIGNED) AS GNAME,
        gender
        FROM `characters`
        WHERE race NOT IN (1, 3, 4, 7, 11)
        ORDER BY ".$order_by." ".$order_dir." LIMIT 25;");
else
  $query = $sql["char"]->query("SELECT characters.guid, characters.name, race, class, 
        totalHonorPoints AS honor, totalKills AS kills, 
        level, arenaPoints AS arena, guildid AS GNAME, gender 
        FROM `characters`, guild_member
        WHERE race NOT IN (1, 3, 4, 7, 11) AND guild_member.guid=characters.guid 
        ORDER BY ".$order_by." ".$order_dir." LIMIT 25;");


$this_page = $sql["char"]->num_rows($query);

$output .= '
        <script type="text/javascript">
          answerbox.btn_ok="'.lang("global", "yes_low").'";
          answerbox.btn_cancel="'.lang("global", "no").'";
        </script>
        <div class="fieldset_border honor_faction">
          <span class="honor_faction_icon"><img src="img/horde.gif" alt="" /></span>
          <table class="lined" id="honor_horde_ranks">
            <tr class="bold">
              <td colspan="11" class="hidden">'.lang("honor", "horde")." ".lang("honor", "browse_honor").'</td>
            </tr>
            <tr>
              <th style="width: 20%;">'.lang("honor", "guid").'</th>
              <th style="width: 7%;">'.lang("honor", "race").'</th>
              <th style="width: 7%;">'.lang("honor", "class").'</th>
              <th style="width: 7%;">
                <a href="honor.php?order_by=level&amp;dir='.$dir.'"'.( ( $order_by == 'level' ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "level").'</a>
              </th>
              <th style="width: 7%;">
                <a href="honor.php?order_by=honor&amp;dir='.$dir.'"'.( ( $order_by == 'honor' ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "honor").'</a>
              </th>
              <th style="width: 11%;">
                <a href="honor.php?order_by=honor&amp;dir='.$dir.'"'.( ( $order_by == 'honor' ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "honor_points").'</a>
              </th>
              <th style="width: 7%;">
                <a href="honor.php?order_by=kills&amp;dir='.$dir.'"'.( ( $order_by == 'kills' ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "kills").'</a>
              </th>
              <th style="width: 11%;">
                <a href="honor.php?order_by=arena&amp;dir='.$dir.'"'.( ( $order_by == 'arena' ) ? ( ( $dir ) ? ' class="DESC"' : ' class="ASC"' ) : '' ).'>'.lang("honor", "arena_points_short").'</a>
              </th>
              <th style="width: 24%;">'.lang("honor", "guild").'</th>
            </tr>';

while ( $char = $sql["char"]->fetch_assoc($query) ) 
{
  if ( $core == 1 )
  {
    $guild_id = $sql["char"]->result($sql["char"]->query("SELECT guildid FROM guild_data WHERE playerid='".$char["guid"]."'"), 0);
    $guild_name = $sql["char"]->result($sql["char"]->query("SELECT guildname FROM guilds WHERE guildid='".$guild_id."'"), 0);
  }
  else
  {
    $guild_name = $sql["char"]->fetch_assoc($sql["char"]->query("SELECT `name` FROM `guild` WHERE `guildid`=".$char["GNAME"].";"));
    $guild_name = $guild_name["name"];
  }

  $output .= '
            <tr>
              <td>
                <a href="char.php?id='.$char["guid"].'">'.htmlentities($char["name"], ENT_COMPAT, $site_encoding).'</a>
              </td>
              <td>
                <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
              </td>
              <td>
                <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
              </td>
              <td>'.char_get_level_color($char["level"]).'</td>
              <td>
                <span onmouseover="oldtoolTip(\''.char_get_pvp_rank_name($char["honor"], char_get_side_id($char["race"])).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" class="honor_tooltip">
                  <img src="img/ranks/rank'.char_get_pvp_rank_id($char["honor"], char_get_side_id($char["race"])).'.gif" alt="" />
                </span>
              </td>
              <td>'.$char["honor"].'</td>
              <td>'.$char["kills"].'</td>
              <td>'.$char["arena"].'</td>
              <td>
                <a href="guild.php?action=view_guild&amp;error=3&amp;id='.$char["GNAME"].'">'.htmlentities($guild_name, ENT_COMPAT, $site_encoding).'</a>
              </td>
            </tr>';
}

$output .= '
          </table>
        </div>';

require_once "footer.php";
?>
