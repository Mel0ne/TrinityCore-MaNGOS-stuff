<?php

/**
 * @package World of Warcraft Armory
 * @version Release 4.50
 * @revision 456
 * @copyright (c) 2009-2011 Shadez
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 **/

define('__ARMORY__', true);
define('load_characters_class', true);
define('load_achievements_class', true);
define('load_arenateams_class', true);
if(!@include('includes/armory_loader.php')) {
    die('<b>Fatal error:</b> unable to load system files.');
}
header('Content-type: text/xml');
// Load XSLT template
$xml->LoadXSLT('character/arenateams.xsl');
if(isset($_GET['n'])) {
    $name = $_GET['n'];
}
elseif(isset($_GET['cn'])) {
    $name = $_GET['cn'];
}
else {
    $name = false;
}
if(!isset($_GET['r'])) {
    $_GET['r'] = false;
}
$realmId = $utils->GetRealmIdByName($_GET['r']);
$characters->BuildCharacter($name, $realmId, true, true);
$isCharacter = $characters->CheckPlayer();
if($_GET['r'] === false || !$characters->GetRealmName()) {
    $isCharacter = false;
}
// Get page cache
if($characters->GetGUID() > 0 && $isCharacter && Armory::$armoryconfig['useCache'] == true && !isset($_GET['skipCache'])) {
    $cache_id = $utils->GenerateCacheId('character-arenateams', $characters->GetName(), $characters->GetRealmName());
    if($cache_data = $utils->GetCache($cache_id)) {
        echo $cache_data;
        echo sprintf('<!-- Restored from cache; id: %s -->', $cache_id);
        exit;
    }
}/** Basic info **/
$achievements = $characters->GetAchievementMgr();
$arenateams->guid   = $characters->GetGUID();
$tabUrl = $characters->GetUrlString();
/** Header **/
$xml->XMLWriter()->startElement('page');
$xml->XMLWriter()->writeAttribute('globalSearch', 1);
$xml->XMLWriter()->writeAttribute('lang', Armory::GetLocale());
$xml->XMLWriter()->writeAttribute('requestUrl', 'character-arenateams.xml');
$xml->XMLWriter()->startElement('tabInfo');
$xml->XMLWriter()->writeAttribute('subTab', 'arena');
$xml->XMLWriter()->writeAttribute('tab', 'character');
$xml->XMLWriter()->writeAttribute('tabGroup', 'character');
$xml->XMLWriter()->writeAttribute('tabUrl', $tabUrl);
$xml->XMLWriter()->endElement(); //tabInfo
if(!$isCharacter) {
    $xml->XMLWriter()->startElement('characterInfo');
    $xml->XMLWriter()->writeAttribute('errCode', 'noCharacter');
    $xml->XMLWriter()->endElement();  //characterInfo
    $xml->XMLWriter()->endElement(); //page
    $xml_cache_data = $xml->StopXML();
    echo $xml_cache_data;
    exit;
}
$character_title = $characters->GetChosenTitleInfo();
$character_element = $characters->GetHeader();
$character_arenateams = $arenateams->GetCharacterArenaTeamInfo();
$xml->XMLWriter()->startElement('characterInfo');
if($utils->IsWriteRaw()) {
    $xml->XMLWriter()->writeRaw('<character');
    foreach($character_element as $c_elem_name => $c_elem_value) {
        if($c_elem_name == 'charUrl') {
            $xml->XMLWriter()->writeRaw(' ' . $c_elem_name .'="' .htmlspecialchars($c_elem_value).'"');
        }
        else {
            $xml->XMLWriter()->writeRaw(' ' . $c_elem_name .'="' .$c_elem_value.'"');
        }
    }
    $xml->XMLWriter()->writeRaw('>');
    if($character_arenateams && is_array($character_arenateams)) {
        $xml->XMLWriter()->writeRaw('<arenaTeams>'); // close <character> tag before open new
        foreach($character_arenateams as $arenateam) {
            $xml->XMLWriter()->writeRaw('<arenaTeam');
            foreach($arenateam['data'] as $team_key => $team_value) {
                $xml->XMLWriter()->writeRaw(' ' . $team_key.'="' . htmlspecialchars($team_value).'"');
            }
            $xml->XMLWriter()->writeRaw('><emblem');
            foreach($arenateam['emblem'] as $emblem_key => $emblem_value) {
                $xml->XMLWriter()->writeRaw(' ' . $emblem_key.'="' . $emblem_value.'"');
            }
            $xml->XMLWriter()->writeRaw('><members>');
            foreach($arenateam['members'] as $member) {
                $xml->XMLWriter()->writeRaw('<member');
                foreach($member as $member_key => $member_value) {
                    $xml->XMLWriter()->writeRaw(' ' . $member_key .'="' . htmlspecialchars($member_value) . '"');
                }
                $xml->XMLWriter()->writeRaw('/>'); //member
            }
            $xml->XMLWriter()->writeRaw('</members></emblem></arenaTeam>');
        }
        $xml->XMLWriter()->writeRaw('</arenaTeams>');
    }
    $xml->XMLWriter()->writeRaw('<modelBasePath value="http://eu.media.battle.net.edgesuite.net/"/></character>');
}
else {
    $xml->XMLWriter()->startElement('character');
    foreach($character_element as $c_elem_name => $c_elem_value) {
        $xml->XMLWriter()->writeAttribute($c_elem_name, $c_elem_value);
    }
    if($character_arenateams && is_array($character_arenateams)) {
        $xml->XMLWriter()->startElement('arenaTeams');
        foreach($character_arenateams as $arenateam) {
            $xml->XMLWriter()->startElement('arenaTeam');
            foreach($arenateam['data'] as $team_key => $team_value) {
                $xml->XMLWriter()->writeAttribute($team_key, $team_value);
            }
            $xml->XMLWriter()->startElement('emblem');
            foreach($arenateam['emblem'] as $emblem_key => $emblem_value) {
                $xml->XMLWriter()->writeAttribute($emblem_key, $emblem_value);
            }
            $xml->XMLWriter()->endElement();  //emblem
            $xml->XMLWriter()->startElement('members');
            foreach($arenateam['members'] as $member) {
                $xml->XMLWriter()->startElement('character');
                foreach($member as $member_key => $member_value) {
                    $xml->XMLWriter()->writeAttribute($member_key, $member_value);
                }
                $xml->XMLWriter()->endElement(); //character
            }
            $xml->XMLWriter()->endElement();  //members
            $xml->XMLWriter()->endElement(); //arenaTeam
        }
        $xml->XMLWriter()->endElement(); //arenaTeams
    }
    $xml->XMLWriter()->startElement('modelBasePath');
    $xml->XMLWriter()->writeAttribute('value', 'http://eu.media.battle.net.edgesuite.net/');
    $xml->XMLWriter()->endElement();  //modelBasePath
    $xml->XMLWriter()->endElement(); //character
}
$xml->XMLWriter()->endElement();  //characterInfo
$xml->XMLWriter()->endElement(); //page
$xml_cache_data = $xml->StopXML();
echo $xml_cache_data;
if(Armory::$armoryconfig['useCache'] == true && !isset($_GET['skipCache'])) {
    // Write cache to file
    $cache_data = $utils->GenerateCacheData($characters->GetName(), $characters->GetGUID(), 'character-arenateams');
    $cache_handler = $utils->WriteCache($cache_id, $cache_data, $xml_cache_data);
}
exit;
?>