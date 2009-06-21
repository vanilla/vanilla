<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/


/// <summary>
/// English version of object possession. (ie. "Bob's thing" VS "Jess' thing").
/// </summary>
/// <param name="Word" type="string">
/// The word to format.
/// </param>
function FormatPossessive($Word) {
   return substr($Word, -1) == 's' ? $Word."'" : $Word."'s";
}

$Definition['Locale'] = 'en-CA';
$Definition['GardenDefinition'] = 'From the garden locale source!';

$Definition['Garden.Application.Title'] = 'Application title';

// THESE ARE RELATED TO VALIDATION FUNCTIONS IN /garden/library/core/validation.functions.php
$Definition['ValidateRegex'] = '%s does not appear to be in the correct format.';
$Definition['ValidateRequired'] = '%s is required.';
$Definition['ValidateRequiredArray'] = 'You must select at least one %s.';
$Definition['ValidateEmail'] = '%s does not appear to be valid.';
$Definition['ValidateDate'] = '%s is not a valid date.';
$Definition['ValidateInteger'] = '%s is not a valid integer.';
$Definition['ValidateBoolean'] = '%s is not a valid boolean.';
$Definition['ValidateDecimal'] = '%s is not a valid decimal.';
$Definition['ValidateTime'] = '%s is not a valid time.';
$Definition['ValidateTimestamp'] = '%s is not a valid timestamp.';
$Definition['ValidateLength'] = '%1$s is %2$s characters too long.';
$Definition['ValidateEnum'] = '%s is not valid.';
$Definition['ValidateOneOrMoreArrayItemRequired'] = 'You must select at least one %s.';
$Definition['ValidateConnection'] = 'The connection parameters you specified failed to open a connection to the database. The database reported the following error: %s';
$Definition['ValidateMinimumAge'] = 'You must be at least 16 years old to proceed.';
$Definition['ValidateMatch'] = 'The %s fields do not match.';

$Definition['ErrorPermission'] = 'Sorry, permission denied.';
$Definition['ErrorCredentials'] = 'Sorry, no account could be found related to the username and password you entered.';
$Definition['ErrorPluginVersionMatch'] = 'The enabled {0} plugin (version {1}) failed to meet the version requirements ({2}).';
$Definition['ErrorPluginDisableRequired'] = 'You cannot disable the {0} plugin because the {1} plugin requires it in order to function.';
$Definition['ErrorPluginEnableRequired'] = 'This plugin requires that the {0} plugin be enabled before it can be enabled itself.';
$Definition['ErrorTermsOfService'] = 'You must agree to the terms of service.';
$Definition['ErrorRecordNotFound'] = 'The requested record could not be found.';

$Definition['PageDetailsMessageFull'] = '%1$s to %2$s of %3$s';
$Definition['PageDetailsMessage'] = '%1$s to %2$s';
$Definition['RoleID'] = 'role';
$Definition['Garden.Registration.DefaultRoles'] = 'default role';

$Definition['Category1'] = 'Fiddlesticks!';
$Definition['Category2'] = 'Gravy';
$Definition['DateOfBirth'] = 'Birth date';
$Definition['RoleID'] = 'role';

$Definition['EmailInvitation'] = 'Hello!
%1$s has invited you to join %2$s. If you want to join, you can do so by
clicking this link:

%3$s

Have a great day!';
$Definition['EmailWelcome'] = 'Hello %1$s,
%2$s has created an account for you at %3$s. Your login credentials are:

Username: %1$s
Password: %5$s
Url: %4$s

Have a great day!';
$Definition['EmailPassword'] = 'Hello %1$s,
%2$s has reset your password at %3$s. Your login credentials are now:

Username: %1$s
Password: %5$s
Url: %4$s

Have a great day!';

// TODO: PROVIDE TRANSLATIONS FOR ALL CONFIGURATION SETTINGS THAT ARE EDITABLE ON ADMIN FORMS (ie. Vanilla.Comments.MaxLength, etc).

// Begin Literal Translations:
$Definition['{0} (version {1})'] = '{0} (version {1})';