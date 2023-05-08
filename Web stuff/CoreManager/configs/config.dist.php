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
// Core Selection
//
// 0 - Auto-Detect
// 1 - ArcEmu
// 2 - MaNGOS
// 3 - Trinity

$core = 0;

//#############################################################################
// CoreManager Database Configuration

$corem_db["addr"]     = "127.0.0.1:3306";         // SQL server IP:port your CoreManager DB is located on
$corem_db["user"]     = "root";                   // SQL server login your CoreManager DB is located on
$corem_db["pass"]     = "password";               // SQL server pass your CoreManager DB is located on
$corem_db["name"]     = "db name";                // CoreManager DB name
$corem_db["encoding"] = "utf8";                   // SQL connection encoding

//#############################################################################
// SQL Configuration
//
//  NOTICE: ONLY MySQL IS KNOWN TO BE FUNCTIONAL
//
//  SQL server type  :
//  "MySQL"   - MySQL
//  "PgSQL"   - PostgreSQL
//  "MySQLi"  - MySQLi
//  "SQLLite" - SQLite

$db_type          = "MySQL";

?>
