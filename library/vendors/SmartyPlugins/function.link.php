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
 * Takes a route and prepends the web root (expects "/controller/action/params" as $Destination).
 *
 * @param array The parameters passed into the function.
 * The parameters that can be passed to this function are as follows.
 * - <b>dest</b>: The destination of the url.
 * - <b>withdomain</b>: Whether or not to add the domain to the url.
 * @param Smarty The smarty object rendering the template.
 * @return The url.
 */
function smarty_function_link($Params, &$Smarty) {
   $Destination = GetValue('url', $Params, '', TRUE);
   $WithDomain = GetValue('withdomain', $Params, FALSE, TRUE);
   $RemoveSyndication = GetValue('removeSyndication', $Params, FALSE, TRUE);
   $Text = GetValue('text', $Params, '', TRUE);
   $NoTag = GetValue('notag', $Params, FALSE, TRUE);
   $Class = GetValue('class', $Params, '', TRUE);

   $Session = Gdn::Session();
   $Authenticator = Gdn::Authenticator();

   // Use some logic to expan special urls.
   switch(strtolower($Destination)) {
      case "signin":
      case "signout":
      case "signinout":
      case "entry/leave":
         // The destination is the signin/signout toggle link.
         if ($Session->IsValid()) {
            if(!$Text && !$NoTag)
               $Text = T('Sign Out');
            $Destination = $Authenticator->SignOutUrl();
            $Class = ConcatSep(' ', $Class, 'SignOut');
         } else {
            if(!$Text && !$NoTag)
               $Text = T('Sign In');
            $Attribs = array();

            $Destination = $Authenticator->SignInUrl('');
            if (Gdn::Config('Garden.SignIn.Popup'))
               $Class = ConcatSep(' ', $Class, 'SignInPopup');
         }
         break;
   }

   $Url = Url($Destination, $WithDomain, $RemoveSyndication);
   $Url = str_replace('{Session_TransientKey}', $Session->TransientKey(), $Url);

   if(!$Text)
      $NoTag = TRUE;

   if($NoTag) {
      $Result = $Url;
   } else {
      $Result = '<a';

      // Add the standard attrbutes to the anchor.
      $ID = GetValue('id', $Params, '', TRUE);
      if($ID)
         $Result .= ' id="'.urlencode($ID).'"';
      $Result .= ' href="'.$Url.'"';
      if($Class)
         $Result .= ' class="'.urlencode($Class).'"';

      // Add anything that's left over.
      foreach($Params as $Key => $Value) {
         $Result .= ' '.$Key.'="'.urlencode($Value).'"';
      }

      // Add the link text.
      $Result .= '>'.$Text.'</a>';
   }
   return $Result;
}