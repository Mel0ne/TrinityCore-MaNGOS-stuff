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
require_once "libs/spell_lib.php";

// minimum permission to view page
valid_login($action_permission["view"]);

//########################################################################################################################
// SHOW CHARACTER TALENTS
//########################################################################################################################
function char_talent()
{
  global $output, $realm_id, $characters_db, $corem_db, $dbc_db, $server, $action_permission,
    $site_encoding, $user_lvl, $user_name, $base_datasite, $spell_datasite, $sql, $core;

  // this page uses wowhead tooltops
  wowhead_tt();

  // we need at least an id or we would have nothing to show
  if ( empty($_GET["id"]) )
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
  $id = $sql["char"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    error(lang("global", "empty_fields"));

  if ( $core == 1 )
    $result = $sql["char"]->query("SELECT acct, name, race, class, level, gender,
      CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(data, ' ', ".(CHAR_DATA_OFFSET_POINTS1+1)."), ' ', -1) AS UNSIGNED) AS talent_points
      FROM characters WHERE guid='".$id."' LIMIT 1");
  else
    $result = $sql["char"]->query("SELECT account AS acct, name, race, class, level, gender
      FROM characters WHERE guid='".$id."' LIMIT 1");

  if ( $sql["char"]->num_rows($result) )
  {
    $char = $sql["char"]->fetch_assoc($result);

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
    $view_mod = $s_fields["View_Mod_Talent"];

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

    $view_achieve_override = false;
    if ( $s_fields["View_Mod_AcView_Mod_Acieve"] > 0 )
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

    if ( ( $view_override ) || ( $user_lvl > $owner_gmlvl ) || ( $owner_name === $user_name ) || ( $user_lvl == $action_permission["delete"] ) )
    {
      if ( strlen($_GET["curspec"]) == 0 )
      {
        if ( $core == 1 )
          $spec_query = "SELECT currentspec FROM characters WHERE guid='".$id."'";
        else
          $spec_query = "SELECT activespec AS currentspec FROM characters WHERE guid='".$id."'";
        $spec_results = $sql["char"]->query($spec_query);
        $spec_field = $sql["char"]->fetch_assoc($spec_results);
        $cur_spec = $spec_field["currentspec"] + 1;
        if ( $cur_spec == 1 )
          $opp_spec = 2;
        else
          $opp_spec = 1;
      }
      else
      {
        $cur_spec = $_GET["curspec"];
        if ( $cur_spec == 1 )
          $opp_spec = 2;
        else
          $opp_spec = 1;
      }
      if ( $core == 1 )
      {
        // this_is_junk: ArcEmu stores talents in a characters table field in the following format:
        //               [talent id][spell offset],[talent id2][spell offset2],...[talent idN][spell offsetN],
        //               So, we have to explode it into an array, then into a pair of arrays.
        $result = $sql["char"]->query("SELECT talents".$cur_spec." FROM characters WHERE guid='".$id."'");
        $talent_list = $sql["char"]->result($result, 0);
        $talent_list = substr($talent_list, 0, strlen($talent_list) - 1);
        $talent_list = explode(",", $talent_list);
        $talents = array();
        $talent_ranks = array();
        $pick = 0;
        foreach ( $talent_list as $t )
        {
          if ( $pick )
          {
            array_push($talent_ranks, $t);
            $pick = 0;
          }
          else
          {
            array_push($talents, $t);
            $pick = 1;
          }
        }
      }
      elseif ( $core == 2 )
      {
        $query = "SELECT * FROM character_talent
                    LEFT JOIN `".$dbc_db["name"]."`.talent ON character_talent.talent_id=talent.ID
                  WHERE guid='".$id."' AND spec='".($cur_spec-1)."'";
        $result = $sql["char"]->query($query);
        $talents = array();
        while ( $row = $sql["char"]->fetch_assoc($result) )
        {
          $cur_rank = $row["current_rank"] + 1;
          array_push($talents, $row["Spell".$cur_rank]);
          array_push($talent_ranks, 0);
        }
      }
      else
      {
        $query = "SELECT * FROM character_talent WHERE guid='".$id."' AND spec='".($cur_spec-1)."'";
        $result = $sql["char"]->query($query);
        $talents = array();
        while ( $row = $sql["char"]->fetch_assoc($result) )
        {
          array_push($talents, $row["spell"]);
          array_push($talent_ranks, 0);
        }
      }
      
      $output .= '
              <div class="tab">
              <ul>
                <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "char_sheet").'</a></li>';

      if ( $view_inv_override )
        $output .= '
                <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "inventory").'</a></li>';

      $output .= '
                <li class="selected"><a href="char_talent.php?id='.$id.'&amp;realm='.$realmid.'">'.lang("char", "talents").'</a></li>';

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
            <div class="tab_content center center_text">
              <span class="bold">'.htmlentities($char["name"], ENT_COMPAT, $site_encoding).' - </span>
              <img src="img/c_icons/'.$char["race"].'-'.$char["gender"].'.gif" onmousemove="oldtoolTip(\''.char_get_race_name($char["race"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
              <img src="img/c_icons/'.$char["class"].'.gif" onmousemove="oldtoolTip(\''.char_get_class_name($char["class"]).'\', \'old_item_tooltip\')" onmouseout="oldtoolTip()" alt="" />
              <span class="bold"> - '.lang("char", "level_short").char_get_level_color($char["level"]).'</span>
              <br />
              <br />';
      if ( $cur_spec == 1 )
        $output .= '
              <span class="bold">'.lang("char", "talentspec").': '.$cur_spec.'&nbsp;<a href="char_talent.php?id='.$id.'&amp;realm='.$realm_id.'&amp;curspec='.$opp_spec.'">'.$opp_spec.'</a></span><br />';
      else
        $output .= '
              <span class="bold">'.lang("char", "talentspec").': <a href="char_talent.php?id='.$id.'&amp;realm='.$realm_id.'&amp;curspec='.$opp_spec.'">'.$opp_spec.'</a>&nbsp;'.$cur_spec.'</span><br />';

      if ( count($talents) > 1 )
      {
        $output .= '
              <table class="lined" id="ch_tal_main">
                <tr valign="top" align="center">';

        $talent_rate = ( ( isset($server[$realmid]["talent_rate"]) ) ? $server[$realmid]["talent_rate"] : 1 );
        $talent_points = ($char["level"] - 9) * $talent_rate;
        $talent_points_left = $char["talent_points"];
        $talent_points_used = $talent_points - $talent_points_left;

        $tabs = array();
        $l = 0;

        for ( $i = 0; $i < count($talents); $i++ )
        {
          if ( $core == 1 )
          {
            $talent_spell = $sql["dbc"]->query("SELECT spell".($talent_ranks[$i] + 1)." FROM talent WHERE id='".$talents[$i]."'");
            $talent_spell = $sql["dbc"]->result($talent_spell,0);
          }
          else
          {
            $talent_spell = $talents[$i];
          }

          if ( $tab = $sql["dbc"]->fetch_assoc($sql["dbc"]->query("SELECT TalentTab, Row, Col, Talent1, TalentCount1 FROM talent WHERE spell5='".$talent_spell."' LIMIT 1")) )
          {
              if ( isset($tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]]) )
                $l -=$tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]][1];

              $tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]] = array($talent_spell, "5", "5");
              $l += 5;
              if ( $tab["Talent1"] )
                talent_dependencies($tabs, $tab, $l);
          }
          elseif ( $tab = $sql["dbc"]->fetch_assoc($sql["dbc"]->query("SELECT TalentTab, Row, Col, Talent1, TalentCount1 FROM talent WHERE spell4='".$talent_spell."' LIMIT 1")) )
          {
              if ( isset($tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]]) )
                $l -=$tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]][1];

              $tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]] = array($talent_spell, "4", ( ( $tab["Spell5"] ) ? "2" : "5" ));
              $l += 4;
              if ( $tab["Talent1"] )
                talent_dependencies($tabs, $tab, $l);
          }
          elseif ( $tab = $sql["dbc"]->fetch_assoc($sql["dbc"]->query("SELECT TalentTab, Row, Col, Talent1, TalentCount1 FROM talent WHERE spell3='".$talent_spell."' LIMIT 1")) )
          {
              if ( isset($tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]]) )
                $l -=$tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]][1];

              $tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]] = array($talent_spell, "3", ( ( $tab["Spell4"] ) ? "2" : "5" ));
              $l += 3;
              if ( $tab["Talent1"] )
                talent_dependencies($tabs, $tab, $l);
          }
          elseif ( $tab = $sql["dbc"]->fetch_assoc($sql["dbc"]->query("SELECT TalentTab, Row, Col, Talent1, TalentCount1 FROM talent WHERE spell2='".$talent_spell."' LIMIT 1")) )
          {
              if ( isset($tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]]) )
                $l -=$tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]][1];

              $tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]] = array($talent_spell, "2", ( ( $tab["Spell3"] ) ? "2" : "5" ));
              $l += 2;
              if ( $tab["Talent1"] )
                talent_dependencies($tabs, $tab, $l);
          }
          elseif ( $tab = $sql["dbc"]->fetch_assoc($sql["dbc"]->query("SELECT TalentTab, Row, Col, Talent1, TalentCount1 FROM talent WHERE spell1='".$talent_spell."' LIMIT 1")) )
          {
              if ( isset($tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]]) )
                $l -=$tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]][1];

              $tabs[$tab["TalentTab"]][$tab["Row"]][$tab["Col"]] = array($talent_spell,'1', ( ( $tab["Spell2"] ) ? "2" : "5" ));
              $l += 1;
              if ( $tab["Talent1"] )
                talent_dependencies($tabs, $tab, $l);
          }
        }
        unset($tab);
        unset($talent);

        $class_name = get_class_name($char["class"]);
        foreach ( $tabs as $k=>$data )
        {
          $talent_name = $sql["dbc"]->result($sql["dbc"]->query("SELECT name FROM talenttab WHERE id='".$k."'"), 0, "name");
          $talent_name = str_replace(" ", "", $talent_name);
          $points = 0;
          $output .= '
                  <td>
                    <table class="hidden" id="ch_tal_0_width">
                     <tr>
                       <td colspan="6" style="ch_tal_0_bottom_width">
                       </td>
                     </tr>
                     <tr>';
          for ( $i = 0; $i < 11; ++$i )
          {
            for ( $j = 0; $j < 4; ++$j )
            {
              if ( isset($data[$i][$j]) )
              {
                // this_is_junk: style left hardcoded because it's calculated.
                $output .= '
                        <td valign="bottom" align="center" style="border-top-width: 0px;border-bottom-width: 0px;background-attachment:fixed;background:url(./img/TALENTFRAME/'.$class_name.$talent_name.'.png) '.($j*(-50)).'px '.($i*(-50)).'px">
                          <a href="'.$base_datasite.$spell_datasite.$data[$i][$j][0].'" rel="external">
                            <img src="'.spell_get_icon($data[$i][$j][0]).'" width="36" height="36" class="icon_border_'.$data[$i][$j][2].'" alt="" />
                          </a>
                          <div class="ch_tal_level_shadow">'.$data[$i][$j][1].'</div>
                          <div class="ch_tal_level">'.$data[$i][$j][1].'</div>
                        </td>';
                $points += $data[$i][$j][1];
              }
              else
                $output .= '
                        <td valign="bottom" align="center" style="border-top-width: 0px;border-bottom-width: 0px;background-attachment:fixed;background:url(./img/TALENTFRAME/'.$class_name.$talent_name.'.png) '.($j*(-50)).'px '.($i*(-50)).'px">
                          <img src="img/blank.gif" width="44" height="44" alt="" />
                        </td>';
            }
            $output .= '
                      </tr>
                      <tr>';
          }
          $output .= '
                       <td colspan="6" id="ch_tal_0_top_bottom_width">
                       </td>
                     </tr>
                      <tr>
                        <td colspan="6" valign="bottom" align="left">
                         '.$sql["dbc"]->result($sql["dbc"]->query("SELECT name FROM talenttab WHERE id='".$k."'"), 0, "name").': '.$points.'
                        </td>
                      </tr>
                    </table>
                  </td>';
        }
        unset($data);
        unset($k);
        unset($tabs);
        $output .='
                </tr>
              </table>
              <br />
              <table class="center">
                <tr>
                  <td align="left">
                    '.lang("char", "talent_rate").': <br />
                    '.lang("char", "talent_points").': <br />
                    '.lang("char", "talent_points_used").': <br />
                    '.lang("char", "talent_points_shown").': <br />
                    '.lang("char", "talent_points_left").':
                  </td>
                  <td align="left">
                    '.$talent_rate.'<br />
                    '.$talent_points.'<br />
                    '.$talent_points_used.'<br />
                    '.$l.'<br />
                    '.$talent_points_left.'
                  </td>
                  <td style="width: 64px">
                  </td>
                  <td align="right">';
        unset($l);
        unset($talent_rate);
        unset($talent_points);
        unset($talent_points_used);
        unset($talent_points_left);
        if ( $core == 1)
        {
          $glyph_query = "SELECT glyphs".$cur_spec." FROM characters WHERE guid='".$id."'";
          $glyph_results = $sql["char"]->query($glyph_query);
          $glyph_field = $sql["char"]->fetch_assoc($glyph_results);
          $glyphs = $glyph_field["glyphs1"];
          $glyphs = substr($glyphs, 0, strlen($glyphs) - 1);
          $glyphs = explode(',', $glyphs);
        }
        else
        {
          $glyph_query = "SELECT * FROM character_glyphs WHERE guid='".$id."' and spec='".($cur_spec-1)."'";
          $glyph_result = $sql["char"]->query($glyph_query);
          $glyph_field = $sql["char"]->fetch_assoc($glyph_result);
          $glyphs = array();
          if ( isset($glyph_field["glyph1"]) )
            array_push($glyphs, $glyph_field["glyph1"]);
          if ( isset($glyph_field["glyph2"]) )
            array_push($glyphs, $glyph_field["glyph2"]);
          if ( isset($glyph_field["glyph3"]) )
            array_push($glyphs, $glyph_field["glyph3"]);
          if ( isset($glyph_field["glyph4"]) )
            array_push($glyphs, $glyph_field["glyph4"]);
          if ( isset($glyph_field["glyph5"]) )
            array_push($glyphs, $glyph_field["glyph5"]);
          if ( isset($glyph_field["glyph6"]) )
            array_push($glyphs, $glyph_field["glyph6"]);
        }
        for ( $i = 0; $i < 6; ++$i )
        {
          if ( $glyphs[$i] )
          {
            $glyph = $sql["dbc"]->result($sql["dbc"]->query("SELECT spellid FROM glyphproperties WHERE id='".$glyphs[$i]."'"), 0);

            $output .='
                    <a href="'.$base_datasite.$spell_datasite.$glyph.'" rel="external">
                      <img src="'.spell_get_icon($glyph).'" width="36" height="36" class="icon_border_0" alt="" />
                    </a>';
          }
        }
        unset($glyphs);
        $output .='
                  </td>
                </tr>
              </table>';
      }
      //---------------Page Specific Data Ends here----------------------------
      //---------------Character Tabs Footer-----------------------------------
      $output .= '
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
        makebutton(lang("char", "send_mail"), 'mail.php?type=ingame_mail&amp;to='.$char["name"], 130);
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
          <!-- end of char_talent.php -->';
    }
    else
      error(lang("char", "no_permission"));
  }
  else
    error(lang("char", "no_char_found"));

}

// this_is_junk: Because we use it for filenames, we can't use the one in the language kit.
function get_class_name($class_id)
{
  $class_names = array
    (
       1  => "Warrior",
       2  => "Paladin",
       3  => "Hunter",
       4  => "Rogue",
       5  => "Priest",
       6  => "DeathKnight",
       7  => "Shaman",
       8  => "Mage",
       9  => "Warlock",
       11 => "Druid",
    );

  return $class_names[$class_id];
}


function talent_dependencies(&$tabs, &$tab, &$i)
{
  global $sql;

  $query = "SELECT TalentTab, Row, Col, Spell".($tab["TalentCount1"] + 1).", Talent1, TalentCount1".( ( $tab["TalentCount1"] < 4 ) ? ", Spell".($tab["TalentCount1"] + 2) : "" )." FROM talent WHERE id='".$tab["Talent1"]."' AND Spell".($tab["TalentCount1"] + 1)." != 0 LIMIT 1";

  if ( $dep = $sql["dbc"]->fetch_assoc($sql["dbc"]->query($query)) )
  {
    if ( empty($tabs[$dep["TalentTab"]][$dep["Row"]][$dep["Col"]]) )
    {
      $tabs[$dep["TalentTab"]][$dep["Row"]][$dep["Col"]] = array($dep["Spell".($tab["TalentCount1"] + 1)], ($tab["TalentCount1"] + 1), ( ( $tab["TalentCount1"] < 4 ) ? ($dep["Spell".($tab["TalentCount1"] + 2)] ? "2" : "5" ) : "5" ));
      $i += ($tab["TalentCount1"] + 1);
      if ( $dep["Talent1"] )
        talent_dependencies($tabs, $dep, $i);
    }
  }
}


//########################################################################################################################
// MAIN
//########################################################################################################################

// action variable reserved for future use
//$action = (isset($_GET["action"])) ? $_GET["action"] : NULL;

$output .= '
      <div class="bubble">';

char_talent();

unset($action_permission);

require_once "footer.php";


?>
