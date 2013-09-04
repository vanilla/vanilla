<?php if (!defined('APPLICATION')) exit();

/**
 * Theme system
 * 
 * Allows access to theme controls from within views, to give themers a unified
 * toolset for interacting with Vanilla from within views.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Theme {

   protected static $_AssetInfo = array();
   public static function AssetBegin($AssetContainer = 'Panel') {
      self::$_AssetInfo[] = array('AssetContainer' => $AssetContainer);
      ob_start();
   }

   public static function AssetEnd() {
      if (count(self::$_AssetInfo) == 0)
         return;

      $Asset = ob_get_clean();
      $AssetInfo = array_pop(self::$_AssetInfo);

      Gdn::Controller()->AddAsset($AssetInfo['AssetContainer'], $Asset);
   }

   public static function Breadcrumbs($Data, $HomeLink = TRUE) {
      $Format = '<a href="{Url,html}" itemprop="url"><span itemprop="title">{Name,html}</span></a>';
      
      $Result = '';
      
      if (!is_array($Data))
         $Data = array();

      
      if ($HomeLink) {
         $Row = array('Name' => $HomeLink, 'Url' => Url('/', TRUE), 'CssClass' => 'CrumbLabel HomeCrumb');
         if (!is_string($HomeLink))
            $Row['Name'] = T('Home');
         
         array_unshift($Data, $Row);
      }
      
      $DefaultRoute = ltrim(GetValue('Destination', Gdn::Router()->GetRoute('DefaultController'), ''), '/');

      $Count = 0;
      foreach ($Data as $Row) {
         if (ltrim($Row['Url'], '/') == $DefaultRoute && $HomeLink)
            continue; // don't show default route twice.
         
         // Add the breadcrumb wrapper.
         if ($Count > 0) {
            $Result .= '<span itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">';
         }
         
         $Row['Url'] = Url($Row['Url']);
         $CssClass = GetValue('CssClass', $Row, 'CrumbLabel');
         $Label = '<span class="'.$CssClass.'">'.FormatString($Format, $Row).'</span> ';
         $Result = ConcatSep('<span class="Crumb">'.T('Breadcrumbs Crumb', '›').'</span> ', $Result, $Label);
         
         $Count++;
      }
      
      // Close the stack.
      for ($Count--;$Count > 0; $Count--) {
         $Result .= '</span>';
      }

      $Result ='<span class="Breadcrumbs" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">'.$Result.'</span>';
      return $Result;
   }
   
   protected static $_BulletSep = FALSE;
   protected static $_BulletSection = FALSE;
   
   /**
    * Call before writing an item and it will optionally write a bullet seperator.
    * 
    * @param string $Section The name of the section.
    * @param bool $Return whether or not to return the result or echo it.
    * @return string
    * @since 2.1
    */
   public static function BulletItem($Section, $Return = TRUE) {
      $Result = '';
      
      if (self::$_BulletSection === FALSE)
         self::$_BulletSection = $Section;
      elseif (self::$_BulletSection != $Section) {
         $Result = "<!-- $Section -->".self::$_BulletSep;
         self::$_BulletSection = $Section;
      }
      
      if ($Return)
         return $Result;
      else
         echo $Result;
   }
   
   /**
    * Call before starting a row of bullet-seperated items.
    * 
    * @param strng|bool $Sep The seperator used to seperate each section.
    * @since 2.1
    */
   public static function BulletRow($Sep = FALSE) {
      if (!$Sep) {
         if (!self::$_BulletSep)
            self::$_BulletSep = ' '.Bullet().' ';
      } else {
         self::$_BulletSep = $Sep;
      }
      self::$_BulletSection = FALSE;
   }
   
   
   
   /**
    * Returns whether or not the page is in the current section.
    * @param string|array $Section 
    */
   public static function InSection($Section) {
      $Section = (array)$Section;
      foreach ($Section as $Name) {
         if (isset(self::$_Section[$Name]))
            return TRUE;
      }
      return FALSE;
   }
   
   public static function Link($Path, $Text = FALSE, $Format = NULL, $Options = array()) {
      $Session = Gdn::Session();
      $Class = GetValue('class', $Options, '');
      $WithDomain = GetValue('WithDomain', $Options);
      $Target = GetValue('Target', $Options, '');
      if ($Target == 'current')
         $Target = trim(Url('', TRUE), '/ ');
      
      if (is_null($Format))
         $Format = '<a href="%url" class="%class">%text</a>';

      switch ($Path) {
         case 'activity':
            TouchValue('Permissions', $Options, 'Garden.Activity.View');
            break;
         case 'category':
            $Breadcrumbs = Gdn::Controller()->Data('Breadcrumbs');
            if (is_array($Breadcrumbs) && count($Breadcrumbs) > 0) {
               $Last = array_pop($Breadcrumbs);
               $Path = GetValue('Url', $Last);
               $DefaultText = GetValue('Name', $Last, T('Back'));
            } else {
               $Path = '/';
               $DefaultText = C('Garden.Title', T('Back'));
            }
            if (!$Text)
               $Text = $DefaultText;
            break;
         case 'dashboard':
            $Path = 'dashboard/settings';
            TouchValue('Permissions', $Options, array('Garden.Settings.Manage','Garden.Settings.View'));
            if (!$Text)
               $Text = T('Dashboard');
            break;
         case 'home':
            $Path = '/';
            if (!$Text)
               $Text = T('Home');
            break;
         case 'inbox':
            $Path = 'messages/inbox';
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text)
               $Text = T('Inbox');
            if ($Session->IsValid() && $Session->User->CountUnreadConversations) {
               $Class = trim($Class.' HasCount');
               $Text .= ' <span class="Alert">'.$Session->User->CountUnreadConversations.'</span>';
            }
            if (!$Session->IsValid() || !Gdn::ApplicationManager()->CheckApplication('Conversations'))
               $Text = FALSE;
            break;
         case 'forumroot':
            $Route = Gdn::Router()->GetDestination('DefaultForumRoot');
            if (is_null($Route))
               $Path = '/';
            else
               $Path = CombinePaths (array('/',$Route));
            break;
         case 'profile':
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text && $Session->IsValid())
               $Text = $Session->User->Name;
            if ($Session->IsValid() && $Session->User->CountNotifications) {
               $Class = trim($Class.' HasCount');
               $Text .= ' <span class="Alert">'.$Session->User->CountNotifications.'</span>';
            }
            break;
         case 'user':
            $Path = 'profile';
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text && $Session->IsValid())
               $Text = $Session->User->Name;

            break;
         case 'photo':
            $Path = 'profile';
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text && $Session->IsValid()) {
               $IsFullPath = strtolower(substr($Session->User->Photo, 0, 7)) == 'http://' || strtolower(substr($Session->User->Photo, 0, 8)) == 'https://';
               $PhotoUrl = ($IsFullPath) ? $Session->User->Photo : Gdn_Upload::Url(ChangeBasename($Session->User->Photo, 'n%s'));
               $Text = Img($PhotoUrl, array('alt' => htmlspecialchars($Session->User->Name)));
            }

            break;
         case 'drafts':
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text)
               $Text = T('My Drafts');
            if ($Session->IsValid() && $Session->User->CountDrafts) {
               $Class = trim($Class.' HasCount');
               $Text .= ' <span class="Alert">'.$Session->User->CountDrafts.'</span>';
            }
            break;
         case 'discussions/bookmarked':
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text)
               $Text = T('My Bookmarks');
            if ($Session->IsValid() && $Session->User->CountBookmarks) {
               $Class = trim($Class.' HasCount');
               $Text .= ' <span class="Count">'.$Session->User->CountBookmarks.'</span>';
            }
            break;
         case 'discussions/mine':
            TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
            if (!$Text)
               $Text = T('My Discussions');
            if ($Session->IsValid() && $Session->User->CountDiscussions) {
               $Class = trim($Class.' HasCount');
               $Text .= ' <span class="Count">'.$Session->User->CountDiscussions.'</span>';
            }
            break;
         case 'signin':
         case 'signinout':
            // The destination is the signin/signout toggle link.
            if ($Session->IsValid()) {
               if(!$Text)
                  $Text = T('Sign Out');
               $Path =  SignOutUrl($Target);
               $Class = ConcatSep(' ', $Class, 'SignOut');
            } else {
               if(!$Text)
                  $Text = T('Sign In');
               $Attribs = array();

               $Path = SignInUrl($Target);
               if (SignInPopup() && strpos(Gdn::Request()->Url(), 'entry') === FALSE)
                  $Class = ConcatSep(' ', $Class, 'SignInPopup');
            }
            break;
      }
      
      if ($Text == FALSE && strpos($Format, '%text') !== FALSE)
         return '';
      
      if (GetValue('Permissions', $Options) && !$Session->CheckPermission($Options['Permissions'], FALSE))
         return '';

      $Url = Gdn::Request()->Url($Path, $WithDomain);
      
      if ($TK = GetValue('TK', $Options)) {
         if (in_array($TK, array(1, 'true')))
            $TK = 'TransientKey';
         $Url .= (strpos($Url, '?') === FALSE ? '?' : '&').$TK.'='.urlencode(Gdn::Session()->TransientKey());
      }

      if (strcasecmp(trim($Path, '/'), Gdn::Request()->Path()) == 0)
         $Class = ConcatSep(' ', $Class, 'Selected');

      // Build the final result.
      $Result = $Format;
      $Result = str_replace('%url', $Url, $Result);
      $Result = str_replace('%text', $Text, $Result);
      $Result = str_replace('%class', $Class, $Result);
      
      return $Result;
   }

   /**
    * Renders the banner logo, or just the banner title if the logo is not defined.
    */
   public static function Logo() {
      $Logo = C('Garden.Logo');
      if ($Logo) {
         $Logo = ltrim($Logo, '/');
         // Fix the logo path.
         if (StringBeginsWith($Logo, 'uploads/'))
            $Logo = substr($Logo, strlen('uploads/'));
      }
      $Title = C('Garden.Title', 'Title');
      echo $Logo ? Img(Gdn_Upload::Url($Logo), array('alt' => $Title)) : $Title;
   }

   public static function Module($Name, $Properties = array()) {
      try {
         if (!class_exists($Name)) {
            if (Debug())
               $Result = "Error: $Name doesn't exist";
            else
               $Result = "<!-- Error: $Name doesn't exist -->";
         } else {
               $Module = new $Name(Gdn::Controller(), '');
               $Module->Visible = TRUE;
               foreach ($Properties as $Name => $Value) {
                  $Module->$Name = $Value;
               }
               
               $Result = $Module->ToString();
         }
      } catch (Exception $Ex) {
         if (Debug())
            $Result = '<pre class="Exception">'.htmlspecialchars($Ex->getMessage()."\n".$Ex->getTraceAsString()).'</pre>';
         else
            $Result = $Ex->getMessage();
      }
      return $Result;
   }
   
   public static function Pagename() {
      $Application = Gdn::Dispatcher()->Application();
      $Controller = Gdn::Dispatcher()->Controller();
      switch ($Controller) {
         case 'discussions':
         case 'discussion':
         case 'post':
            return 'discussions';
            
         case 'inbox':
            return 'inbox';
            
         case 'activity':
            return 'activity';
            
         case 'profile':
            $Args = Gdn::Dispatcher()->ControllerArguments();
            if (!sizeof($Args) || (sizeof($Args) && $Args[0] == Gdn::Session()->UserID))
               return 'profile';
            break;
      }
      
      return 'unknown';
   }
   
   /**
    * @var array
    */
   protected static $_Section = array();
   
   /**
    * The current section the site is in. This can be one or more values. Think of it like a server-side css-class.
    * @since 2.1
    * @param string $Section The name of the section
    * @param string $Method One of:
    *  - add
    *  - remove
    *  - set
    *  - get
    */
   public static function Section($Section, $Method = 'add') {
      $Section = array_fill_keys((array)$Section, TRUE);
      
      
      switch (strtolower($Method)) {
         case 'add':
            self::$_Section = array_merge(self::$_Section, $Section);
            break;
         case 'remove':
            self::$_Section = array_diff_key(self::$_Section, $Section);
            break;
         case 'set':
            self::$_Section = $Section;
            break;
         case 'get':
         default:
            return array_keys(self::$_Section);
      }
   }

   public static function Text($Code, $Default) {
      return C("ThemeOption.{$Code}", T('Theme_'.$Code, $Default));
   }
}
