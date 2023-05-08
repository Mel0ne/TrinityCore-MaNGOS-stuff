<?php

/**
 * @package World of Warcraft Armory
 * @version Release 4.50
 * @revision 490
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

if (!defined('__ARMORY__'))
    die('Direct access to this file not is allowed!');

session_start();
error_reporting(0);

// Detect armory directory
define('__ARMORYDIRECTORY__', dirname(dirname(__FILE__)));
if (!defined('__ARMORYDIRECTORY__') || __ARMORYDIRECTORY__ == null)
    die('<b>Fatal error:</b> unable to detect armory directory!');
if (!include(__ARMORYDIRECTORY__ . '/includes/UpdateFields.php'))
    die('<b>Error:</b> unable to load UpdateFields.php!');
if (!include(__ARMORYDIRECTORY__ . '/includes/defines.php'))
    die('<b>Error:</b> unable to load defines.php!');
if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.armory.php'))
    die('<b>Error:</b> unable to load Armory class!');
if (!include(__ARMORYDIRECTORY__ . '/includes/revision_nr.php'))
    die('<b>Error:</b> unable to load revision file!');

// Forgot what am I did here :(
$_SESSION['last_url'] = str_replace('.php', '.xml', $_SERVER['PHP_SELF']) . '?' .str_replace('locale=', 'l=', $_SERVER['QUERY_STRING']);

Armory::InitializeArmory();

if (!include(__ARMORYDIRECTORY__ . '/includes/interfaces/interface.cache.php'))
    die('<b>Error:</b> unable to load cache interface!');

// We are forced to load APC cache, enabled or not
if (!include(__ARMORYDIRECTORY__ . '/includes/classes/cache/cache.apc.php'))
    die('<b>Error:</b> unable to load apc cache class!');

if (Armory::$armoryconfig['useApc'] == true)
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/cache_config.php'))
        die('<b>Error:</b> unable to load cache config!');

    // Make sure APC is installed
    if (!function_exists('apc_fetch'))
        die('<b>Error:</b> APC is enabled in configuation, but is not installed on php');
}

Armory::$cache = new ApcCache();
Armory::$cacheconfig = $cacheConfig;

/* Check DbVersion */
$dbVersion = Armory::$aDB->selectCell("SELECT `version` FROM `ARMORYDBPREFIX_db_version`");
if ($dbVersion != DB_VERSION)
{
    if (!$dbVersion)
    {
        if (isset(Armory::$armoryconfig['checkVersionType']) && Armory::$armoryconfig['checkVersionType'] == 'log')
            Armory::Log()->writeError('ArmoryChecker: wrong Armory DB name!');
        else
            echo '<b>Fatal error</b>: wrong Armory DB name<br/>';
    }
    $errorDBVersion = sprintf('Current version is %s but expected %s.<br />
    Apply all neccessary updates from \'sql/updates\' folder and refresh this page.', ($dbVersion) ? "'" . $dbVersion . "'" : 'not defined', "'" . DB_VERSION . "'");
    if (isset(Armory::$armoryconfig['checkVersionType']) && Armory::$armoryconfig['checkVersionType'] == 'log')
        Armory::Log()->writeError('ArmoryChecker : DB_VERSION error: %s', (defined('DB_VERSION')) ? $errorDBVersion : 'DB_VERSION constant not defined!');
    else
    {
        echo '<b>DB_VERSION error</b>:<br />';
        if (!defined('DB_VERSION'))
            die('DB_VERSION constant not defined!');
        die($errorDBVersion);
    }
}
/* Check config version */
if (!defined('CONFIG_VERSION') || !isset(Armory::$armoryconfig['configVersion']))
{
    if (isset(Armory::$armoryconfig['checkVersionType']) && Armory::$armoryconfig['checkVersionType'] == 'log')
        Armory::Log()->writeError('ArmoryChecker : unable to detect Configuration version!');
    else
        die('<b>ConfigVersion error:</b> unable to detect Configuration version!');
}
else if (CONFIG_VERSION != Armory::$armoryconfig['configVersion'])
{
    $CfgError = sprintf('<b>ConfigVersion error:</b> your config version is outdated (current: %s, expected: %s).<br />
    Please, update your config file from configuration.php.default', Armory::$armoryconfig['configVersion'], CONFIG_VERSION);
    if (isset(Armory::$armoryconfig['checkVersionType']) && Armory::$armoryconfig['checkVersionType'] == 'log')
        Armory::Log()->writeError('ArmoryChecker : %s', $CfgError);
    else
        die($CfgError);
}
/* Check maintenance */
if (Armory::$armoryconfig['maintenance'] == true && !defined('MAINTENANCE_PAGE'))
{
    header('Location: maintenance.xml');
    exit;
}
if (!defined('skip_utils_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.utils.php'))
        die('<b>Error:</b> unable to load utils class!');

    $utils = new Utils();
    // Check $_GET variable
    $utils->CheckVariablesForPage();
    // Update visitors count
    $utils->UpdateVisitorsCount();
}
/** Login **/
if (isset($_GET['login']) && $_GET['login'] == 1)
{
    header('Location: login.xml');
    exit;
}
else if (isset($_GET['logout']) && $_GET['logout'] == 1)
{
    header('Location: login.xml?logoff');
    exit;
}

/** Locale change **/
if (isset($_GET['locale']))
{
    $tmp = strtolower($_GET['locale']);
    $_SESSION['armoryLocaleId'] = Armory::GetLoc();
    switch ($tmp)
    {
        case 'ru_ru':
        case 'ruru':
        case 'ru':
            $_SESSION['armoryLocale'] = 'ru_ru';
            $_SESSION['armoryLocaleId'] = 8;
            break;
        case 'en_gb':
        case 'engb':
        case 'en':
            $_SESSION['armoryLocale'] = 'en_gb';
            $_SESSION['armoryLocaleId'] = 0;
            break;
        case 'es_es':
        case 'es_mx':
        case 'eses':
        case 'es':
            $_SESSION['armoryLocale'] = 'es_es';
            $_SESSION['armoryLocaleId'] = 6;
            break;
        case 'de_de':
        case 'dede':
        case 'de':
            $_SESSION['armoryLocale'] = 'de_de';
            $_SESSION['armoryLocaleId'] = 3;
            break;
        case 'fr_fr':
        case 'frfr':
        case 'fr':
            $_SESSION['armoryLocale'] = 'fr_fr';
            $_SESSION['armoryLocaleId'] = 2;
            break;
        case 'en_us':
        case 'enus':
            $_SESSION['armoryLocale'] = 'en_us';
            $_SESSION['armoryLocaleId'] = 0;
            break;
    }
    $_locale = (isset($_SESSION['armoryLocale'])) ? $_SESSION['armoryLocale'] : Armory::GetLocale();
    Armory::SetLocale($_locale, $_SESSION['armoryLocaleId']);
    if (isset($_SERVER['HTTP_REFERER']))
        $returnUrl = $_SERVER['HTTP_REFERER'];
    else
        $returnUrl = $_SESSION['last_url'];
    header('Location: ' . $returnUrl);
    exit;
}
$_locale = (isset($_SESSION['armoryLocale'])) ? $_SESSION['armoryLocale'] : Armory::GetLocale();
if (defined('load_characters_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.characters.php'))
        die('<b>Error:</b> unable to load characters class!');

    $characters = new Characters();
}
if (defined('load_guilds_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.guilds.php'))
        die('<b>Error:</b> unable to load guilds class!');

    $guilds = new Guilds();
}
if (defined('load_achievements_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.achievements.php'))
        die('<b>Error:</b> unable to load achievements class!');

    // Do not create class instance here. It should be created in Characters::GetAchievementMgr().
}
if (defined('load_items_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.items.php'))
        die('<b>Error:</b> unable to load items class!');

    $items = new Items();
}
if (defined('load_mangos_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.mangos.php'))
        die('<b>Error:</b> unable to load Mangos class!');

    $mangos = new Mangos();
}
if (defined('load_arenateams_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.arenateams.php'))
        die('<b>Error:</b> unable to load arenateams class!');

    $arenateams = new Arenateams();
}
if (defined('load_search_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.search.php'))
        die('<b>Error:</b> unable to load search engine class!');

    $search = new SearchMgr();
}
if (defined('load_itemprototype_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.itemprototype.php'))
        die('<b>Error:</b> unable to load ItemPrototype class!');

    // Do not create class instance here. It should be created in Characters or Items classes.
}
if (defined('load_item_class'))
{
    if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.item.php'))
        die('<b>Error:</b> unable to load Item class!');

    // Do not create class instance here. It should be created in Characters or Items classes.
}
// Start XML parser
if (!include(__ARMORYDIRECTORY__ . '/includes/classes/class.xmlhandler.php'))
    die('<b>Error:</b> unable to load XML Handler class!');

$xml = new XMLHandler(Armory::GetLocale());

if (!defined('RSS_FEED'))
    $xml->StartXML();
?>