<?php

/**
 * @package World of Warcraft Armory
 * @version Release 4.50
 * @revision 450
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
if(!@include('includes/armory_loader.php')) {
    die('<b>Fatal error:</b> unable to load system files.');
}
header('Content-type: text/xml');
/** Header **/
$xml->LoadXSLT('dungeons.xsl');
$xml->XMLWriter()->startElement('page');
$xml->XMLWriter()->writeAttribute('globalSearch', 1);
$xml->XMLWriter()->writeAttribute('lang', Armory::GetLocale());
$xml->XMLWriter()->startElement('tabInfo');
$xml->XMLWriter()->writeAttribute('subTab', 'bc');
$xml->XMLWriter()->writeAttribute('tab', 'dungeons');
$xml->XMLWriter()->writeAttribute('tabGroup', 'dungeons_and_factions');
$xml->XMLWriter()->startElement('dungeons');
$xml->XMLWriter()->writeAttribute('release', 'bc');
$xml->XMLWriter()->writeAttribute('releaseid', 1);
$xml->XMLWriter()->endElement();   //dungeons
$xml->XMLWriter()->endElement();  //tabInfo
$xml->XMLWriter()->endElement(); //page
echo $xml->StopXML();
exit;
?>