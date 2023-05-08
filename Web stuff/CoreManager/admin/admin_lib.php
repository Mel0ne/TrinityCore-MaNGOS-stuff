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

//#############################################################################
//validates sessions' vars and restricting access to given level
function valid_login_webadmin($restrict_lvl)
{
  global $sqlm;

  if ( isset($_SESSION["login"]) )
  {
    $query = "SELECT * FROM config_accounts WHERE Login='".$_SESSION["login"]."'";
    $user = $sqlm->fetch_assoc($sqlm->query($query));

    $user_lvl = $user["SecurityLevel"];
    $ip = ( ( isset($_SERVER["REMOTE_ADDR"]) ) ? $_SERVER["REMOTE_ADDR"] : getenv("REMOTE_ADDR") );
    if ( !$ip === $_SESSION["client_ip"] )
      header("Location: admin_login.php");
  }
  else
    header("Location: admin_login.php");

  if ( $user_lvl < 1073741824 )
    header("Location: admin_login.php?error=5");
}


//#############################################################################
// Get Security Level Name
function sec_level_name($sec)
{
  global $sqlm;
  
  $query = "SELECT * FROM config_gm_level_names WHERE Security_Level='".$sec."'";
  $fields = $sqlm->fetch_assoc($sqlm->query($query));
  
  return $fields["Full_Name"];
}


//#############################################################################
// Get Security Levels List
function sec_level_list()
{
  global $sqlm;
  
  $query = "SELECT Security_Level, Full_Name FROM config_gm_level_names";
  $fields = $sqlm->query($query);
  
  $out = array();
  
  while ( $row = $sqlm->fetch_assoc($fields) )
  {
    $outrow = array();
    $outrow["Sec"] = $row["Security_Level"];
    $outrow["Name"] = $row["Full_Name"];
    array_push($out, $outrow);
  }
  
  return $out;
}


//#############################################################################
//redirects to error page with error code
function error($err)
{
  die($err);
}


//#############################################################################
// Realm Icons for Servers Tab
$get_icon_type = array
(
  0 => array( 0, "normal"),
  1 => array( 1, "pvp"),
  6 => array( 6, "rp"),
  8 => array( 8, "rppvp"),
);


//#############################################################################
// Realm Timezones for Servers Tab
$get_timezone_type = array
(
  0 => array( 0, "undefined"),
  1 => array( 1, "development"),
  2 => array( 2, "united_states"),
  3 => array( 3, "oceanic"),
  4 => array( 4, "latin_america"),
  5 => array( 5, "tournament"),
  6 => array( 6, "korea"),
  8 => array( 8, "english"),
  9 => array( 9, "german"),
 10 => array(10, "french"),
 11 => array(11, "spanish"),
 12 => array(12, "russian"),
 14 => array(14, "taiwan"),
 16 => array(16, "china"),
 26 => array(26, "test_server"),
 28 => array(28, "qa_server"),
);


//#############################################################################
// Generate paging navigation.
// Original from PHPBB with some modifications to make them more simple
function generate_pagination($base_url, $num_items, $per_page, $start_item, $start_tag = "start", $add_prevnext_text = TRUE)
{
  if ( !$num_items )
    return "";

  $total_pages = ceil($num_items / $per_page);
  if ( $total_pages == 1 )
    return "";

  $on_page = floor($start_item / $per_page)+1;
  $page_string = '';
  if ( $total_pages > 10 )
  {
    $init_page_max = ( ( 3 < $total_pages ) ? 3 : $total_pages );
    $count = $init_page_max+1;
    for ( $i = 1; $i < $count; ++$i )
    {
      $page_string .= ( ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.$base_url.'&amp;'.$start_tag.'='.(($i-1)*$per_page).'">'.$i.'</a>' );
      if ( $i < $init_page_max )
        $page_string .= ', ';
    }
    if ( $total_pages > 3 )
    {
      if ( ( $on_page > 1 ) && ( $on_page < $total_pages ) )
      {
        $page_string  .= ( ( $on_page > 5 ) ? ' ... ' : ', ' );
        $init_page_min = ( ( $on_page > 4 ) ? $on_page : 5 );
        $init_page_max = ( ( $on_page < $total_pages-4 ) ? $on_page : $total_pages-4 );

        $count = $init_page_max+2;
        for ( $i = $init_page_min-1; $i < $count; ++$i )
        {
          $page_string .= ( ( $i === $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.$base_url.'&amp;'.$start_tag.'='.(($i-1)*$per_page).'">'.$i.'</a>' );
          if ( $i <  $init_page_max+1 )
            $page_string .= ', ';
        }
        $page_string .= ( ( $on_page < $total_pages-4 ) ? ' ... ' : ', ' );
      }
      else
        $page_string .= ' ... ';

      $count = $total_pages+1;
      for ( $i = $total_pages-2; $i < $count; ++$i )
      {
        $page_string .= ( ( $i == $on_page ) ? '<b>'.$i.'</b>'  : '<a href="'.$base_url.'&amp;'.$start_tag.'='.(($i-1)*$per_page).'">'.$i.'</a>' );
        if ( $i < $total_pages )
          $page_string .= ', ';
      }
    }
  }
  else
  {
    $count = $total_pages + 1;
    for ( $i = 1; $i < $count; ++$i )
    {
      $page_string .= ( ( $i == $on_page ) ? '<b>'.$i.'</b>' : '<a href="'.$base_url.'&amp;'.$start_tag.'='.(($i-1)*$per_page).'">'.$i.'</a>' );
      if ( $i <  $total_pages )
        $page_string .= ', ';
    }
  }
  if ( $add_prevnext_text )
  {
    if ( $on_page > 1 )
      $page_string = '<a href="'.$base_url.'&amp;'.$start_tag.'='.(($on_page-2)*$per_page).'">Prev</a>&nbsp;&nbsp;'.$page_string;

    if ( $on_page < $total_pages )
      $page_string .= '&nbsp;&nbsp;<a href="'.$base_url.'&amp;'.$start_tag.'='.($on_page*$per_page).'">Next</a>';
  }
  $page_string = 'Page: '.$page_string;

  return $page_string;

}


//#############################################################################
// Detect Core Type
function detectcore()
{
  global $logon_db;

  $sqll = new SQL;
  $sqll->connect($logon_db["addr"], $logon_db["user"], $logon_db["pass"], $logon_db["name"], $logon_db["encoding"]);

  // are we ArcEmu?
  $query = "SHOW TABLES FROM `".$logon_db["name"]."` LIKE 'accounts'";
  $result = $sqll->query($query);

  if ( $sqll->num_rows($result) == 1 )
    return 1; // we're ArcEmu

  // no? ...then are we Trinity?
  $query = "SHOW TABLES FROM `".$logon_db["name"]."` LIKE 'account_access'";
  $result = $sqll->query($query);

  if ( $sqll->num_rows($result) == 1 )
    return 3; // we're Trinity
  else
    return 2; // we're MaNGOS
}


//#############################################################################
// Validate Upload
function checkValidUpload($code)
{
  if ( $code == UPLOAD_ERR_OK )
    return 1;

  switch ( $code )
  {
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
      $err = -2;
      break;
    case UPLOAD_ERR_PARTIAL:
      $err = -3;
      break;
    case UPLOAD_ERR_NO_FILE:
      $err = -4;
      break;
    case UPLOAD_ERR_NO_TMP_DIR:
      $err = -5;
      break;
    case UPLOAD_ERR_CANT_WRITE:
      $err = -6;
      break;
    case UPLOAD_ERR_EXTENSION:
      $err = -7;
      break;
    default:
      $err = -1;
  }

  return $err;
}


//#############################################################################
// Get Item Icon For Point System Prize Bags
function get_item_icon($itemid)
{
  global $dbc_db, $world_db, $realm_id, $proxy_cfg, $item_icons, $sql, $core;

  $sqld = new SQL;
  $sqld->connect($dbc_db["addr"], $dbc_db["user"], $dbc_db["pass"], $dbc_db["name"], $dbc_db["encoding"]);

  $sqlw = new SQL;
  $sqlw->connect($world_db[1]["addr"], $world_db[1]["user"], $world_db[1]["pass"], $world_db[1]["name"], $world_db[1]["encoding"]);

  if ( $core == 1 )
    $result = $sqlw->query("SELECT `displayid` FROM `items` WHERE `entry`='".$itemid."' LIMIT 1");
  else
    $result = $sqlw->query("SELECT `displayid` FROM `item_template` WHERE `entry`='".$itemid."' LIMIT 1");
  $displayid_record = $sqlw->fetch_assoc($result);
  $displayid = $displayid_record["displayid"];

  $result = $sqld->query("SELECT `IconName` FROM itemdisplayinfo WHERE id='".$displayid."'");
  $icon_fields = $sqld->fetch_assoc($result);
  return $item_icons."/".$icon_fields["IconName"].".png";
}


?>
