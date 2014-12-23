<?php if (!defined('APPLICATION')) exit();

/**
 * Adds 301 redirects for Vanilla from common forum platforms.
 * 
 * Changes:
 *  1.0        Initial Release
 * 
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Redirector'] = array(
   'Name' => 'Forum Redirector',
   'Description' => "Adds 301 redirects for Vanilla from common forum platforms. This redirector redirects urls from IPB, phpBB, punBB, smf, vBulletin, and Xenforo",
   'Version' => '1.0.1b',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'MobileFriendly' => TRUE,
);

class RedirectorPlugin extends Gdn_Plugin {
   public static $Files = array(
      'forum' => array('RedirectorPlugin', 'forum_Filter'),
      'forums' => array( // xenforo cateogry
         '_arg0' => array('CategoryID', 'Filter' => array('RedirectorPlugin', 'XenforoID')),
         '_arg1' => array('Page', 'Filter' => array('RedirectorPlugin', 'GetNumber'))
         ),
      'forumdisplay.php' => array( // vBulletin category
         'f' => 'CategoryID',
         'page' => 'Page',
         '_arg0' => array('CategoryID', 'Filter' => array('RedirectorPlugin', 'RemoveID')),
         '_arg1' => array('Page', 'Filter' => array('RedirectorPlugin', 'GetNumber'))
         ),
      'forumindex.jspa' => array( // jive 4 category
          'categoryID' => 'CategoryID'
       ),
      'forum.jspa' => array( // jive 4; forums imported as tags
         'forumID' => 'TagID',
         'start' => 'Offset'
      ),
      'thread.jspa' => array( //jive 4 comment/discussion
         'threadID' => 'DiscussionID'
      ),
      'category.jspa' => array(  // jive 4 category
         'categoryID' => 'CategoryID'
      ),
      'index.php' => array( // smf
         'board' => array('CategoryID', 'Filter' => array('RedirectorPlugin', 'SmfOffset')),
         'topic' => array('DiscussionID', 'Filter' => array('RedirectorPlugin', 'SmfOffset')),
         'action' => array('_', 'Filter' => array('RedirectorPlugin', 'SmfAction'))
         ),
      'member.php' => array( // vBulletin user
         'u' => 'UserID',
         '_arg0' => array('UserID', 'Filter' => array('RedirectorPlugin', 'RemoveID'))
         ),
      'memberlist.php' => array( // phpBB user
         'u' => 'UserID'
         ),
      'members' => array( // xenforo profile
         '_arg0' => array('UserID', 'Filter' => array('RedirectorPlugin', 'XenforoID'))
         ),
      'post' => array( // punbb comment
         '_arg0' => 'CommentID'
         ),
      'showpost.php' => array( // vBulletin comment
         'p' => 'CommentID'
         ),
      'showthread.php' => array( // vBulletin discussion
         't' => 'DiscussionID',
         'p' => 'CommentID',
         'page' => 'Page',
         '_arg0' => array('DiscussionID', 'Filter' => array('RedirectorPlugin', 'RemoveID')),
         '_arg1' => array('Page', 'Filter' => array('RedirectorPlugin', 'GetNumber'))
         ),
      'threads' => array( // xenforo discussion
         '_arg0' => array('DiscussionID', 'Filter' => array('RedirectorPlugin', 'XenforoID')),
         '_arg1' => array('Page', 'Filter' => array('RedirectorPlugin', 'GetNumber'))
         ),
      'topic' => array('RedirectorPlugin', 'topic_Filter'),
//      'user' => array( // ipb user
//         '_arg0' => array('UserID', 'Filter' => array('RedirectorPlugin', 'RemoveID'))
//         ),
      'viewforum.php' => array( // phpBB category
         'f' => 'CategoryID',
         'start' => 'Offset'
         ),
      'viewtopic.php' => array( // phpBB discussion/comment
         't' => 'DiscussionID',
         'p' => 'CommentID',
         'start' => 'Offset'
         ),
      'profile.jspa' => array( //jive4 profile
         'userID' => 'UserID'
      )
   );
   
   /**
    * @param Gdn_Dispatcher $Sender
    */
   public function Gdn_Dispatcher_NotFound_Handler($Dispatcher, $Args) {
      $Path = Gdn::Request()->Path();
      $Get = Gdn::Request()->Get();
      
      Trace(array('Path' => $Path, 'Get' => $Get), 'Input');
      
      // Figure out the filename.
      $Parts = explode('/', $Path);
      $After = array();
      $Filename = '';
      while(count($Parts) > 0) {
         $V = array_pop($Parts);
         if (preg_match('`.*\.php`', $V)) {
            $Filename = $V;
            break;
         }
         
         array_unshift($After, $V);
      }
      if ($Filename == 'index.php') {
         // Some site have an index.php?the/path.
         $TryPath = GetValue(0, array_keys($Get));
         if (!$Get[$TryPath]) {
            $After = array_merge(explode('/', $TryPath));
            unset($Get[$TryPath]);
            $Filename = '';
         }
      }
      if (!$Filename) {
         // There was no filename, so we can try the first folder as the filename.
         while (count($After) > 0) {
            $Filename = array_shift($After);
            if (isset(self::$Files[$Filename]))
               break;
         }
      }

      // Add the after parts to the array.
      $i = 0;
      foreach ($After as $Arg) {
         $Get["_arg$i"] = $Arg;
         $i++;
      }
      
      $Url = $this->FilenameRedirect($Filename, $Get);
      if ($Url) {
         if (Debug())
            Trace($Url, "Redirect found");
         else
            Redirect($Url, 301);
      }
   }
   
   public function FilenameRedirect($Filename, $Get) {
      Trace(array('Filename' => $Filename, 'Get' => $Get), 'Testing');
      $Filename = strtolower($Filename);
      array_change_key_case($Get);

      if (!isset(self::$Files[$Filename]))
         return FALSE;
      
      $Row = self::$Files[$Filename];
      
      if (is_callable($Row)) {
         // Use a callback to determine the translation.
         $Row = call_user_func($Row, $Get);
      }
      
      // Translate all of the get parameters into new parameters.
      $Vars = array();
      foreach ($Get as $Key => $Value) {
         if (!isset($Row[$Key]))
            continue;
         
         $Opts = (array)$Row[$Key];
         
         if (isset($Opts['Filter'])) {
            // Call the filter function to change the value.
            $R = call_user_func($Opts['Filter'], $Value, $Opts[0]);
            if (is_array($R)) {
               if (isset($R[0])) {
                  // The filter can change the column name too.
                  $Opts[0] = $R[0];
                  $Value = $R[1];
               } else {
                  // The filter can return return other variables too.
                  $Vars = array_merge($Vars, $R);
                  $Value = NULL;
               }
            } else {
               $Value = $R;
            }
         }
         
         if ($Value !== NULL)
            $Vars[$Opts[0]] = $Value;
      }
      
      Trace($Vars, 'Translated Arguments');
      // Now let's see what kind of record we have.
      // We'll check the various primary keys in order of importance.
      $Result = FALSE;
      if (isset($Vars['CommentID'])) {
         Trace("Looking up comment {$Vars['CommentID']}.");
         
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->GetID($Vars['CommentID']);
         if ($Comment)
            $Result = CommentUrl($Comment, '//');
      } elseif (isset($Vars['DiscussionID'])) {
         Trace("Looking up discussion {$Vars['DiscussionID']}.");
         
         
         $DiscussionModel = new DiscussionModel();
         $DiscussionID = $Vars['DiscussionID'];
         $Discussion = FALSE;
         
         if (is_numeric($DiscussionID)) {
            $Discussion = $DiscussionModel->GetID($Vars['DiscussionID']);
         } else {
            // This is a slug style discussion ID. Let's see if there is a UrlCode column in the discussion table.
            $DiscussionModel->DefineSchema();
            if ($DiscussionModel->Schema->FieldExists('Discussion', 'UrlCode')) {
               $Discussion = $DiscussionModel->GetWhere(array('UrlCode' => $DiscussionID))->FirstRow();
            }
         }
         
         if ($Discussion)
            $Result = DiscussionUrl($Discussion, self::PageNumber($Vars, 'Vanilla.Comments.PerPage'), '//');
      } elseif (isset($Vars['UserID'])) {
         Trace("Looking up user {$Vars['UserID']}.");
         
         $User = Gdn::UserModel()->GetID($Vars['UserID']);
         if ($User)
            $Result = Url(UserUrl($User), '//');
      } elseif (isset($Vars['TagID'])) {
         $Tag = TagModel::instance()->GetID($Vars['TagID']);
         if ($Tag) {
             $Result = TagUrl($Tag, self::PageNumber($Vars, 'Vanilla.Discussions.PerPage'), '//');
         }
      } elseif (isset($Vars['CategoryID'])) {
         Trace("Looking up category {$Vars['CategoryID']}.");
         
         $Category = CategoryModel::Categories($Vars['CategoryID']);
         if ($Category)
            $Result = CategoryUrl($Category, self::PageNumber($Vars, 'Vanilla.Discussions.PerPage'), '//');
      }
      
      return $Result;
   }
   
   public static function forum_Filter($Get) {
      if (GetValue('_arg2', $Get) == 'page') {
         // This is a punbb style forum.
         return array(
            '_arg0' => 'CategoryID',
            '_arg3' => 'Page'
            );
      } elseif (GetValue('_arg1', $Get) == 'page') {
         // This is a bbPress style forum.
         return array(
            '_arg0' => 'CategoryID',
            '_arg2' => 'Page');
      } else {
         // This is an ipb style topic.
         return array(
            '_arg0' => array('CategoryID', 'Filter' => array('RedirectorPlugin', 'RemoveID')),
            '_arg1' => array('Page', 'Filter' => array('RedirectorPlugin', 'IPBPageNumber'))
            );
      }
   }
   
   public static function GetNumber($Value) {
      if (preg_match('`(\d+)`', $Value, $Matches))
         return $Matches[1];
      return NULL;
   }
   
   public static function IPBPageNumber($Value) {
      if (preg_match('`page__st__(\d+)`i', $Value, $Matches))
         return array('Offset', $Matches[1]);
      return self::GetNumber($Value);
   }
   
   /**
    * Return the page number from the given variables that may have an offset or a page.
    * 
    * @param array $Vars The variables that should contain an Offset or Page key.
    * @param int|string $PageSize The pagesize or the config key of the pagesize.
    * @return int
    */
   public static function PageNumber($Vars, $PageSize) {
      if (isset($Vars['Page']))
         return $Vars['Page'];
      if (isset($Vars['Offset'])) {
         if (is_numeric($PageSize))
            return PageNumber($Vars['Offset'], $PageSize, FALSE, Gdn::Session()->IsValid());
         else
            return PageNumber($Vars['Offset'], C($PageSize, 30), FALSE, Gdn::Session()->IsValid());
      }
      return 1;
   }
   
   public static function RemoveID($Value) {
      if (preg_match('`^(\d+)`', $Value, $Matches))
         return $Matches[1];
      return NULL;
   }
   
   public static function SmfAction($Value) {
      if (preg_match('`(\w+);(\w+)=(\d+)`', $Value, $M)) {
         switch (strtolower($M[1])) {
            case 'profile':
               return array('UserID', $M[3]);
         }
      }
   }
   
   public static function SmfOffset($Value, $Key) {
      if (preg_match('`(\d+)\.(\d+)`', $Value, $M)) {
         return array($Key => $M[1], 'Offset' => $M[2]);
      }
      if (preg_match('`\d+\.msg(\d+)`', $Value, $M)) {
         return array('CommentID' => $M[1]);
      }
   }
   
   public static function topic_Filter($Get) {
      if (GetValue('_arg2', $Get) == 'page') {
         // This is a punbb style topic.
         return array(
            '_arg0' => 'DiscussionID',
            '_arg3' => 'Page'
            );
      } elseif (GetValue('_arg1', $Get) == 'page') {
         // This is a bbPress style topc.
         return array(
            '_arg0' => 'DiscussionID',
            '_arg2' => 'Page');
      } else {
         // This is an ipb style topic.
         return array(
            'p' => 'CommentID',
            '_arg0' => array('DiscussionID', 'Filter' => array('RedirectorPlugin', 'RemoveID')),
            '_arg1' => array('Page', 'Filter' => array('RedirectorPlugin', 'IPBPageNumber'))
            );
      }
   }
   
   public static function XenforoID($Value) {
      if (preg_match('`(\d+)$`', $Value, $Matches))
         return $Matches[1];
      return $Value;
   }
}