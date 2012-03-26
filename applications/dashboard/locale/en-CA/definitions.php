<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * English version of object possession. (ie. "Bob's thing" VS "Jess' thing").
 *
 * @param string The word to format.
 */
if (!function_exists('FormatPossessive')) {
   function FormatPossessive($Word) {
      return substr($Word, -1) == 's' ? $Word."'" : $Word."'s";
   }
}

if (!function_exists('Plural')) {
   function Plural($Number, $Singular, $Plural) {
		// Make sure to fix comma-formatted numbers
      $WorkingNumber = str_replace(',', '', $Number);
      return sprintf(T($WorkingNumber == 1 ? $Singular : $Plural), $Number);
   }
}

$Definition['Locale'] = 'en-CA';
$Definition['_Locale'] = 'Locale';

// THESE ARE RELATED TO VALIDATION FUNCTIONS IN /garden/library/core/validation.functions.php
$Definition['ValidateRegex'] = '%s does not appear to be in the correct format.';
$Definition['ValidateRequired'] = '%s is required.';
$Definition['ValidateRequiredArray'] = 'You must select at least one %s.';
$Definition['ValidateEmail'] = '%s does not appear to be valid.';
$Definition['ValidateFormat'] = 'You are not allowed to post raw html.';
$Definition['ValidateDate'] = '%s is not a valid date.';
$Definition['ValidateInteger'] = '%s is not a valid integer.';
$Definition['ValidateBoolean'] = '%s is not a valid boolean.';
$Definition['ValidateDecimal'] = '%s is not a valid decimal.';
$Definition['ValidateTime'] = '%s is not a valid time.';
$Definition['ValidateTimestamp'] = '%s is not a valid timestamp.';
$Definition['ValidateUsername'] = 'Usernames must be 3-20 characters and consist of letters, numbers, and underscores.';
$Definition['ValidateLength'] = '%1$s is %2$s characters too long.';
$Definition['ValidateEnum'] = '%s is not valid.';
$Definition['ValidateOneOrMoreArrayItemRequired'] = 'You must select at least one %s.';
$Definition['ValidateConnection'] = 'The connection parameters you specified failed to open a connection to the database. The database reported the following error: <code>%s</code>';
//$Definition['ValidateMinimumAge'] = 'You are too young to proceed.';
$Definition['ValidateMatch'] = 'The %s fields do not match.';
$Definition['ValidateVersion'] = 'The %s field is not a valid version number. See the php version_compare() function for examples of valid version numbers.';
$Definition['ValidateBanned'] = 'That %s is not allowed.';
$Definition['ValidateUrlStringRelaxed'] = '%s can not contain slashes, quotes or tag characters.';
$Definition['ErrorPermission'] = 'Sorry, permission denied.';
$Definition['InviteErrorPermission'] = 'Sorry, permission denied.';
$Definition['ErrorBadInvitationCode'] = 'The invitation code you supplied is not valid.';
$Definition['ErrorCredentials'] = 'Sorry, no account could be found related to the email/username and password you entered.';
$Definition['ErrorPluginVersionMatch'] = 'The enabled {0} plugin (version {1}) failed to meet the version requirements ({2}).';
$Definition['ErrorPluginDisableRequired'] = 'You cannot disable the {0} plugin because the {1} plugin requires it in order to function.';
$Definition['ErrorPluginEnableRequired'] = 'This plugin requires that the {0} plugin be enabled before it can be enabled itself.';
$Definition['ErrorTermsOfService'] = 'You must agree to the terms of service.';
$Definition['ErrorRecordNotFound'] = 'The requested record could not be found.';

$Definition['PageDetailsMessageFull'] = '%1$s to %2$s of %3$s';
$Definition['PageDetailsMessage'] = '%1$s to %2$s';
$Definition['RoleID'] = 'role';
$Definition['Garden.Registration.DefaultRoles'] = 'default role';
$Definition['Garden.Title'] = 'Banner Title';
$Definition['Garden.Email.SupportName'] = 'Support name';
$Definition['Garden.Email.SupportAddress'] = 'Support email';
$Definition['UrlCode'] = 'Url code';
$Definition['OldPassword'] = 'Old password';

$Definition['RoleID'] = 'role';

$Definition['EmailHeader'] = 'Hello {User.Name}!
';
$Definition['EmailFooter'] = '
Have a great day!';

$Definition['EmailInvitation'] = 'Hello!

%1$s has invited you to join %2$s. If you want to join, you can do so by clicking this link:

  %3$s';
$Definition['EmailMembershipApproved'] = 'Hello %1$s,

You have been approved for membership. Sign in now at the following link:

  %2$s';
$Definition['EmailWelcome'] = '%2$s has created an account for you at %3$s. Your login credentials are:

  Email: %6$s
  Password: %5$s
  Url: %4$s';
$Definition['EmailPassword'] = '%2$s has reset your password at %3$s. Your login credentials are now:

  Email: %6$s
  Password: %5$s
  Url: %4$s';
$Definition['EmailConfirmEmail'] = 'You need to confirm your email address before you can continue. Please confirm your email address by clicking on the following link: {/entry/emailconfirm,exurl,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
$Definition['EmailWelcomeRegister'] = 'You have successfully registered for an account at {Title}. Here is your information:

  Username: {User.Name}
  Email: {User.Email}

You can access the site at {/,exurl,domain}.';
$Definition['EmailWelcomeConnect'] = 'You have successfully connected to {Title}. Here is your information:

  Username: {User.Name}
  Connected With: {ProviderName}

You can access the site at {/,exurl,domain}.';
$Definition['PasswordRequest'] = 'Someone has requested to reset your password at %2$s. To reset your password, follow this link:

  %3$s

If you did not make this request, disregard this email.';
$Definition['EmailNotification'] = '%1$s

Follow the link below to check it out:
%2$s

Have a great day!';
$Definition['EmailStoryNotification'] = '%1$s

%3$s

---
Follow the link below to check it out:
%2$s

Have a great day!';
$Definition['PluginHelp'] = "Plugins allow you to add functionality to your site.<br />Once a plugin has been added to your %s folder, you can enable or disable it here.";
$Definition['ApplicationHelp'] = "Applications allow you to add large groups of functionality to your site.<br />Once an application has been added to your %s folder, you can enable or disable it here.";
$Definition['ThemeHelp'] = "Themes allow you to change the look &amp; feel of your site.<br />Once a theme has been added to your %s folder, you can enable it here.";
$Definition['AddonProblems'] = "<h2>Problems?</h2><p>If something goes wrong with an addon and you can't use your site, you can disable them manually by editing:</p>%s";
$Definition['Date.DefaultFormat'] = '%B %e, %Y';
$Definition['Date.DefaultDayFormat'] = '%B %e';
$Definition['Date.DefaultYearFormat'] = '%B %Y';
$Definition['Date.DefaultTimeFormat'] = '%l:%M%p';
$Definition['Date.DefaultDateTimeFormat'] = '%B %e, %Y %l:%M%p';
$Definition['Saved'] = 'Your changes have been saved.';
$Definition['%s New Plural'] = '%s new';

$Definition['TermsOfService'] = 'Terms of Service';
$Definition['TermsOfServiceText'] = "
   <p>You agree, through your use of this service, that you will not use this
   community to post any material which is knowingly false and/or defamatory,
   inaccurate, abusive, vulgar, hateful, harassing, obscene, profane, sexually
   oriented, threatening, invasive of a person's privacy, or otherwise violative
   of any law. You agree not to post any copyrighted material unless the
   copyright is owned by you.</p>
   
   <p>We at this community also reserve the right to reveal your identity (or
   whatever information we know about you) in the event of a complaint or legal
   action arising from any message posted by you. We log all internet protocol
   addresses accessing this web site.</p>
   
   <p>Please note that advertisements, chain letters, pyramid schemes, and
   solicitations are inappropriate on this community.</p>
   
   <p>We reserve the right to remove any content for any reason or no reason at
   all. We reserve the right to terminate any membership for any reason or no
   reason at all.</p>
   
   <p>You must be at least 13 years of age to use this service.</p>
";

$Definition['Warning: This is for advanced users.'] = '<b>Warning</b>: This is for advanced users and requires that you make additional changes to your web server. This is usually only available if you have dedicated or vps hosting. Do not attempt this if you do not know what you are doing.';
$Definition['Activity.Delete'] = '×';
$Definition['Draft.Delete'] = '×';
$Definition['ConnectName'] = 'Username';

// TODO: PROVIDE TRANSLATIONS FOR ALL CONFIGURATION SETTINGS THAT ARE EDITABLE ON ADMIN FORMS (ie. Vanilla.Comments.MaxLength, etc).