<?php
/*
    CoreManager, PHP Front End for ArcEmu, MaNGOS, and TrinityCore
    Copyright (C) 2010-2013  CoreManager Project

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
// SHOW CHAR PVP
//########################################################################################################################
function char_pvp()
{
  global $output, $realm_id, $characters_db, $logon_db, $corem_db, $action_permission,
    $site_encoding, $user_lvl, $user_name, $sql, $core;

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

  if ( $core == 1 )
    $result = $sql["char"]->query("SELECT acct, name, race, class, level, gender, arenaPoints,
      killsToday,
      killsYesterday,
      killsLifetime,
      honorToday,
      honorYesterday,
      honorPoints
      FROM characters WHERE guid='".$id."' LIMIT 1");
  else
    $result = $sql["char"]->query("SELECT account AS acct, name, race, class, level, gender, arenaPoints,
      todayKills AS killsToday,
      yesterdayKills AS killsYesterday,
      totalKills AS killsLifetime,
      todayHonorPoints AS honorToday,
      yesterdayHonorPoints AS honorYesterday,
      totalHonorPoints AS honorPoints
      FROM characters WHERE guid='".$id."' LIMIT 1");

  if ( $core == 1 )
  {
    // arenateams.data format: [week games] [week wins] [season games] [season wins]
    // arena team player structure [player_id] [week_played] [week_win] [season_played] [season_win] [rating]
    $query = "SELECT id, rating, type,
      SUBSTRING_INDEX(SUBSTRING_INDEX(data, ' ', 2), ' ', 1) AS games, 
      SUBSTRING_INDEX(SUBSTRING_INDEX(data, ' ', 2), ' ', -1) AS wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(data, ' ', -2), ' ', 1) AS played, 
      SUBSTRING_INDEX(SUBSTRING_INDEX(data, ' ', -2), ' ', -1) AS wins2,
      ranking,
      player_data1, player_data2, player_data3, player_data4, player_data5,
      player_data6, player_data7, player_data8, player_data9, player_data10,
      SUBSTRING_INDEX(player_data1, ' ', 1) AS player1_id,
      SUBSTRING_INDEX(player_data2, ' ', 1) AS player2_id,
      SUBSTRING_INDEX(player_data3, ' ', 1) AS player3_id,
      SUBSTRING_INDEX(player_data4, ' ', 1) AS player4_id,
      SUBSTRING_INDEX(player_data5, ' ', 1) AS player5_id,
      SUBSTRING_INDEX(player_data6, ' ', 1) AS player6_id,
      SUBSTRING_INDEX(player_data7, ' ', 1) AS player7_id,
      SUBSTRING_INDEX(player_data8, ' ', 1) AS player8_id,
      SUBSTRING_INDEX(player_data9, ' ', 1) AS player9_id,
      SUBSTRING_INDEX(player_data10, ' ', 1) AS player10_id,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data1, ' ', 2), ' ', -1) AS player1_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data1, ' ', 3), ' ', -1) AS player1_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data1, ' ', 4), ' ', -1) AS player1_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data1, ' ', 5), ' ', -1) AS player1_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data1, ' ', 6), ' ', -1) AS player1_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data2, ' ', 2), ' ', -1) AS player2_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data2, ' ', 3), ' ', -1) AS player2_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data2, ' ', 4), ' ', -1) AS player2_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data2, ' ', 5), ' ', -1) AS player2_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data2, ' ', 6), ' ', -1) AS player2_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data3, ' ', 2), ' ', -1) AS player3_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data3, ' ', 3), ' ', -1) AS player3_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data3, ' ', 4), ' ', -1) AS player3_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data3, ' ', 5), ' ', -1) AS player3_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data3, ' ', 6), ' ', -1) AS player3_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data4, ' ', 2), ' ', -1) AS player4_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data4, ' ', 3), ' ', -1) AS player4_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data4, ' ', 4), ' ', -1) AS player4_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data4, ' ', 5), ' ', -1) AS player4_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data4, ' ', 6), ' ', -1) AS player4_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data5, ' ', 2), ' ', -1) AS player5_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data5, ' ', 3), ' ', -1) AS player5_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data5, ' ', 4), ' ', -1) AS player5_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data5, ' ', 5), ' ', -1) AS player5_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data5, ' ', 6), ' ', -1) AS player5_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data6, ' ', 2), ' ', -1) AS player6_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data6, ' ', 3), ' ', -1) AS player6_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data6, ' ', 4), ' ', -1) AS player6_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data6, ' ', 5), ' ', -1) AS player6_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data6, ' ', 6), ' ', -1) AS player6_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data7, ' ', 2), ' ', -1) AS player7_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data7, ' ', 3), ' ', -1) AS player7_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data7, ' ', 4), ' ', -1) AS player7_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data7, ' ', 5), ' ', -1) AS player7_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data7, ' ', 6), ' ', -1) AS player7_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data8, ' ', 2), ' ', -1) AS player8_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data8, ' ', 3), ' ', -1) AS player8_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data8, ' ', 4), ' ', -1) AS player8_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data8, ' ', 5), ' ', -1) AS player8_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data8, ' ', 6), ' ', -1) AS player8_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data9, ' ', 2), ' ', -1) AS player9_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data9, ' ', 3), ' ', -1) AS player9_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data9, ' ', 4), ' ', -1) AS player9_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data9, ' ', 5), ' ', -1) AS player9_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data9, ' ', 6), ' ', -1) AS player9_rating,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data10, ' ', 2), ' ', -1) AS player10_week_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data10, ' ', 3), ' ', -1) AS player10_week_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data10, ' ', 4), ' ', -1) AS player10_season_played,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data10, ' ', 5), ' ', -1) AS player10_season_wins,
      SUBSTRING_INDEX(SUBSTRING_INDEX(player_data10, ' ', 6), ' ', -1) AS player10_rating
      FROM arenateams HAVING player1_id='".$id."' OR player2_id='".$id."' OR player3_id='".$id."' OR
         player4_id='".$id."' OR player5_id='".$id."' OR player6_id='".$id."' OR player7_id='".$id."' OR
          player8_id='".$id."' OR player9_id='".$id."' OR player10_id='".$id."'";

    $arena_team_query = $sql["char"]->query($query);
  }
  elseif ( $core == 2 )
  {
    $query = "SELECT *,arena_team.arenateamid AS id, rating, type,
      games_week AS games, wins_week AS wins, games_season AS played, wins_season AS wins2, rank AS ranking,
      (SELECT COUNT(*) FROM arena_team_member WHERE arenateamid=id) AS tot_chars
      FROM arena_team
        LEFT JOIN arena_team_stats ON arena_team_stats.arenateamid=arena_team.arenateamid
        LEFT JOIN arena_team_member ON arena_team_member.arenateamid=arena_team.arenateamid
      WHERE arena_team_member.guid='".$id."'";

    $arena_team_query = $sql["char"]->query($query);
  }
  else
  {
    $query = "SELECT *, arena_team.arenaTeamId AS id, rating, type,
      arena_team.weekGames AS games, arena_team.weekWins AS wins,
      arena_team.seasonGames AS played, arena_team.seasonWins AS wins2,
      rank AS ranking, arena_team_member.personalRating AS personalRating,
      arena_team_member.weekGames as played_week, arena_team_member.weekWins as wons_week,
      arena_team_member.seasonGames as played_season, arena_team_member.seasonWins as wons_season,
      (SELECT COUNT(*) FROM arena_team_member WHERE arenaTeamId=id) AS tot_chars
      FROM arena_team
        LEFT JOIN arena_team_member ON arena_team_member.arenaTeamId=arena_team.arenaTeamId
      WHERE arena_team_member.guid='".$id."'";

    $arena_team_query = $sql["char"]->query($query);
  }

  while ( $arena_row = $sql["char"]->fetch_assoc($arena_team_query) )
  {
    // Trinity stores Team type as 2, 3, 5; ArcEmu & MaNGOS use 0, 1, 2
    if ( $core != 3 )
    {
      if ( $arena_row["type"] == 0 )
        $type = 2;
      elseif ( $arena_row["type"] == 1 )
        $type = 3;
      elseif ( $arena_row["type"] == 2 )
        $type = 5;
    }
    else
      $type = $arena_row["type"];

    if ( $type == 2 )
      $arena_team2 = $arena_row;
    elseif ( $type == 3 )
      $arena_team3 = $arena_row;
    elseif ( $type == 5 )
      $arena_team5 = $arena_row;
  }

  $arenateam_data2 = arenateam_data($arena_team2["id"]);
  $arenateam_data3 = arenateam_data($arena_team3["id"]);
  $arenateam_data5 = arenateam_data($arena_team5["id"]);

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
    $view_mod = $s_fields["View_Mod_PvP"];

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

    if ( ( $view_override ) || ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
    {
      $output .= '
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
            </div>
            <div class="tab_content center">
              <div class="tab">
                <ul>
                  <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "char_sheet").'</a></li>';

      if ( ( char_get_class_name($char["class"]) == "Hunter" ) && ( $view_pets_override ) )
        $output .= '
                  <li><a href="char_pets.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "pets").'</a></li>';

      if ( $view_rep_override )
        $output .= '
                  <li><a href="char_rep.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "reputation").'</a></li>';

      if ( $view_skill_override )
        $output .= '
                  <li><a href="char_skill.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "skills").'</a></li>';

      $output .= '
                  <li class="selected"><a href="char_pvp.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "pvp").'</a></li>';

      if ( ( $owner_name == $user_name ) || ( $user_lvl >= get_page_permission("insert", "char_mail.php") ) )
        $output .= '
                  <li><a href="char_mail.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "mail").'</a></li>';

      $output .= '
                </ul>
              </div>
              <div class="tab_content2 center center_text">
                <span class="bold">
                  '.htmlentities($char["name"], ENT_COMPAT, $site_encoding).' -
                  <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
                  <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" /> - '.lang("char", "level_short").char_get_level_color($char["level"]).'
                </span>
                <br />
                <br />
                <table class="lined" id="ch_pvp_top">
                  <tr>
                    <td colspan="4">'.lang("char", "honor").': <span id="ch_pvp_highlight">'.$char["honorPoints"].'</span> <img src="img/money_'.( ( char_get_side_id($char["race"]) ) ? 'horde' : 'alliance' ).'.gif" alt="" /></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>'.lang("char", "today").'</td>
                    <td>'.lang("char", "yesterday").'</td>
                    <td>'.lang("char", "lifetime").'</td>
                  </tr>
                  <tr>
                    <td>'.lang("char", "kills").'</td>
                    <td>'.$char["killsToday"].'</td>
                    <td>'.$char["killsYesterday"].'</td>
                    <td>'.$char["killsLifetime"].'</td>
                  </tr>
                  <tr>
                    <td>'.lang("char", "honor").'</td>
                    <td>'.$char["honorToday"].'</td>
                    <td>'.$char["honorYesterday"].'</td>
                    <td>-</td>
                  </tr>
                </table>
                <br />
                <table class="lined" id="ch_pvp_main">
                  <tr>
                    <td colspan="5">'.lang("char", "arena").': <span class="ch_pvp_highlight">'.$char["arenaPoints"].'</span> <img src="img/money_arena.gif" alt="" /></td>
                  </tr>';

        if ( $arena_team2 != NULL )
        {
          // ArcEmu: find the data set that is ours
          if ( $core == 1 )
          {
            if ( $arena_team2["player1_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player1_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player1_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player1_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player1_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player1_rating"];
            }

            if ( $arena_team2["player2_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player2_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player2_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player2_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player2_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player2_rating"];
            }

            if ( $arena_team2["player3_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player3_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player3_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player3_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player3_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player3_rating"];
            }

            if ( $arena_team2["player4_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player4_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player4_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player4_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player4_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player4_rating"];
            }

            if ( $arena_team2["player5_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player5_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player5_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player5_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player5_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player5_rating"];
            }

            if ( $arena_team2["player6_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player6_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player6_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player6_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player6_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player6_rating"];
            }

            if ( $arena_team2["player7_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player7_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player7_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player7_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player7_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player7_rating"];
            }

            if ( $arena_team2["player8_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player8_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player8_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player8_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player8_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player8_rating"];
            }

            if ( $arena_team2["player9_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player9_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player9_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player9_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player9_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player9_rating"];
            }

            if ( $arena_team2["player10_id"] == $id )
            {
              $arena_team2["played_week"] = $arena_team2["player10_week_played"];
              $arena_team2["wons_week"] = $arena_team2["player10_week_wins"];
              $arena_team2["played_season"] = $arena_team2["player10_season_played"];
              $arena_team2["wons_season"] = $arena_team2["player10_season_wins"];
              $arena_team2["personal_rating"] = $arena_team2["player10_rating"];
            }
          }

          $output .= '
                    <tr>
                      <td rowspan="9" class="ch_pvp_banner_space">
                        <div class="arena_banner">
                          <img src="libs/banner_lib.php?action=banner&amp;f='.$arenateam_data2["banner_style"].'&amp;r='.$arenateam_data2["BackgroundColor"][1].'&amp;g='.$arenateam_data2["BackgroundColor"][2].'&amp;b='.$arenateam_data2["BackgroundColor"][3].'" class="banner_img" alt="" />
                          <img src="libs/banner_lib.php?action=border&amp;f='.$arenateam_data2["BorderStyle"].'&amp;f2='.$arenateam_data2["banner_style"].'&amp;r='.$arenateam_data2["BorderColor"][1].'&amp;g='.$arenateam_data2["BorderColor"][2].'&amp;b='.$arenateam_data2["BorderColor"][3].'" class="border_img" alt="" />
                          <img src="libs/banner_lib.php?action=emblem&amp;f='.$arenateam_data2["EmblemStyle"].'&amp;r='.$arenateam_data2["EmblemColor"][1].'&amp;g='.$arenateam_data2["EmblemColor"][2].'&amp;b='.$arenateam_data2["EmblemColor"][3].'&amp;s=0.55" class="emblem_img" alt="" />
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"><a href="arenateam.php?action=view_team&amp;error=3&amp;id='.$arenateam_data2["id"].'">'.$arenateam_data2["name"].'</a></td>
                      <td colspan="2">'.lang("char", "team").' '.lang("char", "rating").': <span class="ch_pvp_highlight">'.$arena_team2["rating"].'</span></td>
                    </tr>
                    <tr>
                      <td><span class="ch_pvp_dim">'.lang("char", "team").'</span></td>
                      <td>'.lang("char", "games").'</td>
                      <td>'.lang("char", "winloss").'</td>
                      <td>'.lang("char", "ratio").'</td>
                    </tr>
                    <tr>
                      <td>'.lang("char", "thisweek").'</td>
                      <td>'.$arena_team2["games"].'</td>
                      <td>'.$arena_team2["wins"].'-'.($arena_team2["games"]-$arena_team2["wins"]).'</td>
                      <td>'.(($arena_team2["wins"]/$arena_team2["games"])*100).'%</td>
                    </tr>
                    <tr>
                      <td>'.lang("char", "thisseason").'</td>
                      <td>'.$arena_team2["played"].'</td>
                      <td>'.$arena_team2["wins2"].'-'.($arena_team2["played"]-$arena_team2["wins2"]).'</td>
                      <td>'.(($arena_team2["wins2"]/$arena_team2["played"])*100).'%</td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <span class="ch_pvp_dim">'.$char["name"].'</span>
                      </td>
                      <td colspan="2">
                        <span>'.lang("char", "rating").'</span>: <span class="ch_pvp_highlight">'.$arena_team2["personal_rating"].'</span>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"></td>
                      <td>'.lang("char", "played").'</td>
                      <td>'.lang("char", "winloss").'</td>
                    </tr>
                    <tr>
                      <td colspan="2">'.lang("char", "thisweek").'</td>
                      <td>'.$arena_team2["played_week"].'</td>
                      <td>'.$arena_team2["wons_week"].'-'.($arena_team2["played_week"]-$arena_team2["wons_week"]).'</td>
                    </tr>
                    <tr>
                      <td colspan="2">'.lang("char", "thisseason").'</td>
                      <td>'.$arena_team2["played_season"].'</td>
                      <td>'.$arena_team2["wons_season"].'-'.($arena_team2["played_season"]-$arena_team2["wons_season"]).'</td>
                    </tr>';
        }
        else
        {
          $output .= '
                    <tr>
                      <td rowspan="9" class="ch_pvp_banner_space">
                        <div class="arena_banner">
                          <img src="img/blank.gif" class="banner_img" alt="" />
                          <img src="img/blank.gif" class="border_img" alt="" />
                          <img src="img/blank.gif" class="emblem_img" alt="" />
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4"><span class="ch_pvp_dim">('.lang("arenateam", "2MT").')</span></td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>';
        }

        if ( $arena_team3 != NULL )
        {
          // ArcEmu: find the data set that is ours
          if ( $core == 1 )
          {
            if ( $arena_team3["player1_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player1_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player1_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player1_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player1_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player1_rating"];
            }

            if ( $arena_team3["player2_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player2_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player2_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player2_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player2_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player2_rating"];
            }

            if ( $arena_team3["player3_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player3_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player3_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player3_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player3_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player3_rating"];
            }

            if ( $arena_team3["player4_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player4_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player4_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player4_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player4_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player4_rating"];
            }

            if ( $arena_team3["player5_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player5_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player5_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player5_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player5_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player5_rating"];
            }

            if ( $arena_team3["player6_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player6_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player6_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player6_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player6_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player6_rating"];
            }

            if ( $arena_team3["player7_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player7_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player7_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player7_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player7_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player7_rating"];
            }

            if ( $arena_team3["player8_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player8_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player8_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player8_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player8_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player8_rating"];
            }

            if ( $arena_team3["player9_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player9_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player9_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player9_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player9_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player9_rating"];
            }

            if ( $arena_team3["player10_id"] == $id )
            {
              $arena_team3["played_week"] = $arena_team3["player10_week_played"];
              $arena_team3["wons_week"] = $arena_team3["player10_week_wins"];
              $arena_team3["played_season"] = $arena_team3["player10_season_played"];
              $arena_team3["wons_season"] = $arena_team3["player10_season_wins"];
              $arena_team3["personal_rating"] = $arena_team3["player10_rating"];
            }
          }

          $output .= '
                    <tr>
                      <td rowspan="9" class="ch_pvp_banner_space">
                        <div class="arena_banner">
                          <img src="libs/banner_lib.php?action=banner&amp;f='.$arenateam_data3["banner_style"].'&amp;r='.$arenateam_data3["BackgroundColor"][1].'&amp;g='.$arenateam_data3["BackgroundColor"][2].'&amp;b='.$arenateam_data3["BackgroundColor"][3].'" class="banner_img" alt="" />
                          <img src="libs/banner_lib.php?action=border&amp;f='.$arenateam_data3["BorderStyle"].'&amp;f2='.$arenateam_data3["banner_style"].'&amp;r='.$arenateam_data3["BorderColor"][1].'&amp;g='.$arenateam_data3["BorderColor"][2].'&amp;b='.$arenateam_data3["BorderColor"][3].'" class="border_img" alt="" />
                          <img src="libs/banner_lib.php?action=emblem&amp;f='.$arenateam_data3["EmblemStyle"].'&amp;r='.$arenateam_data3["EmblemColor"][1].'&amp;g='.$arenateam_data3["EmblemColor"][2].'&amp;b='.$arenateam_data3["EmblemColor"][3].'&amp;s=0.55" class="emblem_img" alt="" />
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"><a href="arenateam.php?action=view_team&amp;error=3&amp;id='.$arenateam_data3["id"].'">'.$arenateam_data3["name"].'</a></td>
                      <td colspan="2">'.lang("char", "team").' '.lang("char", "rating").': <span class="ch_pvp_highlight">'.$arena_team3["rating"].'</span></td>
                    </tr>
                    <tr>
                      <td><span class="ch_pvp_dim">'.lang("char", "team").'</span></td>
                      <td>'.lang("char", "games").'</td>
                      <td>'.lang("char", "winloss").'</td>
                      <td>'.lang("char", "ratio").'</td>
                    </tr>
                    <tr>
                      <td>'.lang("char", "thisweek").'</td>
                      <td>'.$arena_team3["games"].'</td>
                      <td>'.$arena_team3["wins"].'-'.($arena_team3["games"]-$arena_team3["wins"]).'</td>
                      <td>'.(($arena_team3["wins"]/$arena_team3["games"])*100).'%</td>
                    </tr>
                    <tr>
                      <td>'.lang("char", "thisseason").'</td>
                      <td>'.$arena_team3["played"].'</td>
                      <td>'.$arena_team3["wins2"].'-'.($arena_team3["played"]-$arena_team3["wins2"]).'</td>
                      <td>'.(($arena_team3["wins2"]/$arena_team3["played"])*100).'%</td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <span class="ch_pvp_dim">'.$char["name"].'</span>
                      </td>
                      <td colspan="2">
                        <span>'.lang("char", "rating").'</span>: <span class="ch_pvp_highlight">'.$arena_team3["personal_rating"].'</span>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"></td>
                      <td>'.lang("char", "played").'</td>
                      <td>'.lang("char", "winloss").'</td>
                    </tr>
                    <tr>
                      <td colspan="2">'.lang("char", "thisweek").'</td>
                      <td>'.$arena_team3["played_week"].'</td>
                      <td>'.$arena_team3["wons_week"].'-'.($arena_team3["played_week"]-$arena_team3["wons_week"]).'</td>
                    </tr>
                    <tr>
                      <td colspan="2">'.lang("char", "thisseason").'</td>
                      <td>'.$arena_team3["played_season"].'</td>
                      <td>'.$arena_team3["wons_season"].'-'.($arena_team3["played_season"]-$arena_team3["wons_season"]).'</td>
                    </tr>';
        }
        else
        {
          $output .= '
                    <tr>
                      <td rowspan="9" class="ch_pvp_banner_space">
                        <div class="arena_banner">
                          <img src="img/blank.gif" class="banner_img" alt="" />
                          <img src="img/blank.gif" class="border_img" alt="" />
                          <img src="img/blank.gif" class="emblem_img" alt="" />
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4"><span class="ch_pvp_dim">('.lang("arenateam", "3MT").')</span></td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>';
        }

        if ( $arena_team5 != NULL )
        {
          // ArcEmu: find the data set that is ours
          if ( $core == 1 )
          {
            if ( $arena_team5["player1_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player1_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player1_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player1_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player1_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player1_rating"];
            }

            if ( $arena_team5["player2_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player2_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player2_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player2_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player2_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player2_rating"];
            }

            if ( $arena_team5["player3_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player3_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player3_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player3_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player3_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player3_rating"];
            }

            if ( $arena_team5["player4_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player4_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player4_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player4_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player4_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player4_rating"];
            }

            if ( $arena_team5["player5_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player5_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player5_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player5_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player5_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player5_rating"];
            }

            if ( $arena_team5["player6_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player6_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player6_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player6_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player6_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player6_rating"];
            }

            if ( $arena_team5["player7_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player7_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player7_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player7_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player7_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player7_rating"];
            }

            if ( $arena_team5["player8_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player8_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player8_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player8_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player8_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player8_rating"];
            }

            if ( $arena_team5["player9_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player9_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player9_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player9_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player9_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player9_rating"];
            }

            if ( $arena_team5["player10_id"] == $id )
            {
              $arena_team5["played_week"] = $arena_team5["player10_week_played"];
              $arena_team5["wons_week"] = $arena_team5["player10_week_wins"];
              $arena_team5["played_season"] = $arena_team5["player10_season_played"];
              $arena_team5["wons_season"] = $arena_team5["player10_season_wins"];
              $arena_team5["personal_rating"] = $arena_team5["player10_rating"];
            }
          }

          $output .= '
                    <tr>
                      <td rowspan="9" class="ch_pvp_banner_space">
                        <div class="arena_banner">
                          <img src="libs/banner_lib.php?action=banner&amp;f='.$arenateam_data5["banner_style"].'&amp;r='.$arenateam_data5["BackgroundColor"][1].'&amp;g='.$arenateam_data5["BackgroundColor"][2].'&amp;b='.$arenateam_data5["BackgroundColor"][3].'" class="banner_img" alt="" />
                          <img src="libs/banner_lib.php?action=border&amp;f='.$arenateam_data5["BorderStyle"].'&amp;f2='.$arenateam_data5["banner_style"].'&amp;r='.$arenateam_data5["BorderColor"][1].'&amp;g='.$arenateam_data5["BorderColor"][2].'&amp;b='.$arenateam_data5["BorderColor"][3].'" class="border_img" alt="" />
                          <img src="libs/banner_lib.php?action=emblem&amp;f='.$arenateam_data5["EmblemStyle"].'&amp;r='.$arenateam_data5["EmblemColor"][1].'&amp;g='.$arenateam_data5["EmblemColor"][2].'&amp;b='.$arenateam_data5["EmblemColor"][3].'&amp;s=0.55" class="emblem_img" alt="" />
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"><a href="arenateam.php?action=view_team&amp;error=3&amp;id='.$arenateam_data5["id"].'">'.$arenateam_data5["name"].'</a></td>
                      <td colspan="2">'.lang("char", "team").' '.lang("char", "rating").': <span class="ch_pvp_highlight">'.$arena_team5["rating"].'</span></td>
                    </tr>
                    <tr>
                      <td><span class="ch_pvp_dim">'.lang("char", "team").'</span></td>
                      <td>'.lang("char", "games").'</td>
                      <td>'.lang("char", "winloss").'</td>
                      <td>'.lang("char", "ratio").'</td>
                    </tr>
                    <tr>
                      <td>'.lang("char", "thisweek").'</td>
                      <td>'.$arena_team5["games"].'</td>
                      <td>'.$arena_team5["wins"].'-'.($arena_team5["games"]-$arena_team5["wins"]).'</td>
                      <td>'.(($arena_team5["wins"]/$arena_team5["games"])*100).'%</td>
                    </tr>
                    <tr>
                      <td>'.lang("char", "thisseason").'</td>
                      <td>'.$arena_team5["played"].'</td>
                      <td>'.$arena_team5["wins2"].'-'.($arena_team5["played"]-$arena_team5["wins2"]).'</td>
                      <td>'.(($arena_team5["wins2"]/$arena_team5["played"])*100).'%</td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <span class="ch_pvp_dim">'.$char["name"].'</span>
                      </td>
                      <td colspan="2">
                        <span>'.lang("char", "rating").'</span>: <span class="ch_pvp_highlight">'.$arena_team2["personal_rating"].'</span>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2"></td>
                      <td>'.lang("char", "played").'</td>
                      <td>'.lang("char", "winloss").'</td>
                    </tr>
                    <tr>
                      <td colspan="2">'.lang("char", "thisweek").'</td>
                      <td>'.$arena_team5["played_week"].'</td>
                      <td>'.$arena_team5["wons_week"].'-'.($arena_team5["played_week"]-$arena_team5["wons_week"]).'</td>
                    </tr>
                    <tr>
                      <td colspan="2">'.lang("char", "thisseason").'</td>
                      <td>'.$arena_team5["played_season"].'</td>
                      <td>'.$arena_team5["wons_season"].'-'.($arena_team5["played_season"]-$arena_team5["wons_season"]).'</td>
                    </tr>';
        }
        else
        {
          $output .= '
                    <tr>
                      <td rowspan="9" class="ch_pvp_banner_space">
                        <div class="arena_banner">
                          <img src="img/blank.gif" class="banner_img" alt="" />
                          <img src="img/blank.gif" class="border_img" alt="" />
                          <img src="img/blank.gif" class="emblem_img" alt="" />
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4"><span class="ch_pvp_dim">('.lang("arenateam", "5MT").')</span></td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="4">&nbsp;</td>
                    </tr>';
        }

      $output .= '
                </table>
                <br />
              </div>
              <br />
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
          <!-- end of char_achieve.php -->';
    }
    else
      error(lang("char", "no_permission"));
  }
  else
    error(lang("char", "no_char_found"));

}

function arenateam_data($arenateam_id)
{
  global $sql, $core;

  if ( $core == 1 )
    $query = $sql["char"]->query("SELECT id, name, type,
    INET_NTOA(backgroundcolour) AS BackgroundColor,
    INET_NTOA(bordercolour) AS BorderColor,
    INET_NTOA(emblemcolour) AS EmblemColor,
    emblemstyle AS EmblemStyle, borderstyle AS BorderStyle
    FROM arenateams
    WHERE id='".$arenateam_id."'");
  elseif ( $core == 2 )
    $query = $sql["char"]->query("SELECT arenateamid AS id, name, type,
    INET_NTOA(BackgroundColor) AS BackgroundColor,
    INET_NTOA(BorderColor) AS BorderColor,
    INET_NTOA(EmblemColor) AS EmblemColor,
    EmblemStyle, BorderStyle
    FROM arena_team
    WHERE arenateamid='".$arenateam_id."'");
  else
    $query = $sql["char"]->query("SELECT arenaTeamId AS id, name, type,
    INET_NTOA(BackgroundColor) AS BackgroundColor,
    INET_NTOA(BorderColor) AS BorderColor,
    INET_NTOA(EmblemColor) AS EmblemColor,
    EmblemStyle, BorderStyle
    FROM arena_team
    WHERE arenaTeamId='".$arenateam_id."'");

  $arenateam_data = $sql["char"]->fetch_assoc($query);

  // extract banner colors
  $arenateam_data["BackgroundColor"] = explode(".", $arenateam_data["BackgroundColor"]);
  $arenateam_data["BorderColor"] = explode(".", $arenateam_data["BorderColor"]);
  $arenateam_data["EmblemColor"] = explode(".", $arenateam_data["EmblemColor"]);

  // Trinity stores Team type as 2, 3, 5; ArcEmu & MaNGOS use 0, 1, 2
  if ( $core != 3 )
  {
    if ( $arenateam_data["type"] == 0 )
      $arenateam_data["banner_style"] = 2;
    elseif ( $arenateam_data["type"] == 1 )
      $arenateam_data["banner_style"] = 3;
    elseif ( $arenateam_data["type"] == 2 )
      $arenateam_data["banner_style"] = 5;
  }
  else
  {
    if ( $arenateam_data["type"] == 2 )
      $arenateam_data["banner_style"] = 2;
    elseif ( $arenateam_data["type"] == 3 )
      $arenateam_data["banner_style"] = 3;
    elseif ( $arenateam_data["type"] == 5 )
      $arenateam_data["banner_style"] = 5;
  }

  return $arenateam_data;
}


//########################################################################################################################
// MAIN
//########################################################################################################################

//$action = (isset($_GET["action"])) ? $_GET["action"] : NULL;

$output .= '
      <div class="bubble">';

char_pvp();

unset($action_permission);

require_once "footer.php";


?>
