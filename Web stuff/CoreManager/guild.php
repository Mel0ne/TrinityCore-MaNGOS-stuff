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
// BROWSE GUILDS
//#############################################################################
function browse_guilds()
{
  global $output, $logon_db, $characters_db, $realm_id, $site_encoding,
    $action_permission, $user_lvl, $user_id, $itemperpage, $sql, $core;

  //==========================$_GET and SECURE=================================
  $start = ( ( isset($_GET["start"]) ) ? $sql["char"]->quote_smart($_GET["start"]) : 0 );
  if ( !is_numeric($start) )
    $start = 0;

  $order_by = ( ( isset($_GET["order_by"]) ) ? $sql["char"]->quote_smart($_GET["order_by"]) : "gid" );
  if ( !preg_match('/^[_[:lower:]]{1,10}$/', $order_by) )
    $order_by = 'gid';

  $dir = ( ( isset($_GET["dir"]) ) ? $sql["char"]->quote_smart($_GET["dir"]) : 1 );
  if ( !preg_match('/^[01]{1}$/', $dir) )
    $dir = 1;

  $order_dir = ( ( $dir ) ? "ASC" : "DESC" );
  $dir = ( ( $dir ) ? 0 : 1 );
  //==========================$_GET and SECURE end=============================
  //==========================MyGuild==========================================

  if ( $core == 1 )
    $query_myGuild = $sql["char"]->query("SELECT g.guildid AS gid, g.guildname AS gname, g.leaderguid AS lguid,
      (SELECT name FROM characters WHERE guid=lguid) AS lname, (SELECT race IN (2, 5, 6, 8, 10) FROM characters WHERE guid=lguid) AS faction,
      (SELECT COUNT(*) FROM characters WHERE guid IN (SELECT guid FROM guild_data WHERE guildid=gid) AND online=1) AS gonline,
      (SELECT COUNT(*) FROM guild_data WHERE guildid=gid) AS mcount, g.guildInfo AS info, g.motd AS motd, g.createdate AS createdate,
      (SELECT acct FROM characters WHERE guid=lguid) AS macct,
      (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
      (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
      FROM guilds AS g
      LEFT OUTER JOIN guild_data AS gm ON gm.guildid=g.guildid LEFT OUTER JOIN characters AS c ON c.guid=gm.playerid
      WHERE c.acct=".$user_id." GROUP BY g.guildid ORDER BY gid");
  else
    $query_myGuild = $sql["char"]->query("SELECT g.guildid AS gid, g.name AS gname, g.leaderguid AS lguid,
      (SELECT name FROM characters WHERE guid=lguid) AS lname, (SELECT race IN (2, 5, 6, 8, 10) FROM characters WHERE guid=lguid) AS faction,
      (SELECT COUNT(*) FROM characters WHERE guid IN (SELECT guid FROM guild_member WHERE guildid=gid) AND online = 1) AS gonline,
      (SELECT COUNT(*) FROM guild_member WHERE guildid=gid) AS mcount, g.info AS info, g.motd AS motd, g.createdate AS createdate,
      (SELECT account FROM characters WHERE guid=lguid) AS macct,
      (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
      (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
      FROM guild AS g
      LEFT OUTER JOIN guild_member AS gm ON gm.guildid=g.guildid LEFT OUTER JOIN characters AS c ON c.guid=gm.guid
      WHERE c.account=".$user_id." GROUP BY g.guildid ORDER BY gid");

  if ( $query_myGuild )
  {
    $output .= '
        <div class="guild_fieldset fieldset_border">
          <span class="legend">'.lang("guild", "my_guilds").'</span>
          <table class="lined">
            <tr>
              <th style="width: 1%;">'.lang("guild", "id").'</th>
              <th style="width: 20%;">'.lang("guild", "guild_name").'</th>
              <th style="width: 10%;">'.lang("guild", "guild_leader").'</th>
              <th style="width: 1%;">'.lang("guild", "guild_faction").'</th>
              <th style="width: 10%;">'.lang("guild", "tot_m_online").'</th>
              <th style="width: 20%;">'.lang("guild", "info").'</th>
              <th style="width: 20%;">'.lang("guild", "guild_motd").'</th>
              <th style="width: 20%;">'.lang("guild", "create_date").'</th>
            </tr>';
    while ( $data = $sql["char"]->fetch_assoc($query_myGuild) )
    {
      if ( $core == 1 )
        $result = $sql["logon"]->query("SELECT login FROM accounts WHERE acct='".$data["macct"]."'");
      else
        $result = $sql["logon"]->query("SELECT username AS login FROM account WHERE id='".$data["macct"]."'");

      $uname = $sql["logon"]->result($result, 0, "login");

      $result = $sql["mgr"]->query("SELECT SecurityLevel FROM config_accounts WHERE Login='".$uname."'");

      $owner_gmlvl = $sql["logon"]->result($result, 0, "SecurityLevel");
      if ( !isset($owner_gmlvl) )
        $owner_gmlvl = 0;

      if ( $owner_gmlvl >= 1073741824 )
        $owner_gmlvl -= 1073741824;

      $output .= '
            <tr>
              <td>'.$data["gid"].'</td>
              <td><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$data["gid"].'">'.$data["gname"].'</a></td>
              <td><a href="char.php?id='.$data["lguid"].'" onmousemove="oldtoolTip(\''.lang("char", "level_short").$data["llevel"].' '.char_get_race_name($data["lrace"]).' '.char_get_class_name($data["lclass"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()">'.htmlentities($data["lname"], ENT_COMPAT, $site_encoding).'</a></td>
              <td><img src="img/'.( ( $data["faction"]==0 ) ? "alliance" : "horde" ).'_small.gif" alt="" /></td>
              <td>'.$data["gonline"].'/'.$data["mcount"].'</td>
              <td>'.htmlentities($data["info"], ENT_COMPAT, $site_encoding).'</td>
              <td>'.htmlentities($data["motd"], ENT_COMPAT, $site_encoding).'</td>
              <td class="small">'.date('o-m-d', $data["createdate"]).'</td>
            </tr>';
    }
    unset($data);
    unset($result);
    $output .= '
          </table>
        </div>
        <br />';
  }
  //==========================MyGuild end======================================
  //==========================Browse/Search Guilds CHECK=======================
  $search_by = '';
  $search_value = '';
  if ( isset($_GET["search_value"]) && isset($_GET["search_by"]) )
  {
    $search_by = $sql["char"]->quote_smart($_GET["search_by"]);
    $search_value = $sql["char"]->quote_smart($_GET["search_value"]);

    $search_menu = array("name", "leadername", "guildid");
    if ( !in_array($search_by, $search_menu) )
      $search_by = "name";

    switch ( $search_by )
    {
      case "name":
      {
        if ( preg_match('/^[\t\v\b\f\a\n\r\\\"\'\? <>[](){}_=+-|!@#$%^&*~`.,0123456789\0]{1,30}$/', $search_value) )
          redirect("guild.php?error=5");
        if ( $core == 1 )
        {
          $query = $sql["char"]->query("SELECT g.guildid AS gid, g.guildname, g.leaderguid AS lguid,
            (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race IN (2, 5, 6, 8, 10) AS lfaction,
            (SELECT COUNT(*) FROM guild_data WHERE guildid=gid) AS tot_chars, createdate, c.acct AS laccount,
            g.guildInfo AS info,
            (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
            (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
            FROM guilds AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid
            WHERE g.name LIKE '%".$search_value."%' ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
          $query_count = $sql["char"]->query("SELECT 1 FROM guilds WHERE guildname LIKE '%".$search_value."%'");
        }
        else
        {
          $query = $sql["char"]->query("SELECT g.guildid AS gid, g.name AS guildname, g.leaderguid AS lguid,
            (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race IN (2, 5, 6, 8, 10) AS lfaction,
            (SELECT COUNT(*) FROM guild_member WHERE guildid=gid) AS tot_chars, createdate, c.account AS laccount,
            g.info AS info,
            (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
            (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
            FROM guild AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid
            WHERE g.name LIKE '%".$search_value."%' ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
          $query_count = $sql["char"]->query("SELECT 1 FROM guild WHERE name LIKE '%".$search_value."%'");
        }
        break;
      }
      case "leadername":
      {
        if ( preg_match('/^[\t\v\b\f\a\n\r\\\"\'\? <>[](){}_=+-|!@#$%^&*~`.,0123456789\0]{1,30}$/', $search_value) )
          redirect("guild.php?error=5");
        if ( $core == 1 )
        {
          $query = $sql["char"]->query("SELECT g.guildid AS gid, g.guildname, g.leaderguid AS lguid,
            (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race IN (2, 5, 6, 8, 10) AS lfaction,
            (SELECT COUNT(*) FROM guild_data WHERE guildid=gid) AS tot_chars, createdate, c.acct AS laccount,
            g.guildInfo AS info,
            (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
            (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
            FROM guilds AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid WHERE g.leaderguid IN
            (SELECT guid FROM characters WHERE name LIKE '%".$search_value."%') ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
          $query_count = $sql["char"]->query("SELECT 1 FROM guilds WHERE leaderguid IN (SELECT guid FROM characters WHERE name LIKE '%".$search_value."%')");
        }
        else
        {
          $query = $sql["char"]->query("SELECT g.guildid AS gid, g.name AS guildname, g.leaderguid AS lguid,
            (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race IN (2, 5, 6, 8, 10) AS lfaction,
            (SELECT COUNT(*) FROM guild_member WHERE guildid=gid) AS tot_chars, createdate, c.account AS laccount,
            g.info AS info,
            (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
            (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
            FROM guild AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid WHERE g.leaderguid IN
            (SELECT guid FROM characters WHERE name LIKE '%".$search_value."%') ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
          $query_count = $sql["char"]->query("SELECT 1 FROM guild WHERE leaderguid IN (SELECT guid FROM characters WHERE name LIKE '%".$search_value."%')");
        }
        break;
      }
      case "guildid":
      {
        if ( !is_numeric($search_value) )
          redirect("guild.php?error=5");
        if ( $core == 1 )
        {
          $query = $sql["char"]->query("SELECT g.guildid AS gid, g.guildname, g.leaderguid AS lguid,
            (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race IN (2, 5, 6, 8, 10) AS lfaction,
            (SELECT COUNT(*) FROM guild_data WHERE guildid=gid) AS tot_chars, createdate, c.acct AS laccount,
            g.guildInfo AS info,
            (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
            (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
            FROM guilds AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid
            WHERE g.guildid='".$search_value."' ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
          $query_count = $sql["char"]->query("SELECT 1 FROM guilds WHERE guildid='".$search_value."'");
        }
        else
        {
          $query = $sql["char"]->query("SELECT g.guildid AS gid, g.name AS guildname, g.leaderguid AS lguid,
            (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race in (2, 5, 6, 8, 10) AS lfaction,
            (SELECT COUNT(*) FROM guild_member WHERE guildid=gid) AS tot_chars, createdate, c.account AS laccount,
            g.info AS info,
            (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
            (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
            FROM guild AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid
            WHERE g.guildid='".$search_value."' ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
          $query_count = $sql["char"]->query("SELECT 1 FROM guild WHERE guildid='".$search_value."'");
        }
        break;
      }
      default:
        redirect("guild.php?error=2");
    }
  }
  else
  {
    if ( $core == 1 )
    {
      $query = $sql["char"]->query("SELECT g.guildid AS gid, g.guildname, g.leaderguid AS lguid,
        (SELECT name FROM characters WHERE guid=lguid) AS lname, c.race IN (2, 5, 6, 8, 10) AS lfaction,
        (SELECT COUNT(*) FROM guild_data WHERE guildid=gid) AS tot_chars, createdate, c.acct AS laccount,
        g.guildInfo AS info,
        (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
        (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
        FROM guilds AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid
        ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
      $query_count = $sql["char"]->query("SELECT 1 FROM guilds");
    }
    else
    {
      $query = $sql["char"]->query("SELECT g.guildid AS gid, g.name AS guildname, g.leaderguid AS lguid, 
        (SELECT name FROM characters where guid=lguid) AS lname, c.race in (2,5,6,8,10) AS lfaction, 
        (SELECT COUNT(*) FROM guild_member where guildid=gid) AS tot_chars, createdate,  c.account AS laccount,
        g.info AS info,
        (SELECT race FROM characters WHERE guid=lguid) AS lrace, (SELECT class FROM characters WHERE guid=lguid) AS lclass,
        (SELECT level FROM characters WHERE guid=lguid) AS llevel, (SELECT gender FROM characters WHERE guid=lguid) AS lgender
        FROM guild AS g LEFT OUTER JOIN characters AS c ON c.guid=g.leaderguid 
        ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
      $query_count = $sql["char"]->query("SELECT 1 FROM guild");
    }
  }
  $all_record = $sql["char"]->num_rows($query_count);
  //==========================Browse/Search Guilds CHECK end===================
  //==========================Browse/Search Guilds=============================

  // a little XSS overhead
  $search_by = htmlspecialchars($search_by);
  $search_value = htmlspecialchars($search_value);

$output .= '
        <table class="top_hidden">
          <tr align="left">
            <td>
              <table class="hidden">
                <tr>
                  <td>
                    <form action="guild.php" method="get" id="form">
                      <div>
                        <input type="hidden" name="error" value="4" />
                        <input type="text" size="24" name="search_value" value="'.$search_value.'" />
                        <select name="search_by">
                          <option value="name" '.( ( $search_by == "name" ) ? ' selected="selected"' : '' ).'>'.lang("guild", "by_name").'</option>
                          <option value="leadername" '.( ( $search_by == "leadername" ) ? ' selected="selected"' : '' ).'>'.lang("guild", "by_guild_leader").'</option>
                          <option value="guildid" '.( ( $search_by == "guildid" ) ? ' selected="selected"' : '' ).'>'.lang("guild", "by_id").'</option>
                        </select>
                      </div>
                    </form>
                  </td>
                  <td>';
  makebutton(lang("global", "search"), "javascript:do_submit()", 80);
  ( ( $search_by &&  $search_value ) ? makebutton(lang("guild", "show_guilds"), "guild.php\" type=\"def", 130) : $output .= "" );
  $output .= '
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>';
  //==========================top tage navigaion ENDS here ====================
$output .= '
        <div class="guild_fieldset fieldset_border">
          <span class="legend">'.lang("guild", "browse_guilds").'</span>
            <table class="lined">
              <tr class="hidden">
                <td colspan="6" class="hidden" align="right" style="width: 25%;">';
      $output .= generate_pagination("guild.php?action=brows_guilds&amp;order_by=".$order_by."&amp;".( ( $search_value && $search_by ) ? "search_by=".$search_by."&amp;search_value=".$search_value."&amp" : "")."dir=".( ( $dir ) ? 0 : 1 )."", $all_record, $itemperpage, $start);
      $output .= '
                </td>
              </tr>
              <tr>
                <th style="width: 5%;"><a href="guild.php?order_by=gid&amp;start='.$start.'&amp;dir='.$dir.( ( $search_value && $search_by ) ? '&amp;search_by='.$search_by.'&amp;search_value='.$search_value : "" ).'">'.( ( $order_by == "gid" ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : "" ).lang("guild", "id").'</a></th>
                <th style="width: 30%;"><a href="guild.php?order_by=guildname&amp;start='.$start.'&amp;dir='.$dir.( ( $search_value && $search_by ) ? '&amp;search_by='.$search_by.'&amp;search_value='.$search_value : "" ).'">'.( ( $order_by == "guildname" ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : "" ).lang("guild", "guild_name").'</a></th>
                <th style="width: 20%;"><a href="guild.php?order_by=lname&amp;start='.$start.'&amp;dir='.$dir.( ( $search_value && $search_by ) ? '&amp;search_by='.$search_by.'&amp;search_value='.$search_value : "" ).'">'.( ( $order_by == "lname" ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : "" ).lang("guild", "guild_leader").'</a></th>
                <th style="width: 10%;"><a href="guild.php?order_by=lfaction&amp;start='.$start.'&amp;dir='.$dir.( ( $search_value && $search_by ) ? '&amp;search_by='.$search_by.'&amp;search_value='.$search_value : "" ).'">'.( ( $order_by == "lfaction" ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : "" ).lang("guild", "guild_faction").'</a></th>
                <th style="width: 15%;"><a href="guild.php?order_by=tot_chars&amp;start='.$start.'&amp;dir='.$dir.( ( $search_value && $search_by ) ? '&amp;search_by='.$search_by.'&amp;search_value='.$search_value : "" ).'">'.( ( $order_by == "tot_chars" ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : "" ).lang("guild", "tot_members").'</a></th>
                <th style="width: 20%;">'.lang("guild", "info").'</th>
                <th style="width: 20%;"><a href="guild.php?order_by=createdate&amp;start='.$start.'&amp;dir='.$dir.( ( $search_value && $search_by ) ? '&amp;search_by='.$search_by.'&amp;search_value='.$search_value : "" ).'">'.( ( $order_by == "createdate" ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : "" ).lang("guild", "create_date").'</a></th>
              </tr>';
  while ( $data = $sql["char"]->fetch_assoc($query) )
  {
    if ( $core == 1 )
      $a_query = "SELECT * FROM accounts WHERE acct='".$data["laccount"]."'";
    else
      $a_query = "SELECT *, username AS login FROM account WHERE id='".$data["laccount"]."'";

    $a_result = $sql["logon"]->query($a_query);
    $a_result = $sql["logon"]->fetch_assoc($a_result);
    $user = $a_result["login"];

    $result = $sql["mgr"]->query("SELECT SecurityLevel AS gm FROM config_accounts WHERE Login='".$user."'");
    $owner_gmlvl = $sql["logon"]->result($result, 0, 'gm');
    $output .= '
              <tr>
                <td>'.$data["gid"].'</td>';
    $output .= ( ( $user_lvl >= $action_permission["update"] ) ? '<td><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$data["gid"].'">'.htmlentities($data["guildname"], ENT_COMPAT, $site_encoding).'</a></td>' : '<td>'.htmlentities($data["guildname"], ENT_COMPAT, $site_encoding).'</td>' );
    $output .= ( ( $user_lvl >= $owner_gmlvl ) ? '<td><a href="char.php?id='.$data["lguid"].'" onmousemove="oldtoolTip(\''.lang("char", "level_short").$data["llevel"].' '.char_get_race_name($data["lrace"]).' '.char_get_class_name($data["lclass"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()">'.htmlentities($data["lname"], ENT_COMPAT, $site_encoding).'</a></td>' : '<td><span onmousemove="oldtoolTip(\''.lang("char", "level_short").$data["llevel"].' '.char_get_race_name($data["lrace"]).' '.char_get_class_name($data["lclass"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()">'.htmlentities($data["lname"], ENT_COMPAT, $site_encoding).'</span></td>' );
    $output .= '
                <td><img src="img/'.( ( $data["lfaction"] == 0 ) ? "alliance" : "horde" ).'_small.gif" alt="" /></td>
                <td>'.$data["tot_chars"].'</td>
                <td>'.$data["info"].'</td>
                <td class="small">'.date("o-m-d", $data["createdate"]).'</td>
              </tr>';
  }
  $output .= '
              <tr>
                <td colspan="6" class="hidden" align="right" style="width: 25%;">'.generate_pagination("guild.php?action=brows_guilds&amp;order_by=".$order_by."&amp;".( ( $search_value && $search_by ) ? "search_by=".$search_by."&amp;search_value=".$search_value."&amp" : "" )."dir=".( ( $dir ) ? 0 : 1 )."", $all_record, $itemperpage, $start).'</td>
              </tr>
              <tr>
                <td colspan="6" class="hidden" align="right">'.lang("guild", "tot_guilds").' : '.$all_record.'</td>
              </tr>
            </table>
          </div>
          <br />';

}
//==========================Browse/Search Guilds end=========================


//#############################################################################
// VIEW GUILD
//#############################################################################
function view_guild()
{
  global $output,  $logon_db, $characters_db, $corem_db, $realm_id, $itemperpage,
    $action_permission, $user_lvl, $user_id, $showcountryflag, $site_encoding,
    $show_guild_emblem, $sql, $core;

  if ( !isset($_GET["id"]) )
    redirect("guild.php?error=1");
  $guild_id = $sql["char"]->quote_smart($_GET["id"]);
  if ( !is_numeric($guild_id) )
    redirect("guild.php?error=6");

  //==========================SQL INGUILD and GUILDLEADER======================
  if ( $core == 1 )
    $q_inguild = $sql["char"]->query("SELECT 1 FROM guild_data WHERE guildid='".$guild_id."' AND playerid IN (SELECT guid FROM characters WHERE acct='".$user_id."')");
  else
    $q_inguild = $sql["char"]->query("SELECT 1 FROM guild_member WHERE guildid='".$guild_id."' AND guid IN (SELECT guid FROM characters WHERE account='".$user_id."')");
  $inguild = $sql["char"]->result($q_inguild, 0, "1");
  if ( $user_lvl < $action_permission["update"] && !$inguild )
    redirect("guild.php?error=6");

  if ( $core == 1 )
    $q_amIguildleader = $sql["char"]->query("SELECT 1 FROM guilds WHERE guildid='".$guild_id."' AND leaderguid IN (SELECT guid FROM characters WHERE acct='".$user_id."')");
  else
    $q_amIguildleader = $sql["char"]->query("SELECT 1 FROM guild WHERE guildid='".$guild_id."' AND leaderguid IN (SELECT guid FROM characters WHERE account='".$user_id."')");
  $amIguildleader = $sql["char"]->result($q_amIguildleader, 0, "1");

  if ( $core == 1 )
    $q_guildmemberCount = $sql["char"]->query("SELECT 1 FROM guild_data WHERE guildid='".$guild_id."'");
  else
    $q_guildmemberCount = $sql["char"]->query("SELECT 1 from guild_member where guildid='".$guild_id."'");
  $guildmemberCount = $sql["char"]->num_rows($q_guildmemberCount);
  //====================SQL INGUILD and GUILDLEADER end========================

  //==========================$_GET and SECURE=================================
  $start = ( ( isset($_GET["start"]) ) ? $sql["char"]->quote_smart($_GET["start"]) : 0 );
  if ( is_numeric($start) )
    ;
  else
    $start = 0;

  $order_by = ( ( isset($_GET["order_by"]) ) ? $sql["char"]->quote_smart($_GET["order_by"]) : "mrank" );
  if ( !preg_match("/^[_[:lower:]]{1,10}$/", $order_by) )
    $order_by = "mrank";

  $dir = ( ( isset($_GET["dir"]) ) ? $sql["char"]->quote_smart($_GET["dir"]) : 1 );
  if ( !preg_match("/^[01]{1}$/", $dir) )
    $dir = 1;

  $order_dir = ( ( $dir ) ? "ASC" : "DESC" );
  $dir = ( ( $dir ) ? 0 : 1 );
  //==========================$_GET and SECURE end=============================

  if ( $core == 1 )
    $query = $sql["char"]->query("SELECT guildid, guildname AS name, guildinfo AS info, MOTD, createdate,
      (SELECT COUNT(*) FROM guild_data WHERE guildid='".$guild_id."') AS mtotal,
      (SELECT COUNT(*) FROM guild_data WHERE guildid='".$guild_id."' AND playerid IN
      (SELECT guid FROM characters WHERE online=1)) AS monline,
      emblemStyle AS EmblemStyle,
      emblemColor AS EmblemColor,
      borderStyle AS BorderStyle,
      borderColor AS BorderColor,
      backgroundColor AS BackgroundColor
      FROM guilds WHERE guildid='".$guild_id."'");
  else
    $query = $sql["char"]->query("SELECT guildid, name, info, MOTD, createdate,
      (SELECT COUNT(*) FROM guild_member where guildid='".$guild_id."') AS mtotal,
      (SELECT COUNT(*) FROM guild_member where guildid='".$guild_id."' AND guid IN
      (SELECT guid FROM characters WHERE online=1)) AS monline,
      EmblemStyle, EmblemColor, BorderStyle, BorderColor, BackgroundColor
      FROM guild WHERE guildid='".$guild_id."'");
  $guild_data = $sql["char"]->fetch_assoc($query);

  $output .= '
        <script type="text/javascript">
          answerbox.btn_ok="'.lang("global", "yes").'";
          answerbox.btn_cancel="'.lang("global", "no").'";
        </script>
        <div class="guild_fieldset fieldset_border">
          <span class="legend">'.lang("guild", "guild").'</span>
          <table class="lined">
            <tr>
              <td style="width: 25%;" class="hidden"><b>'.lang("guild", "create_date").':</b><br />'.date("o-m-d", $guild_data["createdate"]).'</td>
              <td style="width: 50%;" class="bold hidden" colspan="2">'.$guild_data["name"].'</td>
              <td style="width: 25%;" class="hidden"><b>'.lang("guild", "tot_m_online").':</b><br />'.$guild_data["monline"].' / '.$guild_data["mtotal"].'</td>
            </tr>
            <tr>
              <td colspan="2" class="hidden"><b>'.lang("guild", "info").':</b><br />'.$guild_data["info"].'</td>
              <td colspan="2" class="hidden"><b>'.lang("guild", "motd").':</b><br />'.$guild_data["MOTD"].'</td>
            </tr>';
  if ( $show_guild_emblem )
    $output .= '
            <tr>
              <td colspan="4" class="hidden">
                <div id="guild_emblem">
                  <div>
                    <img id="guild_view_background" src="img/emblems/Background_'.doubledigit($guild_data["BackgroundColor"]).'.png" alt="" />
                    <img id="guild_view_emblem" src="img/emblems/Emblem_'.doubledigit($guild_data["EmblemStyle"]).'_'.doubledigit($guild_data["EmblemColor"]).'.png" alt="" />
                    <img id="guild_view_border" src="img/emblems/Border_'.doubledigit($guild_data["BorderStyle"]).'_'.doubledigit($guild_data["BorderColor"]).'.png" alt="" />
                    <img id="guild_emblem_border" src="img/EmblemBorder.png" alt="" />
                  </div>
                </div>
              </td>
            </tr>
            <tr>
              <td id="guild_pages_background" style="text-align: right;" colspan="4">'.generate_pagination("guild.php?action=view_guild&amp;id=".$guild_id."&amp;order_by=".$order_by."&amp;dir=".( ( $dir ) ? 0 : 1 ), $guildmemberCount, $itemperpage, $start).'</td>
            </tr>
          </table>
          <table class="lined">
            <tr>
              <th style="width: 1%;">'.lang("guild", "remove").'</th>
              <th style="width: 15%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=cname&amp;start='.$start.'&amp;dir='.$dir.'">'.( ( $order_by == "cname" ) ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "name").'</a></th>
              <th style="width: 1%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=crace&amp;start='.$start.'&amp;dir='.$dir.'">'.( ( $order_by == "crace" ) ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "race").'</a></th>
              <th style="width: 1%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=class&amp;start='.$start.'&amp;dir='.$dir.'">'.( $order_by == "cclass" ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "class").'</a></th>
              <th style="width: 1%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=clevel&amp;start='.$start.'&amp;dir='.$dir.'">'.( $order_by == "clevel" ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "level").'</a></th>
              <th style="width: 25%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=mrank&amp;start='.$start.'&amp;dir='.$dir.'">'.( $order_by == "mrank" ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "rank").'</a></th>
              <th style="width: 15%;">'.lang("guild", "pnote").'</th>
              <th style="width: 15%;">'.lang("guild", "offnote").'</th>
              <th style="width: 15%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=clogout&amp;start='.$start.'&amp;dir='.$dir.'">'.( ( $order_by == "clogout" ) ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "llogin").'</a></th>
              <th style="width: 1%;"><a href="guild.php?action=view_guild&amp;error=3&amp;id='.$guild_id.'&amp;order_by=conline&amp;start='.$start.'&amp;dir='.$dir.'">'.( ( $order_by == "conline" ) ? '<img src="img/arr_'.( ( $dir ) ? "up" : "dw" ).'.gif" alt="" /> ' : '' ).lang("guild", "online").'</a></th>';

  if ($showcountryflag)
  {
    require_once "libs/misc_lib.php";

    $output .= '
              <th style="width: 1%;">'.lang("global", "country").'</th>';
  }

  $output .= '
              </tr>';
  // this_is_junk: WTF? O_o
  if ( $core == 1 )
    $members = $sql["char"]->query("SELECT gm.playerid AS cguid, c.name AS cname, c.`race` AS crace, c.`class` AS cclass,
      c.`level` AS clevel,
      gm.guildrank AS mrank, (SELECT rankname FROM guild_ranks WHERE guildid='".$guild_id."' AND rankid=mrank) AS rname,
      gm.publicNote AS pnote, gm.officerNote AS offnote, gender,
      c.`online` AS conline, c.`acct`, c.`timestamp` AS clogout
      FROM guild_data AS gm LEFT OUTER JOIN characters AS c ON c.guid=gm.playerid
      WHERE gm.guildid='".$guild_id."' ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
  else
    $members = $sql["char"]->query("SELECT gm.guid AS cguid, c.name AS cname, c.`race` AS crace, c.`class` AS cclass,
      c.`level` AS clevel,
      gm.rank AS mrank, (SELECT rname FROM guild_rank WHERE guildid='".$guild_id."' AND rid=mrank) AS rname,
      gm.pnote AS pnote, gm.offnote AS offnote, gender,
      c.`online` AS conline, c.`account` AS acct, c.`logout_time` AS clogout
      FROM guild_member AS gm LEFT OUTER JOIN characters AS c ON c.guid=gm.guid
      WHERE gm.guildid='".$guild_id."' ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);

  while ( $member = $sql["char"]->fetch_assoc($members) )
  {
    if ( $core == 1 )
    {
      $query = "SELECT * FROM accounts WHERE acct='".$member["acct"]."'";
      $result = $sql["logon"]->query($query);
      $result = $sql["logon"]->fetch_assoc($result);
      $user = $result["login"];
    }
    else
    {
      $query = "SELECT * FROM account WHERE id='".$member["acct"]."'";
      $result = $sql["logon"]->query($query);
      $result = $sql["logon"]->fetch_assoc($result);
      $user = $result["username"];
    }
    
    $result = $sql["mgr"]->query("SELECT SecurityLevel AS gm FROM config_accounts WHERE Login='".$user."'");
    $owner_gmlvl = $sql["logon"]->result($result, 0, "gm");

    if ( $owner_gmlvl >= 1073741824 )
      $owner_gmlvl -= 1073741824;

    $output .= '
            <tr>';
    // gm, guildleader or own account! are allowed to remove from guild
    $output .= ( ( $user_lvl >= $action_permission["delete"] || $amIguildleader || $member["acct"] == $user_id ) ? '<td><img src="img/aff_cross.png" alt="" onclick="answerBox(\''.lang("global", "delete").': &lt;font color=white&gt;'.$member["cname"].'&lt;/font&gt;&lt;br /&gt;'.lang("global", "are_you_sure").'\', \'guild.php?action=rem_char_from_guild&amp;realm='.$realmid.'&amp;id='.$member["cguid"].'&amp;guld_id='.$guild_id.'\');" class="guild_edit_delete_cursor" /></td>' : '<td></td>' );
    $output .= '
              <td><a href="char.php?id='.$member["cguid"].'">'.htmlentities($member["cname"], ENT_COMPAT, $site_encoding).'</a></td>
              <td><img src="img/c_icons/'.$member["crace"].'-'.$member["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($member["crace"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" /></td>
              <td><img src="img/c_icons/'.$member["cclass"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($member["cclass"]).'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" /></td>
              <td>'.char_get_level_color($member["clevel"]).'</td>
              <td>'.htmlentities($member["rname"], ENT_COMPAT, $site_encoding).' ('.$member["mrank"].')</td>
              <td>'.htmlentities($member["pnote"], ENT_COMPAT, $site_encoding).'</td>
              <td>'.htmlentities($member["offnote"], ENT_COMPAT, $site_encoding).'</td>
              <td><span style="font-weight: bold;">'.get_days_with_color($member["clogout"]).'</span></td>
              <td><img src="img/'.( ( $member["conline"] ) ? 'up' : 'down' ).'.gif" alt="" /></td>';

    if ( $showcountryflag )
    {
        // this_is_junk: apparently sometimes guilds end up with members who don't exist. O_o
        //               and because they don't exist, they don't have anything in their acct field.
        //               which misc_get_country_by_account() doesn't like.
        if ( $member["acct"] )
          $country = misc_get_country_by_account($member["acct"]);
        else
          $country = 0;

        $output .= '
              <td>'.( ($country["code"] ) ? '<img src="img/flags/'.$country["code"].'.png" onmousemove="oldtoolTip(\''.$country["country"].'\',\'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />' : '-' ).'</td>';
    }

              $output .= '
            </tr>';
  }
  unset($member);

  $output .= '
            <tr>
              <td align="right" class="hidden">'.generate_pagination("guild.php?action=view_guild&amp;error=3&amp;id=".$guild_id."&amp;order_by=".$order_by."&amp;dir=".!$dir, $guildmemberCount, $itemperpage, $start).'</td>
            </tr>
          </table>
          <br />';

  $output .= '
          <table class="hidden center">
            <tr>
              <td>';

  if ( $user_lvl >= $action_permission["delete"] || $amIguildleader )
  {
    makebutton(lang("guild", "del_guild"), "guild.php?action=del_guild&amp;realm=".$realm_id."&amp;id=".$guild_id."&amp;name=".$guild_data["name"]."\" type=\"wrn", 130);
    $output .= '
              </td>
              <td>';
  }

  makebutton(lang("guild", "guildbank"), "guildbank.php?id=".$guild_id, 130);
  $output .= '
              </td>
              <td>';

  makebutton(lang("guild", "show_guilds"), "guild.php\" type=\"def", 130);
  $output .= '
              </td>
            </tr>
          </table>
        </div>';
}


//#############################################################################
// ARE YOU SURE  YOU WOULD LIKE TO OPEN YOUR AIRBAG?
//#############################################################################
function show_del_guild()
{
  global $output, $characters_db, $logon_db, $realm_id,
    $action_permission, $user_lvl, $user_id, $sql, $core;

  if ( empty($_GET["realm"]) )
    $realmid = $realm_id;
  else
  {
    $realmid = $sql["logon"]->quote_smart($_GET["realm"]);
    if ( !is_numeric($realmid) )
      $realmid = $realm_id;
  }

  $id = $sql["logon"]->quote_smart($_GET["id"]);

  if ( $core == 1 )
    $q_amIguildleader = "SELECT 1 FROM guild WHERE guildid='".$id."' AND leaderguid IN (SELECT guid FROM characters WHERE acct='".$user_id."')";
  else
    $q_amIguildleader = "SELECT 1 FROM guild WHERE guildid='".$id."' AND leaderguid IN (SELECT guid FROM characters WHERE account='".$user_id."')";

  $r_amIguildleader = $sql["char"]->query($q_amIguildleader);
  $amIguildleader = $sql["char"]->result($r_amIguildleader, 0, "1");

  if ( $user_lvl < $action_permission["delete"] && !$amIguildleader )
    redirect("guild.php?error=6");

  $output .= '
        <div class="center_text">
          <h1><span class="error">'.lang("global", "are_you_sure").'</span></h1>
          <br />
          <span class="bold">'.lang("guild", "guild_id").': '.$id.' "'.$_GET["name"].'"<br />'.lang("global", "will_be_erased").'</span>
          <br />
          <br />
          <form action="guild.php?action=do_del_guild" method="post" id="form">
            <div class="no_display">
              <input type="hidden" name="check" value="'.(-$id).'" />
            </div>
            <table class="hidden center">
              <tr>
                <td>';

  makebutton(lang("global", "yes"), "javascript:do_submit()\" type=\"wrn", 130);
  $output .= '
                </td>
                <td>';

  makebutton(lang("global", "no"), "guild.php?action=view_guild&amp;id=".$id."\" type=\"def", 130);
  $output .= '
                </td>
              </tr>
            </table>
          </form>
        </div>';
}


//#############################################################################
// REMOVE GUILD
//#############################################################################
function do_del_guild()
{
  global $characters_db, $realm_id, $user_lvl, $user_id, $sql;

  require_once("libs/del_lib.php");

  if ( isset($_POST["check"]) && $_POST["check"] != "" )
  {
    $check = $sql["logon"]->quote_smart($_POST["check"]);
    $check = explode("-", $check);
  }

  for ( $i = 1; $i < count($check); $i++ )
  {
    if ( $check[$i] != "" )
    {
      if ( del_guild($check[$i], $realm_id) )
        $deleted_guilds++;
    }
  }

  redirect("guild.php");
}


//#############################################################################
//REMOVE CHAR FROM GUILD
//#############################################################################
function rem_char_from_guild()
{
  global $characters_db, $realm_id, $user_lvl, $user_id, $sql;

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

  if ( isset($_GET["id"]) )
    $guid = $_GET["id"];
  else
    redirect("guild.php?error=1");

  if ( !is_numeric($guid) )
    redirect("guild.php?error=5");

  if ( isset($_GET["guld_id"]) )
    $guld_id = $_GET["guld_id"];
  else
    redirect("guild.php?error=1");

  if ( !is_numeric($guld_id) )
    redirect("guild.php?error=5");

  if ( $core == 1)
    $q_amIguildleaderOrSelfRemoval = $sql["char"]->query("SELECT 1 FROM guild AS g
        LEFT OUTER JOIN guild_member AS gm ON gm.guildid=g.guildid
      WHERE g.guildid='".$guld_id."' AND
      (g.leaderguid IN (SELECT guid FROM characters WHERE account='".$user_id."')
      OR gm.guid IN (SELECT guid FROM characters WHERE account='".$user_id."' AND guid='".$guid."'))");
  else
    $q_amIguildleaderOrSelfRemoval = $sql["char"]->query("SELECT 1 FROM guild AS g
        LEFT OUTER JOIN guild_member AS gm ON gm.guildid=g.guildid
      WHERE g.guildid='".$guld_id."' AND
      (g.leaderguid IN (SELECT guid FROM characters WHERE account='".$user_id."')
      OR gm.guid IN (SELECT guid FROM characters WHERE account='".$user_id."' AND guid='".$guid."'))");

  $amIguildleaderOrSelfRemoval = $sql["char"]->result($q_amIguildleaderOrSelfRemoval, 0, "1");

  if ( $user_lvl < $action_permission["delete"] && !$amIguildleaderOrSelfRemoval )
    redirect("guild.php?error=6");

  if ( $core == 1 )
  {
    $char_data = $sql["char"]->query("SELECT data FROM `characters` WHERE guid='".$guid."'");
    $data = $sql["char"]->result($char_data, 0, "data");
    $data = explode(" ",$data);
    $data[CHAR_DATA_OFFSET_GUILD_ID] = 0;
    $data[CHAR_DATA_OFFSET_GUILD_RANK] = 0;
    $data = implode(" ",$data);
    $sql["char"]->query("UPDATE `characters` SET data='".$data."' WHERE guid='".$guid."'");
  }

  $sql["char"]->query("DELETE FROM guild_member WHERE guid='".$guid."'");
  redirect("guild.php?action=view_guild&amp;id=".$guld_id);
}

function doubledigit($inp)
{
  if ( strlen($inp) < 2 )
    return "0".$inp;
  else
    return $inp;
}


//#############################################################################
// MAIN
//#############################################################################
$err = ( ( isset($_GET["error"]) ) ? $_GET["error"] : NULL );

$output .= '
      <div class="bubble">
        <div class="top">';

switch ( $err )
{
  case 1:
    $output .= '
          <h1><span class="error">'.lang("global", "err_empty_fields").'</span></h1>';
    break;
  case 2:
    $output .= '
          <h1><span class="error">'.lang("global", "err_no_search_passed").'</span></h1>';
    break;
  case 3:
    $output .= '
          <h1>'.lang("guild", "guild").'</h1>';
    break;
  case 4:
    $output .= '
          <h1><span class="error">'.lang("guild", "guild_search_result").':</span></h1>';
    break;
  case 5:
    $output .= '
          <h1><span class="error">'.lang("global", "err_invalid_input").':</span></h1>';
    break;
  case 6:
    $output .= '
          <h1><span class="error">'.lang("global", "err_no_permission").':</span></h1>';
    break;
  default: //no error
    $output .= '
          <h1>'.lang("guild", "browse_guilds").'</h1>';
}

unset($err);

$output .= '
        </div>';

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

if ( $action == "view_guild" )
  view_guild();
elseif ( $action == "del_guild" )
  show_del_guild();
elseif ( $action == "do_del_guild" )
  do_del_guild();
elseif ( $action == "rem_char_from_guild" )
  rem_char_from_guild();
else
  browse_guilds();

unset($action);
unset($action_permission);

require_once "footer.php";

?>
