<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Use this file to construct tables and views necessary for your application.
// There are some examples below to get you started.

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;
   
/*
The Column method (defined in /library/database/class.generic.structure.php)
has the following arguments:
  Column(
   $Name, // The name of the column to add
   $Type, // The type of column to add
   $Length = '', // The length of the column (if applicable)
   $Null = FALSE, // A boolean value indicating if the column allows nulls
   $Default = NULL, // The default value of the column 
   $KeyType = FALSE, // The type of key to make the column (primary or key)
   $AutoIncrement = FALSE // Should the field auto_increment?
  );

Example table construction:

$Construct->Table('ExampleTable')
	->PrimaryKey('ExampleTableID')
   ->Column('ExampleUserID', 'int', TRUE)
   ->Column('Field1', 'varchar(50)')
   ->Set($Explicit, $Drop);

Example view construction:

$SQL = $Database->SQL();
$SQL->Select('e.ExampleTableID, e.ExampleUserID, u.Name as ExampleUser, e.Field1')
   ->From('ExampleTable e')
   ->Join('User u', 'e.ExampleUserID = u.UserID');
$Construct->View('vw_Example', $SQL);
*/