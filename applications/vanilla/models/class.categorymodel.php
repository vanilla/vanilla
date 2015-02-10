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
 * Category Model
 *
 * @package Vanilla
 */

/**
 * Manages discussion categories.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class CategoryModel extends Gdn_Model {
   const CACHE_KEY = 'Categories';

   public $Watching = FALSE;

   /**
    * Merged Category data, including Pure + UserCategory
    *
    * @var array
    */
   public static $Categories = NULL;

   /**
    * Whether or not to explicitly shard the categories cache.
    * @var bool
    */
   public static $ShardCache = FALSE;

   /**
    * Whether or not to join the users in some calls.
    * Forums with a lot of categories may need to optimize using this setting and simpler views.
    * @var bool Whether or not to join users to recent posts.
    */
   public $JoinRecentUsers = TRUE;

   /**
    * Class constructor. Defines the related database table name.
    *
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Category');
   }

   public static function AllowedDiscussionTypes($Category) {
      $Category = self::PermissionCategory($Category);
      $Allowed = GetValue('AllowedDiscussionTypes', $Category);
      $AllTypes = DiscussionModel::DiscussionTypes();;

      if (empty($Allowed) || !is_array($Allowed))
         return $AllTypes;
      else
         return array_intersect_key($AllTypes, array_flip($Allowed));
   }

   /**
    *
    *
    * @since 2.0.18
    * @access public
    * @return array Category IDs.
    */
   public static function CategoryWatch($AllDiscussions = TRUE) {
      $Categories = self::Categories();
      $AllCount = count($Categories);

      $Watch = array();

      foreach ($Categories as $CategoryID => $Category) {
         if ($AllDiscussions && GetValue('HideAllDiscussions', $Category)) {
            continue;
         }

         if ($Category['PermsDiscussionsView'] && $Category['Following']) {
            $Watch[] = $CategoryID;
         }
      }

      Gdn::PluginManager()->EventArguments['CategoryIDs'] =& $Watch;
      Gdn::PluginManager()->FireEvent('CategoryWatch');

      if ($AllCount == count($Watch))
         return TRUE;

      return $Watch;
   }

   /**
    * Gets either all of the categories or a single category.
    *
    * @param int|string|bool $ID Either the category ID or the category url code.
    * If nothing is passed then all categories are returned.
    * @return array Returns either one or all categories.
    * @since 2.0.18
    */
   public static function Categories($ID = FALSE) {

      if (self::$Categories == NULL) {
         // Try and get the categories from the cache.
         self::$Categories = Gdn::Cache()->Get(self::CACHE_KEY);

         if (!self::$Categories) {
            $Sql = Gdn::SQL();
            $Sql = clone $Sql;
            $Sql->Reset();

            $Sql->Select('c.*')
               ->Select('lc.DateInserted', '', 'DateLastComment')
               ->From('Category c')
               ->Join('Comment lc', 'c.LastCommentID = lc.CommentID', 'left')
               ->OrderBy('c.TreeLeft');

            self::$Categories = array_merge(array(), $Sql->Get()->ResultArray());
            self::$Categories = Gdn_DataSet::Index(self::$Categories, 'CategoryID');
            self::BuildCache();
         }

         self::JoinUserData(self::$Categories, TRUE);

      }

      if ($ID !== FALSE) {
         if (!is_numeric($ID) && $ID) {
            $Code = $ID;
            foreach (self::$Categories as $Category) {
               if (strcasecmp($Category['UrlCode'], $Code) === 0) {
                  $ID = $Category['CategoryID'];
                  break;
               }
            }
         }

         if (isset(self::$Categories[$ID])) {
            $Result = self::$Categories[$ID];
            return $Result;
         } else {
            return NULL;
         }
      } else {
         $Result = self::$Categories;
         return $Result;
      }
   }

   /**
    * Build and augment the category cache
    *
    * @param array $Categories
    */
   protected static function BuildCache() {
      self::CalculateData(self::$Categories);
      self::JoinRecentPosts(self::$Categories);
      Gdn::Cache()->Store(self::CACHE_KEY, self::$Categories, array(
         Gdn_Cache::FEATURE_EXPIRY  => 600,
         Gdn_Cache::FEATURE_SHARD   => self::$ShardCache
      ));
   }

   /**
    *
    *
    * @since 2.0.18
    * @access public
    * @param array $Data Dataset.
    */
   protected static function CalculateData(&$Data) {
		foreach ($Data as &$Category) {
         $Category['CountAllDiscussions'] = $Category['CountDiscussions'];
         $Category['CountAllComments'] = $Category['CountComments'];
         $Category['Url'] = self::CategoryUrl($Category, FALSE, '/');
         $Category['ChildIDs'] = array();
         if (GetValue('Photo', $Category))
            $Category['PhotoUrl'] = Gdn_Upload::Url($Category['Photo']);
         else
            $Category['PhotoUrl'] = '';

         if ($Category['DisplayAs'] == 'Default') {
            if ($Category['Depth'] <= C('Vanilla.Categories.NavDepth', 0)) {
               $Category['DisplayAs'] = 'Categories';
            } elseif ($Category['Depth'] == (C('Vanilla.Categories.NavDepth', 0) + 1) && C('Vanilla.Categories.DoHeadings')) {
               $Category['DisplayAs'] = 'Heading';
            } else {
               $Category['DisplayAs'] = 'Discussions';
            }
         }

         if (!GetValue('CssClass', $Category))
            $Category['CssClass'] = 'Category-'.$Category['UrlCode'];

         if (isset($Category['AllowedDiscussionTypes']) && is_string($Category['AllowedDiscussionTypes']))
            $Category['AllowedDiscussionTypes'] = unserialize($Category['AllowedDiscussionTypes']);
		}

      $Keys = array_reverse(array_keys($Data));
      foreach ($Keys as $Key) {
         $Cat = $Data[$Key];
         $ParentID = $Cat['ParentCategoryID'];

         if (isset($Data[$ParentID]) && $ParentID != $Key) {
            $Data[$ParentID]['CountAllDiscussions'] += $Cat['CountAllDiscussions'];
            $Data[$ParentID]['CountAllComments'] += $Cat['CountAllComments'];
            array_unshift($Data[$ParentID]['ChildIDs'], $Key);
         }
      }
	}

   public static function ClearCache() {
      Gdn::Cache()->Remove(self::CACHE_KEY);
   }

   public static function ClearUserCache() {
      $Key = 'UserCategory_'.Gdn::Session()->UserID;
      Gdn::Cache()->Remove($Key);
   }

   public function Counts($Column) {
      $Result = array('Complete' => TRUE);
      switch ($Column) {
         case 'CountDiscussions':
            $this->Database->Query(DBAModel::GetCountSQL('count', 'Category', 'Discussion'));
            break;
         case 'CountComments':
            $this->Database->Query(DBAModel::GetCountSQL('sum', 'Category', 'Discussion', $Column, 'CountComments'));
            break;
         case 'LastDiscussionID':
            $this->Database->Query(DBAModel::GetCountSQL('max', 'Category', 'Discussion'));
            break;
         case 'LastCommentID':
            $Data = $this->SQL
               ->Select('d.CategoryID')
               ->Select('c.CommentID', 'max', 'LastCommentID')
               ->Select('d.DiscussionID', 'max', 'LastDiscussionID')
               ->Select('c.DateInserted', 'max', 'DateLastComment')
               ->Select('d.DateInserted', 'max', 'DateLastDiscussion')

               ->From('Comment c')
               ->Join('Discussion d', 'd.DiscussionID = c.DiscussionID')
               ->GroupBy('d.CategoryID')
               ->Get()->ResultArray();

            // Now we have to grab the discussions associated with these comments.
            $CommentIDs = ConsolidateArrayValuesByKey($Data, 'LastCommentID');

            // Grab the discussions for the comments.
            $this->SQL
               ->Select('c.CommentID, c.DiscussionID')
               ->From('Comment c')
               ->WhereIn('c.CommentID', $CommentIDs);

            $Discussions =  $this->SQL->Get()->ResultArray();
            $Discussions = Gdn_DataSet::Index($Discussions, array('CommentID'));

            foreach ($Data as $Row) {
               $CategoryID = (int)$Row['CategoryID'];
               $Category = CategoryModel::Categories($CategoryID);
               $CommentID = $Row['LastCommentID'];
               $DiscussionID = GetValueR("$CommentID.DiscussionID", $Discussions, NULL);

               $DateLastComment = Gdn_Format::ToTimestamp($Row['DateLastComment']);
               $DateLastDiscussion = Gdn_Format::ToTimestamp($Row['DateLastDiscussion']);

               $Set = array('LastCommentID' => $CommentID);

               if ($DiscussionID) {
                  $LastDiscussionID = GetValue('LastDiscussionID', $Category);

                  if ($DateLastComment >= $DateLastDiscussion) {
                     // The most recent discussion is from this comment.
                     $Set['LastDiscussionID'] = $DiscussionID;
                  } else {
                     // The most recent discussion has no comments.
                     $Set['LastCommentID'] = NULL;
                  }
               } else {
                  // Something went wrong.
                  $Set['LastCommentID'] = NULL;
                  $Set['LastDiscussionID'] = NULL;
               }

               $this->SetField($CategoryID, $Set);
            }
            break;
         case 'LastDateInserted':
            $Categories = $this->SQL
               ->Select('ca.CategoryID')
               ->Select('d.DateInserted', '', 'DateLastDiscussion')
               ->Select('c.DateInserted', '', 'DateLastComment')

               ->From('Category ca')
               ->Join('Discussion d', 'd.DiscussionID = ca.LastDiscussionID')
               ->Join('Comment c', 'c.CommentID = ca.LastCommentID')
               ->Get()->ResultArray();

            foreach ($Categories as $Category) {
               $DateLastDiscussion = GetValue('DateLastDiscussion', $Category);
               $DateLastComment = GetValue('DateLastComment', $Category);

               $MaxDate = $DateLastComment;
               if (is_null($DateLastComment) || $DateLastDiscussion > $MaxDate)
                  $MaxDate = $DateLastDiscussion;

               if (is_null($MaxDate)) continue;

               $CategoryID = (int)$Category['CategoryID'];
               $this->SetField($CategoryID, 'LastDateInserted', $MaxDate);
            }
            break;
      }
      self::ClearCache();
      return $Result;
   }

   public static function DefaultCategory() {
      foreach (self::Categories() as $Category) {
         if ($Category['CategoryID'] > 0)
            return $Category;
      }
   }

   /**
    * Remove categories that a user does not have permission to view.
    *
    * @param array $categoryIDs An array of categories to filter.
    * @return array Returns an array of category IDs that are okay to view.
    */
   public static function filterCategoryPermissions($categoryIDs) {
      $permissionCategories = static::GetByPermission('Discussions.View');

      if ($permissionCategories === true) {
         return $categoryIDs;
      } else {
         $permissionCategoryIDs = array_keys($permissionCategories);
         return array_intersect($categoryIDs, $permissionCategoryIDs);
      }
   }

   public static function GetByPermission($Permission = 'Discussions.Add', $CategoryID = NULL, $Filter = array(), $PermFilter = array()) {
      static $Map = array('Discussions.Add' => 'PermsDiscussionsAdd', 'Discussions.View' => 'PermsDiscussionsView');
      $Field = $Map[$Permission];
      $DoHeadings = C('Vanilla.Categories.DoHeadings');
      $PermFilters = array();

      $Result = array();
      $Categories = self::Categories();
      foreach ($Categories as $ID => $Category) {
         if (!$Category[$Field])
            continue;

         if ($CategoryID != $ID) {
            if ($Category['CategoryID'] <= 0)
               continue;

            $Exclude = FALSE;
            foreach ($Filter as $Key => $Value) {
               if (isset($Category[$Key]) && $Category[$Key] != $Value) {
                  $Exclude = TRUE;
                  break;
               }
            }

            if (!empty($PermFilter)) {
               $PermCategory = GetValue($Category['PermissionCategoryID'], $Categories);
               if ($PermCategory) {
                  if (!isset($PermFilters[$PermCategory['CategoryID']])) {
                     $PermFilters[$PermCategory['CategoryID']] = self::Where($PermCategory, $PermFilter);
                  }

                  $Exclude = !$PermFilters[$PermCategory['CategoryID']];
               } else {
                  $Exclude = TRUE;
               }
            }

            if ($Exclude)
               continue;

            if ($DoHeadings && $Category['Depth'] <= 1) {
               if ($Permission == 'Discussions.Add')
                  continue;
               else
                  $Category['PermsDiscussionsAdd'] = FALSE;
            }
         }

         $Result[$ID] = $Category;
      }
      return $Result;
   }

   public static function Where($Row, $Where) {
      if (empty($Where))
         return TRUE;

      foreach ($Where as $Key => $Value) {
         $RowValue = GetValue($Key, $Row);

         // If there are no discussion types set then all discussion types are allowed.
         if ($Key == 'AllowedDiscussionTypes' && empty($RowValue))
            continue;

         if (is_array($RowValue)) {
            if (is_array($Value)) {
               // If both items are arrays then all values in the filter must be in the row.
               if (count(array_intersect($Value, $RowValue)) < count($Value))
                  return FALSE;
            } elseif (!in_array($Value, $RowValue)) {
               return FALSE;
            }
         } elseif (is_array($Value)) {
            if (!in_array($RowValue, $Value))
               return FALSE;
         } else {
            if ($RowValue != $Value)
               return FALSE;
         }
      }

      return TRUE;
   }

   /**
    * Give a user points specific to this category.
    *
    * @param int $UserID The user to give the points to.
    * @param int $Points The number of points to give.
    * @param string $Source The source of the points.
    * @param int $CategoryID The category to give the points for.
    * @param int $Timestamp The time the points were given.
    */
   public static function GivePoints($UserID, $Points, $Source = 'Other', $CategoryID = 0, $Timestamp = FALSE) {
      // Figure out whether or not the category tracks points seperately.
      if ($CategoryID) {
         $Category = self::Categories($CategoryID);
         if ($Category)
            $CategoryID = GetValue('PointsCategoryID', $Category);
         else
            $CategoryID = 0;
      }

      UserModel::GivePoints($UserID, $Points, array($Source, 'CategoryID' => $CategoryID), $Timestamp);
   }

   /**
    *
    *
    * @since 2.0.18
    * @access public
    * @param array $Data Dataset.
    * @param string $Column Name of database column.
    * @param array $Options 'Join' key may contain array of columns to join on.
    */
   public static function JoinCategories(&$Data, $Column = 'CategoryID', $Options = array()) {
      $Join = GetValue('Join', $Options, array('Name' => 'Category', 'PermissionCategoryID', 'UrlCode' => 'CategoryUrlCode'));
      foreach ($Data as &$Row) {
         $ID = GetValue($Column, $Row);
         $Category = self::Categories($ID);
         foreach ($Join as $N => $V) {
            if (is_numeric($N))
               $N = $V;

            if ($Category)
               $Value = $Category[$N];
            else
               $Value = NULL;

            SetValue($V, $Row, $Value);
         }
      }
   }

   public static function JoinRecentPosts(&$Data) {
      $DiscussionIDs = array();
      $CommentIDs = array();
      $Joined = FALSE;

      foreach ($Data as &$Row) {
         if (isset($Row['LastTitle']) && $Row['LastTitle'])
            continue;

         if ($Row['LastDiscussionID'])
            $DiscussionIDs[] = $Row['LastDiscussionID'];

         if ($Row['LastCommentID']) {
            $CommentIDs[] = $Row['LastCommentID'];
         }
         $Joined = TRUE;
      }

      // Create a fresh copy of the Sql object so as not to pollute.
      $Sql = clone Gdn::SQL();
      $Sql->Reset();

      // Grab the discussions.
      if (count($DiscussionIDs) > 0) {
         $Discussions = $Sql->WhereIn('DiscussionID', $DiscussionIDs)->Get('Discussion')->ResultArray();
         $Discussions = Gdn_DataSet::Index($Discussions, array('DiscussionID'));
      }

      if (count($CommentIDs) > 0) {
         $Comments = $Sql->WhereIn('CommentID', $CommentIDs)->Get('Comment')->ResultArray();
         $Comments = Gdn_DataSet::Index($Comments, array('CommentID'));
      }

      foreach ($Data as &$Row) {
         $Discussion = GetValue($Row['LastDiscussionID'], $Discussions);
         $NameUrl = 'x';
         if ($Discussion) {
            $Row['LastTitle'] = Gdn_Format::Text($Discussion['Name']);
            $Row['LastUserID'] = $Discussion['InsertUserID'];
            $Row['LastDiscussionUserID'] = $Discussion['InsertUserID'];
            $Row['LastDateInserted'] = $Discussion['DateInserted'];
            $NameUrl = Gdn_Format::Text($Discussion['Name'], TRUE);
            $Row['LastUrl'] = DiscussionUrl($Discussion, FALSE, '/').'#latest';
         }
         $Comment = GetValue($Row['LastCommentID'], $Comments);
         if ($Comment) {
            $Row['LastUserID'] = $Comment['InsertUserID'];
            $Row['LastDateInserted'] = $Comment['DateInserted'];
         } else {
            $Row['NoComment'] = TRUE;
         }

         TouchValue('LastTitle', $Row, '');
         TouchValue('LastUserID', $Row, NULL);
         TouchValue('LastDiscussionUserID', $Row, NULL);
         TouchValue('LastDateInserted', $Row, NULL);
         TouchValue('LastUrl', $Row, NULL);
      }
      return $Joined;
   }

   public static function JoinRecentChildPosts(&$Category = NULL, &$Categories = NULL) {
      if ($Categories === NULL)
         $Categories =& self::$Categories;

      if ($Category === NULL)
         $Category =& $Categories[-1];

      if (!isset($Category['ChildIDs']))
         return;

      $LastTimestamp = Gdn_Format::ToTimestamp($Category['LastDateInserted']);;
      $LastCategoryID = NULL;

      if ($Category['DisplayAs'] == 'Categories') {
         // This is an overview category so grab it's recent data from it's children.
         foreach ($Category['ChildIDs'] as $CategoryID) {
            if (!isset($Categories[$CategoryID]))
               continue;

            $ChildCategory =& $Categories[$CategoryID];
            if ($ChildCategory['DisplayAs'] == 'Categories') {
               self::JoinRecentChildPosts($ChildCategory, $Categories);
            }
            $Timestamp = Gdn_Format::ToTimestamp($ChildCategory['LastDateInserted']);

            if ($LastTimestamp === FALSE || $LastTimestamp < $Timestamp) {
               $LastTimestamp = $Timestamp;
               $LastCategoryID = $CategoryID;
            }
         }

         if ($LastCategoryID) {
            $LastCategory = $Categories[$LastCategoryID];

            $Category['LastCommentID'] = $LastCategory['LastCommentID'];
            $Category['LastDiscussionID'] = $LastCategory['LastDiscussionID'];
            $Category['LastDateInserted'] = $LastCategory['LastDateInserted'];
            $Category['LastTitle'] = $LastCategory['LastTitle'];
            $Category['LastUserID'] = $LastCategory['LastUserID'];
            $Category['LastDiscussionUserID'] = $LastCategory['LastDiscussionUserID'];
            $Category['LastUrl'] = $LastCategory['LastUrl'];
            $Category['LastCategoryID'] = $LastCategory['CategoryID'];
//            $Category['LastName'] = $LastCategory['LastName'];
//            $Category['LastName'] = $LastCategory['LastName'];
//            $Category['LastEmail'] = $LastCategory['LastEmail'];
//            $Category['LastPhoto'] = $LastCategory['LastPhoto'];
         }
      }
   }

   /**
    * Add UserCategory modifiers
    *
    * Update &$Categories in memory by applying modifiers from UserCategory for
    * the currently logged-in user.
    *
    * @since 2.0.18
    * @access public
    * @param array &$Categories
    * @param bool $AddUserCategory
    */
   public static function JoinUserData(&$Categories, $AddUserCategory = TRUE) {
      $IDs = array_keys($Categories);
      $DoHeadings = C('Vanilla.Categories.DoHeadings');

      if ($AddUserCategory) {
         $SQL = clone Gdn::SQL();
         $SQL->Reset();

         if (Gdn::Session()->UserID) {
            $Key = 'UserCategory_'.Gdn::Session()->UserID;
            $UserData = Gdn::Cache()->Get($Key);
            if ($UserData === Gdn_Cache::CACHEOP_FAILURE) {
               $UserData = $SQL->GetWhere('UserCategory', array('UserID' => Gdn::Session()->UserID))->ResultArray();
               $UserData = Gdn_DataSet::Index($UserData, 'CategoryID');
               Gdn::Cache()->Store($Key, $UserData);
            }
         } else
            $UserData = array();

//         Gdn::Controller()->SetData('UserData', $UserData);

         foreach ($IDs as $ID) {
            $Category = $Categories[$ID];

            $DateMarkedRead = GetValue('DateMarkedRead', $Category);
            $Row = GetValue($ID, $UserData);
            if ($Row) {
               $UserDateMarkedRead = $Row['DateMarkedRead'];

               if (!$DateMarkedRead || ($UserDateMarkedRead && Gdn_Format::ToTimestamp($UserDateMarkedRead) > Gdn_Format::ToTimestamp($DateMarkedRead))) {
                  $Categories[$ID]['DateMarkedRead'] = $UserDateMarkedRead;
                  $DateMarkedRead = $UserDateMarkedRead;
               }

               $Categories[$ID]['Unfollow'] = $Row['Unfollow'];
            } else {
               $Categories[$ID]['Unfollow'] = FALSE;
            }

            // Calculate the following field.
            $Following = !((bool)GetValue('Archived', $Category) || (bool)GetValue('Unfollow', $Row, FALSE));
            $Categories[$ID]['Following'] = $Following;

            // Calculate the read field.
            if ($DoHeadings && $Category['Depth'] <= 1) {
               $Categories[$ID]['Read'] = FALSE;
            } elseif ($DateMarkedRead) {
               if (GetValue('LastDateInserted', $Category))
                  $Categories[$ID]['Read'] = Gdn_Format::ToTimestamp($DateMarkedRead) >= Gdn_Format::ToTimestamp($Category['LastDateInserted']);
               else
                  $Categories[$ID]['Read'] = TRUE;
            } else {
               $Categories[$ID]['Read'] = FALSE;
            }
         }

      }

      // Add permissions.
      $Session = Gdn::Session();
      foreach ($IDs as $CID) {
         $Category = $Categories[$CID];
         $Categories[$CID]['Url'] = Url($Category['Url'], '//');
         if ($Photo = val('Photo', $Category)) {
            $Categories[$CID]['PhotoUrl'] = Gdn_Upload::Url($Photo);
         }

         if ($Category['LastUrl'])
            $Categories[$CID]['LastUrl'] = Url($Category['LastUrl'], '//');
         $Categories[$CID]['PermsDiscussionsView'] = $Session->CheckPermission('Vanilla.Discussions.View', TRUE, 'Category', $Category['PermissionCategoryID']);
         $Categories[$CID]['PermsDiscussionsAdd'] = $Session->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', $Category['PermissionCategoryID']);
         $Categories[$CID]['PermsDiscussionsEdit'] = $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Category['PermissionCategoryID']);
         $Categories[$CID]['PermsCommentsAdd'] = $Session->CheckPermission('Vanilla.Comments.Add', TRUE, 'Category', $Category['PermissionCategoryID']);
      }

      // Translate name and description
      foreach ($IDs as $ID) {
         $Code = $Categories[$ID]['UrlCode'];
         $Categories[$ID]['Name'] = TranslateContent("Categories.".$Code.".Name", $Categories[$ID]['Name']);
         $Categories[$ID]['Description'] = TranslateContent("Categories.".$Code.".Description", $Categories[$ID]['Description']);
      }
   }

   /**
    * Delete a single category and assign its discussions to another.
    *
    * @since 2.0.0
    * @access public
    *
    * @param object $Category
    * @param int $ReplacementCategoryID Unique ID of category all discussion are being move to.
    */
   public function Delete($Category, $ReplacementCategoryID) {
      // Don't do anything if the required category object & properties are not defined.
      if (
         !is_object($Category)
         || !property_exists($Category, 'CategoryID')
         || !property_exists($Category, 'ParentCategoryID')
         || !property_exists($Category, 'AllowDiscussions')
         || !property_exists($Category, 'Name')
         || $Category->CategoryID <= 0
      ) {
         throw new Exception(T('Invalid category for deletion.'));
      } else {
         // Remove permissions related to category
         $PermissionModel = Gdn::PermissionModel();
         $PermissionModel->Delete(NULL, 'Category', 'CategoryID', $Category->CategoryID);

         // If there is a replacement category...
         if ($ReplacementCategoryID > 0) {
            // Update children categories
            $this->SQL
               ->Update('Category')
               ->Set('ParentCategoryID', $ReplacementCategoryID)
               ->Where('ParentCategoryID', $Category->CategoryID)
               ->Put();

            // Update permission categories.
            $this->SQL
               ->Update('Category')
               ->Set('PermissionCategoryID', $ReplacementCategoryID)
               ->Where('PermissionCategoryID', $Category->CategoryID)
               ->Where('CategoryID <>', $Category->CategoryID)
               ->Put();

            // Update discussions
            $this->SQL
               ->Update('Discussion')
               ->Set('CategoryID', $ReplacementCategoryID)
               ->Where('CategoryID', $Category->CategoryID)
               ->Put();

            // Update the discussion count
            $Count = $this->SQL
               ->Select('DiscussionID', 'count', 'DiscussionCount')
               ->From('Discussion')
               ->Where('CategoryID', $ReplacementCategoryID)
               ->Get()
               ->FirstRow()
               ->DiscussionCount;

            if (!is_numeric($Count))
               $Count = 0;

            $this->SQL
               ->Update('Category')->Set('CountDiscussions', $Count)
               ->Where('CategoryID', $ReplacementCategoryID)
               ->Put();

            // Update tags
            $this->SQL
               ->Update('Tag')
               ->Set('CategoryID', $ReplacementCategoryID)
               ->Where('CategoryID', $Category->CategoryID)
               ->Put();

            $this->SQL
               ->Update('TagDiscussion')
               ->Set('CategoryID', $ReplacementCategoryID)
               ->Where('CategoryID', $Category->CategoryID)
               ->Put();
         } else {
            // Delete comments in this category
            $this->SQL
               ->From('Comment c')
               ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
               ->Where('d.CategoryID', $Category->CategoryID)
               ->Delete();

            // Delete discussions in this category
            $this->SQL->Delete('Discussion', array('CategoryID' => $Category->CategoryID));

            // Make inherited permission local permission
            $this->SQL
               ->Update('Category')
               ->Set('PermissionCategoryID', 0)
               ->Where('PermissionCategoryID', $Category->CategoryID)
               ->Where('CategoryID <>', $Category->CategoryID)
               ->Put();

            // Delete tags
            $this->SQL->Delete('Tag', array('CategoryID' => $Category->CategoryID));
            $this->SQL->Delete('TagDiscussion', array('CategoryID' => $Category->CategoryID));
         }

         // Delete the category
         $this->SQL->Delete('Category', array('CategoryID' => $Category->CategoryID));
      }
      // Make sure to reorganize the categories after deletes
      $this->RebuildTree();
   }

   /**
    * Get data for a single category selected by Url Code. Disregards permissions.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $CodeID Unique Url Code of category we're getting data for.
    * @return object SQL results.
    */
   public function GetByCode($Code) {
      return $this->SQL->GetWhere('Category', array('UrlCode' => $Code))->FirstRow();
   }

   /**
    * Get data for a single category selected by ID. Disregards permissions.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $CategoryID Unique ID of category we're getting data for.
    * @return object SQL results.
    */
   public function GetID($CategoryID, $DatasetType = DATASET_TYPE_OBJECT) {
      $Category = $this->SQL->GetWhere('Category', array('CategoryID' => $CategoryID))->FirstRow($DatasetType);
      if (isset($Category->AllowedDiscussionTypes) && is_string($Category->AllowedDiscussionTypes))
            $Category->AllowedDiscussionTypes = unserialize($Category->AllowedDiscussionTypes);

      return $Category;
   }

   /**
    * Get list of categories (respecting user permission).
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $OrderFields Ignored.
    * @param string $OrderDirection Ignored.
    * @param int $Limit Ignored.
    * @param int $Offset Ignored.
    * @return Gdn_DataSet SQL results.
    */
   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->SQL
         ->Select('c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.AllowDiscussions, c.UrlCode')
         ->From('Category c')
         ->BeginWhereGroup()
         ->Permission('Vanilla.Discussions.View', 'c', 'PermissionCategoryID', 'Category')
         ->EndWhereGroup()
         ->OrWhere('AllowDiscussions', '0')
         ->OrderBy('TreeLeft', 'asc');

         // Note: we are using the Nested Set tree model, so TreeLeft is used for sorting.
         // Ref: http://articles.sitepoint.com/article/hierarchical-data-database/2
         // Ref: http://en.wikipedia.org/wiki/Nested_set_model

      $CategoryData = $this->SQL->Get();
      $this->AddCategoryColumns($CategoryData);
      return $CategoryData;
   }

   /**
    * Get list of categories (disregarding user permission for admins).
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $OrderFields Ignored.
    * @param string $OrderDirection Ignored.
    * @param int $Limit Ignored.
    * @param int $Offset Ignored.
    * @return object SQL results.
    */
   public function GetAll() {
      $CategoryData = $this->SQL
         ->Select('c.*')
         ->From('Category c')
         ->OrderBy('TreeLeft', 'asc')
         ->Get();

      $this->AddCategoryColumns($CategoryData);
      return $CategoryData;
   }

   /**
    * Return the number of descendants for a specific category.
    */
   public function GetDescendantCountByCode($Code) {
      $Category = $this->GetByCode($Code);
      if ($Category)
         return round(($Category->TreeRight - $Category->TreeLeft - 1) / 2);

      return 0;
   }

   /**
    * Get all of the ancestor categories above this one.
    * @param int|string $Category The category ID or url code.
    * @param bool $checkPermissions Whether or not to only return the categories with view permission.
    * @param bool $includeHeadings Whether or not to include heading categories.
    * @return array
    */
   public static function GetAncestors($categoryID, $checkPermissions = true, $includeHeadings = false) {
      $Categories = self::Categories();
      $Result = array();

      // Grab the category by ID or url code.
      if (is_numeric($categoryID)) {
         if (isset($Categories[$categoryID]))
            $Category = $Categories[$categoryID];
      } else {
         foreach ($Categories as $ID => $Value) {
            if ($Value['UrlCode'] == $categoryID) {
               $Category = $Categories[$ID];
               break;
            }
         }
      }

      if (!isset($Category))
         return $Result;

      // Build up the ancestor array by tracing back through parents.
      $Result[$Category['CategoryID']] = $Category;
      $Max = 20;
      while (isset($Categories[$Category['ParentCategoryID']])) {
         // Check for an infinite loop.
         if ($Max <= 0)
            break;
         $Max--;

         if ($checkPermissions && !$Category['PermsDiscussionsView']) {
            $Category = $Categories[$Category['ParentCategoryID']];
            continue;
         }

         if ($Category['CategoryID'] == -1)
            break;

         // Return by ID or code.
         if (is_numeric($categoryID))
            $ID = $Category['CategoryID'];
         else
            $ID = $Category['UrlCode'];

         if ($includeHeadings || $Category['DisplayAs'] !== 'Heading') {
            $Result[$ID] = $Category;
         }

         $Category = $Categories[$Category['ParentCategoryID']];
      }
      $Result = array_reverse($Result, true); // order for breadcrumbs
      return $Result;
   }

   /**
    *
    *
    * @since 2.0.18
    * @acces public
    * @param string $Code Where condition.
    * @return object DataSet
    */
   public function GetDescendantsByCode($Code) {
      Deprecated('CategoryModel::GetDescendantsByCode', 'CategoryModel::GetAncestors');

      // SELECT title FROM tree WHERE lft < 4 AND rgt > 5 ORDER BY lft ASC;
      return $this->SQL
         ->Select('c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.CountComments, c.AllowDiscussions, c.UrlCode')
         ->From('Category c')
         ->Join('Category d', 'c.TreeLeft < d.TreeLeft and c.TreeRight > d.TreeRight')
         ->Where('d.UrlCode', $Code)
         ->OrderBy('c.TreeLeft', 'asc')
         ->Get();
   }

   /**
    * Get the subtree starting at a given parent.
    *
    * @param string $parentCategory The ID or url code of the parent category.
    * @param bool $includeParent Whether or not to include the parent in the result.
    * @param bool|int $adjustDepth Whether or not to adjust the depth or a number to adjust the depth by.
    * Passing `true` as this parameter will make the returned subtree look like the full tree which is useful for many
    * views that expect the full category tree.
    * @return array An array of categories.
    */
   public static function GetSubtree($parentCategory, $includeParent = true, $adjustDepth = false) {
      $Result = array();
      $Category = self::Categories($parentCategory);

      // Check to see if the depth should be adjusted.
      // This value is true if called by a dev or a number if called recursively.
      if ($adjustDepth === true) {
         $adjustDepth = -val('Depth', $Category) + ($includeParent ? 1 : 0);
      }

      if ($Category) {
         if ($includeParent) {
            if ($adjustDepth) {
               $Category['Depth'] += $adjustDepth;
            }

            $Result[$Category['CategoryID']] = $Category;
         }
         $ChildIDs = val('ChildIDs', $Category, array());

         foreach ($ChildIDs as $ChildID) {
            $Result = array_replace($Result, self::GetSubtree($ChildID, true, $adjustDepth));
         }
      }
      return $Result;
   }

   public function GetFull($CategoryID = FALSE, $Permissions = FALSE) {

      // Get the current category list
      $Categories = self::Categories();

      // Filter out the categories we aren't supposed to view.
      if ($CategoryID && !is_array($CategoryID))
         $CategoryID = array($CategoryID);

      if (!$CategoryID && $this->Watching) {
         $CategoryID = self::CategoryWatch(FALSE);
      }

      switch ($Permissions) {
         case 'Vanilla.Discussions.Add':
            $Permissions = 'PermsDiscussionsAdd';
            break;
         case 'Vanilla.Disussions.Edit':
            $Permissions = 'PermsDiscussionsEdit';
            break;
         default:
            $Permissions = 'PermsDiscussionsView';
            break;
      }

      $IDs = array_keys($Categories);
      foreach ($IDs as $ID) {
         if ($ID < 0)
            unset($Categories[$ID]);
         elseif (!$Categories[$ID][$Permissions])
            unset($Categories[$ID]);
         elseif (is_array($CategoryID) && !in_array($ID, $CategoryID))
            unset($Categories[$ID]);
      }

      foreach ($Categories as &$Category) {
         if ($Category['ParentCategoryID'] <= 0)
            self::JoinRecentChildPosts($Category, $Categories);
      }

      // This join users call can be very slow on forums with a lot of categories so we can disable it here.
      if ($this->JoinRecentUsers)
         Gdn::UserModel()->JoinUsers($Categories, array('LastUserID'));

      $Result = new Gdn_DataSet($Categories, DATASET_TYPE_ARRAY);
      $Result->DatasetType(DATASET_TYPE_OBJECT);
      return $Result;
   }

   /**
    * Get a list of categories, considering several filters
    *
    * @param array $RestrictIDs Optional list of category ids to mask the dataset
    * @param string $Permissions Optional permission to require. Defaults to Vanilla.Discussions.View.
    * @param array $ExcludeWhere Exclude categories with any of these flags
    * @return \Gdn_DataSet
    */
   public function GetFiltered($RestrictIDs = FALSE, $Permissions = FALSE, $ExcludeWhere = FALSE) {

      // Get the current category list
      $Categories = self::Categories();

      // Filter out the categories we aren't supposed to view.
      if ($RestrictIDs && !is_array($RestrictIDs))
         $RestrictIDs = array($RestrictIDs);
      elseif ($this->Watching)
         $RestrictIDs = self::CategoryWatch();

      switch ($Permissions) {
         case 'Vanilla.Discussions.Add':
            $Permissions = 'PermsDiscussionsAdd';
            break;
         case 'Vanilla.Disussions.Edit':
            $Permissions = 'PermsDiscussionsEdit';
            break;
         default:
            $Permissions = 'PermsDiscussionsView';
            break;
      }

      $IDs = array_keys($Categories);
      foreach ($IDs as $ID) {

         // Exclude the root category
         if ($ID < 0)
            unset($Categories[$ID]);

         // No categories where we don't have permission
         elseif (!$Categories[$ID][$Permissions])
            unset($Categories[$ID]);

         // No categories whose filter fields match the provided filter values
         elseif (is_array($ExcludeWhere)) {
            foreach ($ExcludeWhere as $Filter => $FilterValue)
               if (GetValue($Filter, $Categories[$ID], FALSE) == $FilterValue)
                  unset($Categories[$ID]);
         }

         // No categories that are otherwise filtered out
         elseif (is_array($RestrictIDs) && !in_array($ID, $RestrictIDs))
            unset($Categories[$ID]);
      }

      Gdn::UserModel()->JoinUsers($Categories, array('LastUserID'));

      $Result = new Gdn_DataSet($Categories, DATASET_TYPE_ARRAY);
      $Result->DatasetType(DATASET_TYPE_OBJECT);
      return $Result;
   }

   /**
    * Get full data for a single category by its URL slug. Respects permissions.
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $UrlCode Unique category slug from URL.
    * @return object SQL results.
    */
   public function GetFullByUrlCode($UrlCode) {
      $Data = (object)self::Categories($UrlCode);

      // Check to see if the user has permission for this category.
      // Get the category IDs.
      $CategoryIDs = DiscussionModel::CategoryPermissions();
      if (is_array($CategoryIDs) && !in_array(GetValue('CategoryID', $Data), $CategoryIDs))
         $Data = FALSE;
      return $Data;
   }

   /**
    * A simplified version of GetWhere that polls the cache instead of the database.
    * @param array $Where
    * @return array
    * @since 2.2.2
    */
   public function GetWhereCache($Where) {
      $Result = array();

      foreach (self::Categories() as $Index => $Row) {
         $Match = true;
         foreach ($Where as $Column => $Value) {
            $RowValue = GetValue($Column, $Row, NULL);

            if ($RowValue != $Value && !(is_array($Value) && in_array($RowValue, $Value))) {
               $Match = false;
               break;
            }
         }
         if ($Match)
            $Result[$Index] = $Row;
      }

      return $Result;
   }

   /**
    * Check whether category has any children categories.
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $CategoryID Unique ID for category being checked.
    * @return bool
    */
   public function HasChildren($CategoryID) {
      $ChildData = $this->SQL
         ->Select('CategoryID')
         ->From('Category')
         ->Where('ParentCategoryID', $CategoryID)
         ->Get();
      return $ChildData->NumRows() > 0 ? TRUE : FALSE;
   }

   /**
    *
    *
    * @since 2.0.0
    * @access public
    * @param array $Data
    * @param string $Permission
    * @param string $Column
    */
   public static function JoinModerators($Data, $Permission = 'Vanilla.Comments.Edit', $Column = 'Moderators') {
      $Moderators = Gdn::SQL()
         ->Select('u.UserID, u.Name, u.Photo, u.Email')
         ->Select('p.JunctionID as CategoryID')
         ->From('User u')
         ->Join('UserRole ur', 'ur.UserID = u.UserID')
         ->Join('Permission p', 'ur.RoleID = p.RoleID')
         ->Where('`'.$Permission.'`', 1)
         ->Get()->ResultArray();

      $Moderators = Gdn_DataSet::Index($Moderators, 'CategoryID', array('Unique' => FALSE));

      foreach ($Data as &$Category) {
         $ID = GetValue('PermissionCategoryID', $Category);
         $Mods = GetValue($ID, $Moderators, array());
         $ModIDs = array();
         $UniqueMods = array();
         foreach ($Mods as $Mod) {
            if (!in_array($Mod['UserID'], $ModIDs)) {
               $ModIDs[] = $Mod['UserID'];
               $UniqueMods[] = $Mod;
            }

         }
         SetValue($Column, $Category, $UniqueMods);
      }
   }

   public static function MakeTree($Categories, $Root = NULL) {
      $Result = array();

      $Categories = (array)$Categories;

      if ($Root) {
         $Root = (array)$Root;
         // Make the tree out of this category as a subtree.
         $DepthAdjust = -$Root['Depth'];
         $Result = self::_MakeTreeChildren($Root, $Categories, $DepthAdjust);
      } else {
         // Make a tree out of all categories.
         foreach ($Categories as $Category) {
            if (isset($Category['Depth']) && $Category['Depth'] == 1) {
               $Row = $Category;
               $Row['Children'] = self::_MakeTreeChildren($Row, $Categories);
               $Result[] = $Row;
            }
         }
      }
      return $Result;
   }

   protected static function _MakeTreeChildren($Category, $Categories, $DepthAdj = null) {
      if (is_null($DepthAdj)) {
         $DepthAdj = -val('Depth', $Category);
      }

      $Result = array();
      foreach ($Category['ChildIDs'] as $ID) {
         if (!isset($Categories[$ID]))
            continue;
         $Row = $Categories[$ID];
         $Row['Depth'] += $DepthAdj;
         $Row['Children'] = self::_MakeTreeChildren($Row, $Categories);
         $Result[] = $Row;
      }
      return $Result;
   }

   /**
    * Return the category that contains the permissions for the given category.
    *
    * @param mixed $Category
    * @since 2.2
    */
   public static function PermissionCategory($Category) {
      if (empty($Category))
         return self::Categories(-1);

      if (!is_array($Category) && !is_object($Category)) {
         $Category = self::Categories($Category);
      }

      return self::Categories(GetValue('PermissionCategoryID', $Category));
   }

   /**
    * Rebuilds the category tree. We are using the Nested Set tree model.
    *
    * @param bool $BySort Rebuild the tree by sort order instead of existing tree order.
    *
    * @since 2.0.0
    * @ref http://articles.sitepoint.com/article/hierarchical-data-database/2
    * @ref http://en.wikipedia.org/wiki/Nested_set_model
    */
   public function RebuildTree($BySort = false) {
      // Grab all of the categories.
      if ($BySort) {
         $Order = 'Sort, Name';
      } else {
         $Order = 'TreeLeft, Sort, Name';
      }

      $Categories = $this->SQL->Get('Category', $Order);
      $Categories = Gdn_DataSet::Index($Categories->ResultArray(), 'CategoryID');

      // Make sure the tree has a root.
      if (!isset($Categories[-1])) {
         $RootCat = array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'Root', 'UrlCode' => '', 'Description' => 'Root of category tree. Users should never see this.', 'PermissionCategoryID' => -1, 'Sort' => 0, 'ParentCategoryID' => NULL);
         $Categories[-1] = $RootCat;
         $this->SQL->Insert('Category', $RootCat);
      }

      // Build a tree structure out of the categories.
      $Root = NULL;
      foreach ($Categories as &$Cat) {
         if (!isset($Cat['CategoryID']))
            continue;

         // Backup category settings for efficient database saving.
         try {
            $Cat['_TreeLeft'] = $Cat['TreeLeft'];
            $Cat['_TreeRight'] = $Cat['TreeRight'];
            $Cat['_Depth'] = $Cat['Depth'];
            $Cat['_PermissionCategoryID'] = $Cat['PermissionCategoryID'];
            $Cat['_ParentCategoryID'] = $Cat['ParentCategoryID'];
         } catch (Exception $Ex) {
         }

         if ($Cat['CategoryID'] == -1) {
            $Root =& $Cat;
            continue;
         }

         $ParentID = $Cat['ParentCategoryID'];
         if (!$ParentID) {
            $ParentID = -1;
            $Cat['ParentCategoryID'] = $ParentID;
         }
         if (!isset($Categories[$ParentID]['Children']))
            $Categories[$ParentID]['Children'] = array();
         $Categories[$ParentID]['Children'][] =& $Cat;
      }
      unset($Cat);

      // Set the tree attributes of the tree.
      $this->_SetTree($Root);
      unset($Root);

      // Save the tree structure.
      foreach ($Categories as $Cat) {
         if (!isset($Cat['CategoryID']))
            continue;
         if ($Cat['_TreeLeft'] != $Cat['TreeLeft'] || $Cat['_TreeRight'] != $Cat['TreeRight'] || $Cat['_Depth'] != $Cat['Depth'] || $Cat['PermissionCategoryID'] != $Cat['PermissionCategoryID'] || $Cat['_ParentCategoryID'] != $Cat['ParentCategoryID'] || $Cat['Sort'] != $Cat['TreeLeft']) {
            $this->SQL->Put('Category',
               array('TreeLeft' => $Cat['TreeLeft'], 'TreeRight' => $Cat['TreeRight'], 'Depth' => $Cat['Depth'], 'PermissionCategoryID' => $Cat['PermissionCategoryID'], 'ParentCategoryID' => $Cat['ParentCategoryID'], 'Sort' => $Cat['TreeLeft']),
               array('CategoryID' => $Cat['CategoryID']));
         }
      }
      $this->SetCache();
   }

   /**
    *
    *
    * @since 2.0.18
    * @access protected
    * @param array $Node
    * @param int $Left
    * @param int $Depth
    */
   protected function _SetTree(&$Node, $Left = 1, $Depth = 0) {
      $Right = $Left + 1;

      if (isset($Node['Children'])) {
         foreach ($Node['Children'] as &$Child) {
            $Right = $this->_SetTree($Child, $Right, $Depth + 1);
            $Child['ParentCategoryID'] = $Node['CategoryID'];
            if ($Child['PermissionCategoryID'] != $Child['CategoryID']) {
               $Child['PermissionCategoryID'] = GetValue('PermissionCategoryID', $Node, $Child['CategoryID']);
            }
         }
         unset($Node['Children']);
      }

      $Node['TreeLeft'] = $Left;
      $Node['TreeRight'] = $Right;
      $Node['Depth'] = $Depth;

      return $Right + 1;
   }

   /**
    * Saves the category tree based on a provided tree array. We are using the
    * Nested Set tree model.
    *
    * @ref http://articles.sitepoint.com/article/hierarchical-data-database/2
    * @ref http://en.wikipedia.org/wiki/Nested_set_model
    *
    * @since 2.0.16
    * @access public
    *
    * @param array $TreeArray A fully defined nested set model of the category tree.
    */
   public function SaveTree($TreeArray) {
      /*
        TreeArray comes in the format:
      '0' ...
        'item_id' => "root"
        'parent_id' => "none"
        'depth' => "0"
        'left' => "1"
        'right' => "34"
      '1' ...
        'item_id' => "1"
        'parent_id' => "root"
        'depth' => "1"
        'left' => "2"
        'right' => "3"
      etc...
      */

      // Grab all of the categories so that permissions can be properly saved.
      $PermTree = $this->SQL->Select('CategoryID, PermissionCategoryID, TreeLeft, TreeRight, Depth, Sort, ParentCategoryID')->From('Category')->Get();
      $PermTree = $PermTree->Index($PermTree->ResultArray(), 'CategoryID');

      // The tree must be walked in order for the permissions to save properly.
      usort($TreeArray, array('CategoryModel', '_TreeSort'));
      $Saves = array();

      foreach($TreeArray as $I => $Node) {
         $CategoryID = GetValue('item_id', $Node);
         if ($CategoryID == 'root')
            $CategoryID = -1;

         $ParentCategoryID = GetValue('parent_id', $Node);
         if (in_array($ParentCategoryID, array('root', 'none')))
            $ParentCategoryID = -1;

         $PermissionCategoryID = GetValueR("$CategoryID.PermissionCategoryID", $PermTree, 0);
         $PermCatChanged = FALSE;
         if ($PermissionCategoryID != $CategoryID) {
            // This category does not have custom permissions so must inherit its parent's permissions.
            $PermissionCategoryID = GetValueR("$ParentCategoryID.PermissionCategoryID", $PermTree, 0);
            if ($CategoryID != -1 && !GetValueR("$ParentCategoryID.Touched", $PermTree)) {
               throw new Exception("Category $ParentCategoryID not touched before touching $CategoryID.");
            }
            if ($PermTree[$CategoryID]['PermissionCategoryID'] != $PermissionCategoryID)
               $PermCatChanged = TRUE;
            $PermTree[$CategoryID]['PermissionCategoryID'] = $PermissionCategoryID;
         }
         $PermTree[$CategoryID]['Touched'] = TRUE;

         // Only update if the tree doesn't match the database.
         $Row = $PermTree[$CategoryID];
         if ($Node['left'] != $Row['TreeLeft'] || $Node['right'] != $Row['TreeRight'] || $Node['depth'] != $Row['Depth'] || $ParentCategoryID != $Row['ParentCategoryID'] || $Node['left'] != $Row['Sort'] || $PermCatChanged) {
            $Set = array(
                  'TreeLeft' => $Node['left'],
                  'TreeRight' => $Node['right'],
                  'Depth' => $Node['depth'],
                  'Sort' => $Node['left'],
                  'ParentCategoryID' => $ParentCategoryID,
                  'PermissionCategoryID' => $PermissionCategoryID
               );

            $this->SQL->Update(
               'Category',
               $Set,
               array('CategoryID' => $CategoryID)
            )->Put();

            $Saves[] = array_merge(array('CategoryID' => $CategoryID), $Set);
         }
      }
      self::ClearCache();
      return $Saves;
   }

   /**
    * Utility method for sorting via usort.
    *
    * @since 2.0.18
    * @access protected
    * @param $A First element to compare.
    * @param $B Second element to compare.
    * @return int -1, 1, 0 (per usort)
    */
   protected function _TreeSort($A, $B) {
      if ($A['left'] > $B['left'])
         return 1;
      elseif ($A['left'] < $B['left'])
         return -1;
      else
         return 0;
   }

   /**
    * Saves the category.
    *
    * @since 2.0.0
    * @access public
    *
    * @param array $FormPostValue The values being posted back from the form.
    * @return int ID of the saved category.
    */
   public function Save($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Get data from form
      $CategoryID = ArrayValue('CategoryID', $FormPostValues);
      $NewName = ArrayValue('Name', $FormPostValues, '');
      $UrlCode = ArrayValue('UrlCode', $FormPostValues, '');
      $AllowDiscussions = ArrayValue('AllowDiscussions', $FormPostValues, '');
      $CustomPermissions = (bool)GetValue('CustomPermissions', $FormPostValues);
      $CustomPoints = GetValue('CustomPoints', $FormPostValues, NULL);

      if (isset($FormPostValues['AllowedDiscussionTypes']) && is_array($FormPostValues['AllowedDiscussionTypes'])) {
         $FormPostValues['AllowedDiscussionTypes'] = serialize($FormPostValues['AllowedDiscussionTypes']);
      }

      // Is this a new category?
      $Insert = $CategoryID > 0 ? FALSE : TRUE;
      if ($Insert)
         $this->AddInsertFields($FormPostValues);

      $this->AddUpdateFields($FormPostValues);
      $this->Validation->ApplyRule('UrlCode', 'Required');
      $this->Validation->ApplyRule('UrlCode', 'UrlStringRelaxed');

      // Make sure that the UrlCode is unique among categories.
      $this->SQL->Select('CategoryID')
         ->From('Category')
         ->Where('UrlCode', $UrlCode);

      if ($CategoryID)
         $this->SQL->Where('CategoryID <>', $CategoryID);

      if ($this->SQL->Get()->NumRows())
         $this->Validation->AddValidationResult('UrlCode', 'The specified url code is already in use by another category.');

		//	Prep and fire event.
		$this->EventArguments['FormPostValues'] = &$FormPostValues;
		$this->EventArguments['CategoryID'] = $CategoryID;
		$this->FireEvent('BeforeSaveCategory');

      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         $Fields = $this->Validation->SchemaValidationFields();
         $Fields = RemoveKeyFromArray($Fields, 'CategoryID');
         $AllowDiscussions = ArrayValue('AllowDiscussions', $Fields) == '1' ? TRUE : FALSE;
         $Fields['AllowDiscussions'] = $AllowDiscussions ? '1' : '0';

         if ($Insert === FALSE) {
            $OldCategory = $this->GetID($CategoryID, DATASET_TYPE_ARRAY);
            if (NULL === GetValue('AllowDiscussions', $FormPostValues, NULL))
               $AllowDiscussions = $OldCategory['AllowDiscussions']; // Force the allowdiscussions property
            $Fields['AllowDiscussions'] = $AllowDiscussions ? '1' : '0';

            // Figure out custom points.
            if ($CustomPoints !== NULL) {
               if ($CustomPoints) {
                  $Fields['PointsCategoryID'] = $CategoryID;
               } else {
                  $Parent = self::Categories(GetValue('ParentCategoryID', $Fields, $OldCategory['ParentCategoryID']));
                  $Fields['PointsCategoryID'] = GetValue('PointsCategoryID', $Parent, 0);
               }
            }

            $this->Update($Fields, array('CategoryID' => $CategoryID));

            // Check for a change in the parent category.
            if (isset($Fields['ParentCategoryID']) && $OldCategory['ParentCategoryID'] != $Fields['ParentCategoryID']) {
               $this->RebuildTree();
            } else {
               $this->SetCache($CategoryID, $Fields);
            }
         } else {
            $CategoryID = $this->Insert($Fields);

            if ($CategoryID) {
               if ($CustomPermissions) {
                  $this->SQL->Put('Category', array('PermissionCategoryID' => $CategoryID), array('CategoryID' => $CategoryID));
               }
               if ($CustomPoints) {
                  $this->SQL->Put('Category', array('PointsCategoryID' => $CategoryID), array('CategoryID' => $CategoryID));
               }
            }

            $this->RebuildTree(); // Safeguard to make sure that treeleft and treeright cols are added
         }

         // Save the permissions
         if ($CategoryID) {
            // Check to see if this category uses custom permissions.
            if ($CustomPermissions) {
               $PermissionModel = Gdn::PermissionModel();
               $Permissions = $PermissionModel->PivotPermissions(GetValue('Permission', $FormPostValues, array()), array('JunctionID' => $CategoryID));
               $PermissionModel->SaveAll($Permissions, array('JunctionID' => $CategoryID, 'JunctionTable' => 'Category'));

               if (!$Insert) {
                  // Figure out my last permission and tree info.
                  $Data = $this->SQL->Select('PermissionCategoryID, TreeLeft, TreeRight')->From('Category')->Where('CategoryID', $CategoryID)->Get()->FirstRow(DATASET_TYPE_ARRAY);

                  // Update this category's permission.
                  $this->SQL->Put('Category', array('PermissionCategoryID' => $CategoryID), array('CategoryID' => $CategoryID));

                  // Update all of my children that shared my last category permission.
                  $this->SQL->Put('Category',
                     array('PermissionCategoryID' => $CategoryID),
                     array('TreeLeft >' => $Data['TreeLeft'], 'TreeRight <' => $Data['TreeRight'], 'PermissionCategoryID' => $Data['PermissionCategoryID']));

                  self::ClearCache();
               }
            } elseif (!$Insert) {
               // Figure out my parent's permission.
               $NewPermissionID = $this->SQL
                  ->Select('p.PermissionCategoryID')
                  ->From('Category c')
                  ->Join('Category p', 'c.ParentCategoryID = p.CategoryID')
                  ->Where('c.CategoryID', $CategoryID)
                  ->Get()->Value('PermissionCategoryID', 0);

               if ($NewPermissionID != $CategoryID) {
                  // Update all of my children that shared my last permission.
                  $this->SQL->Put('Category',
                     array('PermissionCategoryID' => $NewPermissionID),
                     array('PermissionCategoryID' => $CategoryID));

                  self::ClearCache();
               }

               // Delete my custom permissions.
               $this->SQL->Delete('Permission',
                  array('JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => $CategoryID));
            }
         }

         // Force the user permissions to refresh.
         Gdn::UserModel()->ClearPermissions();

         // $this->RebuildTree();
      } else {
         $CategoryID = FALSE;
      }

      return $CategoryID;
   }

   /**
    * Grab the Category IDs of the tree.
    *
    * @since 2.0.18
    * @access public
    * @param int $CategoryID
    * @param mixed $Set
    */
   public function SaveUserTree($CategoryID, $Set) {
      $Categories = $this->GetSubtree($CategoryID);
      foreach ($Categories as $Category) {
         $this->SQL->Replace(
            'UserCategory',
            $Set,
            array('UserID' => Gdn::Session()->UserID, 'CategoryID' => $Category['CategoryID']));
      }
      $Key = 'UserCategory_'.Gdn::Session()->UserID;
      Gdn::Cache()->Remove($Key);
   }

   /**
    * Grab and update the category cache
    *
    * @since 2.0.18
    * @access public
    * @param int $ID
    * @param array $Data
    */
   public static function SetCache($ID = FALSE, $Data = FALSE) {
      $Categories = Gdn::Cache()->Get(self::CACHE_KEY);
      self::$Categories = NULL;

      if (!$Categories)
         return;

      if (!$ID || !is_array($Categories)) {
         Gdn::Cache()->Remove(self::CACHE_KEY);
         return;
      }

      if (!array_key_exists($ID, $Categories)) {
         Gdn::Cache()->Remove(self::CACHE_KEY);
         return;
      }

      $Category = $Categories[$ID];
      $Category = array_merge($Category, $Data);
      $Categories[$ID] = $Category;

      self::$Categories = $Categories;
      unset($Categories);
      self::BuildCache();
      self::JoinUserData(self::$Categories, TRUE);
   }

   public function SetField($ID, $Property, $Value = FALSE) {
      if (!is_array($Property))
         $Property = array($Property => $Value);

      if (isset($Property['AllowedDiscussionTypes']) && is_array($Property['AllowedDiscussionTypes'])) {
         $Property['AllowedDiscussionTypes'] = serialize($Property['AllowedDiscussionTypes']);
      }

      $this->SQL->Put($this->Name, $Property, array('CategoryID' => $ID));

      // Set the cache.
      self::SetCache($ID, $Property);

		return $Property;
   }

   public static function SetLocalField($ID, $Property, $Value) {
      // Make sure the field is here.
      if (!self::$Categories === null)
         self::Categories(-1);

      if (isset(self::$Categories[$ID])) {
         self::$Categories[$ID][$Property] = $Value;
         return TRUE;
      }
      return FALSE;
   }

   public function SetRecentPost($CategoryID) {
      $Row = $this->SQL->GetWhere('Discussion', array('CategoryID' => $CategoryID), 'DateLastComment', 'desc', 1)->FirstRow(DATASET_TYPE_ARRAY);

      $Fields = array('LastCommentID' => NULL, 'LastDiscussionID' => NULL);

      if ($Row) {
         $Fields['LastCommentID'] = $Row['LastCommentID'];
         $Fields['LastDiscussionID'] = $Row['DiscussionID'];
      }
      $this->SetField($CategoryID, $Fields);
      $this->SetCache($CategoryID, array('LastTitle' => NULL, 'LastUserID' => NULL, 'LastDateInserted' => NULL, 'LastUrl' => NULL));
   }

   /**
    * If looking at the root node, make sure it exists and that the
    * nested set columns exist in the table.
    *
    * @since 2.0.15
    * @access public
    */
   public function ApplyUpdates() {
      if (!C('Vanilla.NestedCategoriesUpdate')) {
         // Add new columns
         $Construct = Gdn::Database()->Structure();
         $Construct->Table('Category')
            ->Column('TreeLeft', 'int', TRUE)
            ->Column('TreeRight', 'int', TRUE)
            ->Column('Depth', 'int', TRUE)
            ->Column('CountComments', 'int', '0')
            ->Column('LastCommentID', 'int', TRUE)
            ->Set(0, 0);

         // Insert the root node
         if ($this->SQL->GetWhere('Category', array('CategoryID' => -1))->NumRows() == 0)
            $this->SQL->Insert('Category', array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => T('Root Category Name', 'Root'), 'UrlCode' => '', 'Description' => T('Root Category Description', 'Root of category tree. Users should never see this.')));

         // Build up the TreeLeft & TreeRight values.
         $this->RebuildTree();

         SaveToConfig('Vanilla.NestedCategoriesUpdate', 1);
      }
   }

	/**
    * Modifies category data before it is returned.
    *
    * Adds CountAllDiscussions column to each category representing the sum of
    * discussions within this category as well as all subcategories.
    *
    * @since 2.0.17
    * @access public
    *
    * @param object $Data SQL result.
    */
	public static function AddCategoryColumns($Data) {
		$Result = &$Data->Result();
      $Result2 = $Result;
		foreach ($Result as &$Category) {
         if (!property_exists($Category, 'CountAllDiscussions'))
            $Category->CountAllDiscussions = $Category->CountDiscussions;

         if (!property_exists($Category, 'CountAllComments'))
            $Category->CountAllComments = $Category->CountComments;

         // Calculate the following field.
         $Following = !((bool)GetValue('Archived', $Category) || (bool)GetValue('Unfollow', $Category));
         $Category->Following = $Following;

         $DateMarkedRead = GetValue('DateMarkedRead', $Category);
         $UserDateMarkedRead = GetValue('UserDateMarkedRead', $Category);

         if (!$DateMarkedRead)
            $DateMarkedRead = $UserDateMarkedRead;
         elseif ($UserDateMarkedRead && Gdn_Format::ToTimestamp($UserDateMarkedRead) > Gdn_Format::ToTimeStamp($DateMarkedRead))
            $DateMarkedRead = $UserDateMarkedRead;

         // Set appropriate Last* columns.
         SetValue('LastTitle', $Category, GetValue('LastDiscussionTitle', $Category, NULL));
         $LastDateInserted = GetValue('LastDateInserted', $Category, NULL);

         if (GetValue('LastCommentUserID', $Category) == NULL) {
            SetValue('LastCommentUserID', $Category, GetValue('LastDiscussionUserID', $Category, NULL));
            SetValue('DateLastComment', $Category, GetValue('DateLastDiscussion', $Category, NULL));
            SetValue('LastUserID', $Category, GetValue('LastDiscussionUserID', $Category, NULL));

            $LastDiscussion = ArrayTranslate($Category, array(
                'LastDiscussionID' => 'DiscussionID',
                'CategoryID' => 'CategoryID',
                'LastTitle' => 'Name'));

            SetValue('LastUrl', $Category, DiscussionUrl($LastDiscussion, FALSE, '/').'#latest');

            if (is_null($LastDateInserted))
               SetValue('LastDateInserted', $Category, GetValue('DateLastDiscussion', $Category, NULL));
         } else {
            $LastDiscussion = ArrayTranslate($Category, array(
               'LastDiscussionID' => 'DiscussionID',
               'CategoryID' => 'CategoryID',
               'LastTitle' => 'Name'
            ));

            SetValue('LastUserID', $Category, GetValue('LastCommentUserID', $Category, NULL));
            SetValue('LastUrl', $Category, DiscussionUrl($LastDiscussion, FALSE, '/').'#latest');

            if (is_null($LastDateInserted))
               SetValue('LastDateInserted', $Category, GetValue('DateLastComment', $Category, NULL));
         }

         $LastDateInserted = GetValue('LastDateInserted', $Category, NULL);
         if ($DateMarkedRead) {
            if ($LastDateInserted)
               $Category->Read = Gdn_Format::ToTimestamp($DateMarkedRead) >= Gdn_Format::ToTimestamp($LastDateInserted);
            else
               $Category->Read = TRUE;
         } else {
            $Category->Read = FALSE;
         }

         foreach ($Result2 as $Category2) {
            if ($Category2->TreeLeft > $Category->TreeLeft && $Category2->TreeRight < $Category->TreeRight) {
               $Category->CountAllDiscussions += $Category2->CountDiscussions;
               $Category->CountAllComments += $Category2->CountComments;
            }
         }
		}
	}

   public static function CategoryUrl($Category, $Page = '', $WithDomain = TRUE) {
      if (function_exists('CategoryUrl')) return CategoryUrl($Category, $Page, $WithDomain);

      if (is_string($Category))
         $Category = CategoryModel::Categories($Category);
      $Category = (array)$Category;

      $Result = '/categories/'.rawurlencode($Category['UrlCode']);
      if ($Page && $Page > 1) {
            $Result .= '/p'.$Page;
      }
      return Url($Result, $WithDomain);
   }

}
