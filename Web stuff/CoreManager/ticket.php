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

valid_login($action_permission["view"]);

//#############################################################################
//  BROWSE  TICKETS
//#############################################################################
function browse_tickets()
{
  global $output, $characters_db, $realm_id, $action_permission, $user_lvl, $itemperpage, $sql, $core,
    $site_encoding;

  //==========================$_GET and SECURE=================================
  $start = ( ( isset($_GET["start"]) ) ? $sql["char"]->quote_smart($_GET["start"]) : 0 );
  if ( !is_numeric($start) )
    $start = 0;

  $order_by = ( ( isset($_GET["order_by"]) ) ? $sql["char"]->quote_smart($_GET["order_by"]) : 'status' );
  if ( !preg_match('/^[_[:lower:]]{1,10}$/', $order_by) )
    $order_by = 'status';

  $dir = ( ( isset($_GET["dir"]) ) ? $sql["char"]->quote_smart($_GET["dir"]) : 1 );
  if ( !preg_match('/^[01]{1}$/', $dir) )
    $dir = 1;

  $order_dir = ( ( $dir ) ? "ASC" : "DESC" );
  $dir = ( ( $dir ) ? 0 : 1 );
  //==========================$_GET and SECURE end=============================

  //get total number of items
  if ( $core == 1 )
    $query_1 = $sql["char"]->query("SELECT COUNT(*) FROM gm_tickets WHERE deleted=0");
  elseif ( $core == 2 )
    $query_1 = $sql["char"]->query("SELECT COUNT(*) FROM character_ticket");
  else
    $query_1 = $sql["char"]->query("SELECT COUNT(*) FROM gm_tickets WHERE closedBy=0");
  $all_record = $sql["char"]->result($query_1,0);
  unset($query_1);

  if ( $core == 1 )
    $query = $sql["char"]->query("SELECT gm_tickets.ticketid AS guid, gm_tickets.playerGuid AS player,
                        gm_tickets.message AS message,
                        `characters`.name AS opener,
                        gm_tickets.deleted AS status, gm_tickets.timestamp AS timestamp
                        FROM gm_tickets
                          LEFT JOIN `characters` ON gm_tickets.playerGuid=`characters`.`guid`
                        ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
  elseif ( $core == 2 )
    $query = $sql["char"]->query("SELECT character_ticket.ticket_id AS guid, character_ticket.guid AS player,
                        character_ticket.ticket_text AS message,
                        op.name AS opener,
                        0 AS status, UNIX_TIMESTAMP(character_ticket.ticket_lastchange) AS timestamp
                        FROM character_ticket
                          LEFT JOIN `characters` AS op ON character_ticket.guid=op.`guid`
                        ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);
  else
    $query = $sql["char"]->query("SELECT gm_tickets.guid AS guid, gm_tickets.guid AS player,
                        gm_tickets.message AS message,
                        op.name AS opener, gm.name AS closer,
                        gm_tickets.closedBy AS status, gm_tickets.lastModifiedTime AS timestamp
                        FROM gm_tickets
                          LEFT JOIN `characters` AS op ON gm_tickets.guid=op.`guid`
                          LEFT JOIN `characters` AS gm ON gm_tickets.closedBy=gm.`guid`
                        ORDER BY ".$order_by." ".$order_dir." LIMIT ".$start.", ".$itemperpage);

  $output .= '
        <script type="text/javascript" src="libs/js/check.js"></script>
        <table class="top_hidden">
          <tr>
            <td style="width: 25%;" align="right">';
  $output .= generate_pagination("ticket.php?action=browse_tickets&amp;order_by=".$order_by."&amp;dir=".!$dir, $all_record, $itemperpage, $start);
  $output .= '
            </td>
          </tr>
        </table>';
  $output .= '
        <form method="get" action="ticket.php" id="form">
          <div>
            <input type="hidden" name="action" value="delete_tickets" />
            <input type="hidden" name="start" value="'.$start.'" />
          </div>
          <table class="lined">
            <tr>
              <td colspan="3" align="left" class="hidden">';
  if ( $user_lvl >= $action_permission["delete"] )
    makebutton(lang("ticket", "del_selected_tickets"), "javascript:do_submit()\" type=\"wrn", 230);
  $output .= '
              </td>
            </tr>
            <tr>';
  if ( $user_lvl >= $action_permission["delete"] )
    $output .= '
              <th style="width: 7%;">
                <input name="allbox" type="checkbox" value="Check All" onclick="CheckAll(document.getElementById(\'form\'));" />
              </th>';
  if ( $user_lvl >= $action_permission["update"] )
    $output .= '
              <th style="width: 7%;">'.lang("global", "edit").'</th>';
  $output .= '
              <th style="width: 10%;">
                <a href="ticket.php?order_by=guid&amp;start='.$start.'&amp;dir='.$dir.'">'.( ( $order_by == 'guid' ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : '' ).''.lang("ticket", "id").'</a>
              </th>
              <th style="width: 16%;">
                <a href="ticket.php?order_by=opener&amp;start='.$start.'&amp;dir='.$dir.'">'.( ( $order_by == 'opener' ) ? '<img src="img/arr_'.( ( $dir ) ? "dw" : "up" ).'.gif" alt="" /> ' : '').''.lang("ticket", "sender").'</a>
              </th>';
$output .= '
              <th style="width: 40%;">'.lang("ticket", "message").'</th>
              <th style="width: 10%;">'.lang("ticket", "date").'</th>';
  if ( $core == 3 )
    $output .= '
              <th style="width: 40%;">'.lang("ticket", "closedby").'</th>';
  $output .= '
            </tr>';
  while ( $ticket = $sql["char"]->fetch_assoc($query) )
  {
    $output .= '
            <tr>';
    if ( $user_lvl >= $action_permission["delete"] )
      $output .= '
              <td>
                <input type="checkbox" name="check[]" value="'.$ticket["guid"].'" onclick="CheckCheckAll(getElementById(\'form\'));" />
              </td>';
    if ( $user_lvl >= $action_permission["update"] )
      $output .= '
              <td>
                <a href="ticket.php?action=edit_ticket&amp;error=4&amp;id='.$ticket["guid"].'"><img src="img/edit.png" alt="'.lang("global", "edit").'" /></a>
              </td>';
    $output .= '
              <td>'.$ticket["guid"].'</td>
              <td>
                <a href="char.php?id='.$ticket["player"].'">'.htmlentities($ticket["opener"], ENT_COMPAT, $site_encoding).'</a>
              </td>
              <td>'.htmlentities($ticket["message"], ENT_COMPAT, $site_encoding).'</td>
              <td>'.date('G:i:s m-d-Y', $ticket["timestamp"]).'</td>';
    if ( $core == 3 )
      $output .= '
              <td>
                <a href="char.php?id='.$ticket["status"].'">'.htmlentities($ticket["closer"], ENT_COMPAT, $site_encoding).'</a>
              </td>';
    $output .= '
            </tr>';
  }
  unset($query);
  unset($ticket);
  $output .= '
            <tr>
              <td colspan="5" align="right" class="hidden" style="width: 25%;">';
  $output .= generate_pagination("ticket.php?action=browse_tickets&amp;order_by=".$order_by."&amp;dir=".!$dir, $all_record, $itemperpage, $start);
  $output .= '
              </td>
            </tr>
            <tr>
              <td colspan="3" align="left" class="hidden">';
  if ( $user_lvl >= $action_permission["delete"] )
    makebutton(lang("ticket", "del_selected_tickets"), "javascript:do_submit()\" type=\"wrn", 230);
  $output .= '
              </td>
              <td colspan="2" align="right" class="hidden">'.lang("ticket", "tot_tickets").': '.$all_record.'</td>
            </tr>
          </table>
        </form>
        <br />';

}


//########################################################################################################################
//  DELETE TICKETS
//########################################################################################################################
function delete_tickets()
{
  global $characters_db, $realm_id, $action_permission, $sql, $core;

  valid_login($action_permission["delete"]);

  if ( !isset($_GET["check"]) )
    redirect("ticket.php?error=1");

  $check = $sql["char"]->quote_smart($_GET["check"]);

  $deleted_tickets = 0;
  for ( $i = 0; $i < count($check); $i++ )
  {
    if ( $check[$i] != "" )
    {
      if ( $core == 1 )
        $query = $sql["char"]->query("DELETE FROM gm_tickets WHERE ticketid='".$check[$i]."'");
      elseif ( $core == 2)
        $query = $sql["char"]->query("DELETE FROM character_ticket WHERE ticket_id='".$check[$i]."'");
      else
        $query = $sql["char"]->query("DELETE FROM gm_tickets WHERE guid='".$check[$i]."'");
      $deleted_tickets++;
    }
  }

  if ( $deleted_tickets == 0 )
    redirect('ticket.php?error=3');
  else
    redirect('ticket.php?error=2');
}


//########################################################################################################################
//  EDIT TICKET
//########################################################################################################################
function edit_ticket()
{
  global  $output, $characters_db, $realm_id, $action_permission, $site_encoding, $sql, $core;

  valid_login($action_permission["update"]);

  if ( !isset($_GET["id"]) )
    redirect("Location: ticket.php?error=1");

  $id = $sql["char"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    redirect("ticket.php?error=1");

  if ( $core == 1 )
    $query = $sql["char"]->query("SELECT gm_tickets.ticketid AS guid, gm_tickets.playerGuid AS player,
                        gm_tickets.message AS message,
                        `characters`.name AS opener,
                        gm_tickets.deleted AS status, gm_tickets.timestamp AS timestamp
                        FROM gm_tickets
                          LEFT JOIN `characters` ON gm_tickets.playerGuid=`characters`.`guid`
                        WHERE ticketid='".$id."'");
  elseif ( $core == 2)
    $query = $sql["char"]->query("SELECT character_ticket.ticket_id AS guid, character_ticket.guid AS player,
                        character_ticket.ticket_text AS message,
                        op.name AS opener,
                        UNIX_TIMESTAMP(character_ticket.ticket_lastchange) AS timestamp
                        FROM character_ticket
                          LEFT JOIN `characters` AS op ON character_ticket.guid=op.`guid`
                        WHERE character_ticket.ticket_id='".$id."'");
  else
    $query = $sql["char"]->query("SELECT gm_tickets.guid AS guid, gm_tickets.guid AS player,
                        gm_tickets.message AS message,
                        op.name AS opener, gm.name AS closer,
                        gm_tickets.closedBy AS status, lastModifiedTime AS timestamp
                        FROM gm_tickets
                          LEFT JOIN `characters` AS op ON gm_tickets.guid=op.`guid`
                          LEFT JOIN `characters` AS gm ON gm_tickets.closedBy=gm.`guid`
                        WHERE gm_tickets.guid='".$id."'");

  if ( $ticket = $sql["char"]->fetch_assoc($query) )
  {
    $output .= '
          <div id="ticket_edit_field" class="fieldset_border center">
            <span class="legend">'.lang("ticket", "edit_reply").'</span>
            <form method="post" action="ticket.php?action=do_edit_ticket" id="form">
              <div>
                <input type="hidden" name="id" value="'.$id.'" />
              </div>
              <table class="flat">
                <tr>
                  <td>'.lang("ticket", "id").'</td>
                  <td>'.$id.'</td>
                </tr>
                <tr>
                  <td>'.lang("ticket", "submitted_by").':</td>
                  <td>
                    <a href="char.php?id='.$ticket["player"].'">'.htmlentities($ticket["opener"], ENT_COMPAT, $site_encoding).'</a>
                  </td>
                </tr>
                <tr>
                  <td>'.lang("ticket", "date").':</td>
                  <td>'.date('G:i:s m-d-Y', $ticket["timestamp"]).'</td>
                </tr>
                <tr>
                  <td valign="top">'.lang("ticket", "message").'</td>
                  <td>
                    <textarea name="new_text" rows="5" cols="40">'.htmlentities($ticket["message"], ENT_COMPAT, $site_encoding).'</textarea>
                  </td>
                </tr>';
    if ( $core == 3 )
      $output .= '
                <tr>
                  <td>'.lang("ticket", "closedby").':</td>
                  <td>'.( ( $ticket["status"] <> 0 ) ? '<a href="char.php?id='.$ticket["status"].'">'.htmlentities($ticket["closer"], ENT_COMPAT, $site_encoding).'</a>' : '' ).'</td>
                </tr>';
    $output .= '
                <tr>
                  <td>';
    makebutton(lang("ticket", "update"), "javascript:do_submit()\" type=\"wrn", 140);
    $output .= '
                  </td>
                  <td>';
    // MaNGOS just deletes a ticket to close it
    // so we don't need this button
    if ( $core == 2 )
      $output .= '
                    &nbsp;';
    else
    {
      if ( !$ticket["status"] )
        makebutton(lang("ticket", "abandon".( ( $core == 1 ) ? "A" : "MT" )), 'ticket.php?action=do_mark_ticket&amp;id='.$id.'" type="wrn', 230);
      else
        makebutton(lang("ticket", "abandon".( ( $core == 1 ) ? "A" : "MT" )), 'ticket.php', 230);
    }
    $output .= '
                  </td>
                </tr>
                <tr>
                  <td>';
    makebutton(lang("ticket", "send_ingame_mail"), "mail.php?type=ingame_mail&amp;to=".$ticket["opener"], 140);
    $output .= '
                  </td>
                  <td>';
    makebutton(lang("global", "back"), "javascript:window.history.back()\" type=\"def", 130);
    $output .= '
                  </td>
                </tr>
              </table>
            </form>
            <br />
            <br />
          </div>';
  }
  else
    error(lang("global", "err_no_records_found"));

}


//########################################################################################################################
//  DO EDIT  TICKET
//########################################################################################################################
function do_edit_ticket()
{
  global $characters_db, $realm_id, $action_permission, $sql, $core;

  valid_login($action_permission["update"]);

  if ( empty($_POST["new_text"]) || empty($_POST["id"]) )
    redirect("ticket.php?error=1");

  $new_text = $sql["char"]->quote_smart($_POST["new_text"]);
  $id = $sql["char"]->quote_smart($_POST["id"]);
  if ( !is_numeric($id) )
    redirect("ticket.php?error=1");

  if ( $core == 1 )
    $query = $sql["char"]->query("UPDATE gm_tickets SET message='".$new_text."' WHERE ticketid='".$id."'");
  elseif ( $core == 2 )
    $query = $sql["char"]->query("UPDATE character_ticket SET ticket_text='".$new_text."' WHERE ticket_id='".$id."'");
  else
    $query = $sql["char"]->query("UPDATE gm_tickets SET message='".$new_text."' WHERE guid='".$id."'");

  if ( $sql["char"]->affected_rows() )
    redirect("ticket.php?error=5");
  else
    redirect("ticket.php?error=6");
}


//########################################################################################################################
//  DO MARK TICKET AS ABANDONED
//########################################################################################################################
function do_mark_ticket()
{
  global $characters_db, $realm_id, $action_permission, $sql, $core, $user_id;

  valid_login($action_permission["update"]);

  if ( empty($_GET["id"]) )
    redirect("ticket.php?error=1");

  $id = $sql["char"]->quote_smart($_GET["id"]);
  if ( !is_numeric($id) )
    redirect("ticket.php?error=1");

  if ( $core == 3 )
  {
    // get closing account's oldest character
    $query = "SELECT guid FROM characters WHERE account='".$user_id."' ORDER BY guid LIMIT 1";
    $result = $sql["char"]->query($query);
    $fields = $sql["char"]->fetch_assoc($result);
    $closer = $fields["guid"];
  }

  if ( $core == 1 )
    $query = $sql["char"]->query("UPDATE gm_tickets SET deleted=1 WHERE ticketid='".$id."'");
  elseif ( $core == 2 )
    // this_is_junk: MaNGOS doesn't have a way to close a ticket?  Just delete it?
    $query = $sql["char"]->query("DELETE FROM character_ticket WHERE ticket_id='".$id."'");
  else
    $query = $sql["char"]->query("UPDATE gm_tickets SET closedBy=".$closer." WHERE guid='".$id."'");

  if ( $sql["char"]->affected_rows() )
    redirect("ticket.php?error=5");
  else
    redirect("ticket.php?error=6");
}


//########################################################################################################################
// MAIN
//########################################################################################################################
$err = ( ( isset($_GET["error"]) ) ? $_GET["error"] : NULL );

$output .= '
      <div class="bubble">
        <div class="top">';

switch ( $err )
{
  case 1:
    $output .= '
          <h1><span class="error">'.lang("global", "empty_fields").'</span></h1>';
    break;
  case 2:
    $output .= '
          <h1><span class=\"error\">'.lang("ticket", "ticked_deleted").'</span></h1>';
    break;
  case 3:
    $output .= '
          <h1><span class="error">'.lang("ticket", "ticket_not_deleted").'</span></h1>';
    break;
  case 4:
    $output .= '
          <h1>'.lang("ticket", "edit_ticked").'</h1>';
    break;
  case 5:
    $output .= '
          <h1><span class="error">'.lang("ticket", "ticket_updated").'</span></h1>';
    break;
  case 6:
    $output .= '
          <h1><span class="error">'.lang("ticket", "ticket_update_err").'</span></h1>';
    break;
  default: //no error
    $output .= '
          <h1>'.lang("ticket", "browse_tickets").'</h1>';
}

unset($err);

$output .= '
        </div>';

$action = ( ( isset($_GET["action"]) ) ? $_GET["action"] : NULL );

switch ( $action )
{
  case "browse_tickets":
    browse_tickets();
    break;
  case "delete_tickets":
    delete_tickets();
    break;
  case "edit_ticket":
    edit_ticket();
    break;
  case "do_edit_ticket":
    do_edit_ticket();
    break;
  case "do_mark_ticket":
    do_mark_ticket();
    break;
  default:
    browse_tickets();
}

unset($action);
unset($action_permission);

require_once "footer.php";

?>
