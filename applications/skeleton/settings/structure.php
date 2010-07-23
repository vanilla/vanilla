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
The Column method (defined in /library/database/class.generic.structure.php) has the following arguments:
  Column(
   $Name, // The name of the column to create.
   $Type, // The data type of the column to be created. Types with a length speecifty the length in barackets.
          //  * If an array of values is provided, the type will be set as "enum" and the array will be assigned as the column's Enum property.
          //  * If an array of two values is specified then a "set" or "enum" can be specified (ex. array('set', array('Short', 'Tall', 'Fat', 'Skinny')))
   $NullOrDefault = FALSE, // A boolean value indicating if the column allows nulls
   $Default = NULL, //  Whether or not nulls are allowed, if not a default can be specified.
                    //   * TRUE: Nulls are allowed.
                    //   * FALSE: Nulls are not allowed.
                    //   * Any other value: Nulls are not allowed, and the specified value will be used as the default.
   $KeyType = FALSE, // What type of key is this column on the table? Options are primary, key, and FALSE (not a key).
  );

Example table construction:

$Construct = Gdn::Structure();

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
*/