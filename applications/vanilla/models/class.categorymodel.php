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
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Category');
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
         } else {
            // Delete comments in this category
            $this->SQL->From('Comment')
               ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
               ->Delete('Comment c', array('d.CategoryID' => $Category->CategoryID));
               
            // Delete discussions in this category
            $this->SQL->Delete('Discussion', array('CategoryID' => $Category->CategoryID));

            // Make inherited permission local permission
            $this->SQL
               ->Update('Category')
               ->Set('PermissionCategoryID', 0)
               ->Where('PermissionCategoryID', $Category->CategoryID)
               ->Where('CategoryID <>', $Category->CategoryID)
               ->Put();
         }
         
         // Delete the category
         $this->SQL->Delete('Category', array('CategoryID' => $Category->CategoryID));
         
         // If there is only one category, make sure that Categories are not used
         $CountCategories = $this->Get()->NumRows();
         SaveToConfig('Vanilla.Categories.Use', $CountCategories > 2);
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
   public function GetID($CategoryID) {
      return $this->SQL->GetWhere('Category', array('CategoryID' => $CategoryID))->FirstRow();
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
         ->Select('c.ParentCategoryID, c.CategoryID, c.TreeLeft, c.TreeRight, c.Depth, c.Name, c.Description, c.CountDiscussions, c.CountComments, c.AllowDiscussions, c.UrlCode, c.PermissionCategoryID')
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
   
   public function GetDescendantsByCode($Code) {
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
    * Get full data for a single category or all categories. Respects Permissions.
    *
    * If no CategoryID is provided, it gets all categories.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $CategoryID Unique ID of category to return.
    * @param string $Permissions Permission to check.
    * @return object SQL results.
    */
   public function GetFull($CategoryID = '', $Permissions = FALSE) {
      // Minimally check for view discussion permission
      if (!$Permissions)
         $Permissions = 'Vanilla.Discussions.View';

      // Get the category IDs.
      if ($Permissions == 'Vanilla.Discussions.View') {
         $CategoryIDs = DiscussionModel::CategoryPermissions();
         if ($CategoryIDs !== TRUE)
            $this->SQL->WhereIn('c.CategoryID', $CategoryIDs);
      } else {
         $this->SQL->Permission($Permissions, 'c', 'PermissionCategoryID', 'Category');
      }

      // Build base query
      $this->SQL
         ->Select('c.Name, c.CategoryID, c.TreeRight, c.TreeLeft, c.Depth, c.Description, c.CountDiscussions, c.CountComments, c.UrlCode, c.LastCommentID')
         ->Select('co.DateInserted', '', 'DateLastComment')
         ->Select('co.InsertUserID', '', 'LastCommentUserID')
         ->Select('cu.Name', '', 'LastCommentName')
         ->Select('cu.Photo', '', 'LastCommentPhoto')
         ->Select('co.DiscussionID', '', 'LastDiscussionID')
         ->Select('d.Name', '', 'LastDiscussionName')
         ->From('Category c')
         ->Join('Comment co', 'c.LastCommentID = co.CommentID', 'left')
         ->Join('User cu', 'co.InsertUserID = cu.UserID', 'left')
         ->Join('Discussion d', 'd.DiscussionID = co.DiscussionID', 'left')
         ->Where('c.AllowDiscussions', '1');

      // Single record or full list?
      if (is_numeric($CategoryID) && $CategoryID > 0) {
         return $this->SQL->Where('c.CategoryID', $CategoryID)->Get()->FirstRow();
      } else {
         $CategoryData = $this->SQL->OrderBy('TreeLeft', 'asc')->Get();
         $this->AddCategoryColumns($CategoryData);
         return $CategoryData;
      }
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
      $this->SQL
         ->Select('c.*')
         ->From('Category c')
         ->Where('c.UrlCode', $UrlCode)
         ->Where('c.CategoryID >', 0);
         
      $Data = $this->SQL
         ->Get()
         ->FirstRow();

      // Check to see if the user has permission for this category.
      // Get the category IDs.
      $CategoryIDs = DiscussionModel::CategoryPermissions();
      if (is_array($CategoryIDs) && !in_array(GetValue('CategoryID', $Data), $CategoryIDs))
         $Data = FALSE;
      return $Data;
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
    * Rebuilds the category tree. We are using the Nested Set tree model.
    * 
    * @ref http://articles.sitepoint.com/article/hierarchical-data-database/2
    * @ref http://en.wikipedia.org/wiki/Nested_set_model
    *  
    * @since 2.0.0
    * @access public
    */
   public function RebuildTree() {
      // Grab all of the categories.
      $Categories = $this->SQL->Get('Category', 'TreeLeft, Sort, Name');
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
         // Backup category settings for efficient database saving.
         try {
            $Cat['_TreeLeft'] = $Cat['TreeLeft'];
            $Cat['_TreeRight'] = $Cat['TreeRight'];
            $Cat['_Depth'] = $Cat['Depth'];
            $Cat['_PermissionCategoryID'] = $Cat['PermissionCategoryID'];
            $Cat['_ParentCategoryID'] = $Cat['ParentCategoryID'];
         } catch (Exception $Ex) {
            $Foo = 'Bar';
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

      // Save the tree structure.
      foreach ($Categories as $Cat) {
         if ($Cat['_TreeLeft'] != $Cat['TreeLeft'] || $Cat['_TreeRight'] != $Cat['TreeRight'] || $Cat['_Depth'] != $Cat['Depth'] || $Cat['PermissionCategoryID'] != $Cat['PermissionCategoryID'] || $Cat['_ParentCategoryID'] != $Cat['ParentCategoryID'] || $Cat['Sort'] != $Cat['TreeLeft']) {
            $this->SQL->Put('Category',
               array('TreeLeft' => $Cat['TreeLeft'], 'TreeRight' => $Cat['TreeRight'], 'Depth' => $Cat['Depth'], 'PermissionCategoryID' => $Cat['PermissionCategoryID'], 'ParentCategoryID' => $Cat['ParentCategoryID'], 'Sort' => $Cat['TreeLeft']),
               array('CategoryID' => $Cat['CategoryID']));
         }
      }
   }

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
      
      foreach($TreeArray as $I => $Node) {
         $CategoryID = GetValue('item_id', $Node);
         if ($CategoryID == 'root')
            $CategoryID = -1;
            
         $ParentCategoryID = GetValue('parent_id', $Node);
         if ($ParentCategoryID == 'root')
            $ParentCategoryID = -1;
         else if ($ParentCategoryID == 'none')
         	$ParentCategoryID = null;

         $PermissionCategoryID = GetValueR("$CategoryID.PermissionCategoryID", $PermTree, 0);
         $PermCatChanged = FALSE;
         if ($PermissionCategoryID != $CategoryID) {
            // This category does not have custom permissions so must inherit its parent's permissions.
            $PermissionCategoryID = GetValueR("$ParentCategoryID.PermissionCategoryID", $PermTree, 0);
            if ($CategoryID != -1 && !GetValueR("$ParentCategoryID.Touched", $PermTree)) {
               $Foo = 'Bar';
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
            
            $this->SQL->Update(
               'Category',
               array(
                  'TreeLeft' => $Node['left'],
                  'TreeRight' => $Node['right'],
                  'Depth' => $Node['depth'],
                  'Sort' => $Node['left'],
                  'ParentCategoryID' => $ParentCategoryID,
                  'PermissionCategoryID' => $PermissionCategoryID
               ),
               array('CategoryID' => $CategoryID)
            )->Put();
         }
      }
   }

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
      
      // Is this a new category?
      $Insert = $CategoryID > 0 ? FALSE : TRUE;
      if ($Insert)
         $this->AddInsertFields($FormPostValues);               

      $this->AddUpdateFields($FormPostValues);
      if ($AllowDiscussions == '1') {
         $this->Validation->ApplyRule('UrlCode', 'Required');
         $this->Validation->ApplyRule('UrlCode', 'UrlString', 'Url code can only contain letters, numbers, underscores and dashes.');
      
         // Make sure that the UrlCode is unique among categories.
         $this->SQL->Select('CategoryID')
            ->From('Category')
            ->Where('UrlCode', $UrlCode);
         
         if ($CategoryID)
            $this->SQL->Where('CategoryID <>', $CategoryID);
         
         if ($this->SQL->Get()->NumRows())
            $this->Validation->AddValidationResult('UrlCode', 'The specified url code is already in use by another category.');
            
      }
      
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         $Fields = $this->Validation->SchemaValidationFields();
         $Fields = RemoveKeyFromArray($Fields, 'CategoryID');
            $AllowDiscussions = ArrayValue('AllowDiscussions', $Fields) == '1' ? TRUE : FALSE;
         $Fields['AllowDiscussions'] = $AllowDiscussions ? '1' : '0';

         if ($Insert === FALSE) {
            $OldCategory = $this->GetID($CategoryID);
            $AllowDiscussions = $OldCategory->AllowDiscussions; // Force the allowdiscussions property
            $Fields['AllowDiscussions'] = $AllowDiscussions ? '1' : '0';
            $this->Update($Fields, array('CategoryID' => $CategoryID));
         } else {
            $CategoryID = $this->Insert($Fields);
            $this->RebuildTree(); // Safeguard to make sure that treeleft and treeright cols are added
         }
         
         // Save the permissions
         if ($AllowDiscussions && $CategoryID) {
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
               }

               // Delete my custom permissions.
               $this->SQL->Delete('Permission',
                  array('JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => $CategoryID));
            }
         }
         
         // Force the user permissions to refresh.
         $this->SQL->Put('User', array('Permissions' => ''), array('Permissions <>' => ''));
         // $this->RebuildTree();
      } else {
         $CategoryID = FALSE;
      }
      
      return $CategoryID;
   }
   
   public function ApplyUpdates() {
      // If looking at the root node, make sure it exists and that the nested
      // set columns exist in the table (added in Vanilla 2.0.15)
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
            $this->SQL->Insert('Category', array('CategoryID' => -1, 'TreeLeft' => 1, 'TreeRight' => 4, 'Depth' => 0, 'InsertUserID' => 1, 'UpdateUserID' => 1, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime(), 'Name' => 'Root', 'UrlCode' => '', 'Description' => 'Root of category tree. Users should never see this.'));
         
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
	public function AddCategoryColumns($Data) {
		$Result = &$Data->Result();
      $Result2 = $Result;
		foreach ($Result as &$Category) {
         if (!property_exists($Category, 'CountAllDiscussions'))
            $Category->CountAllDiscussions = $Category->CountDiscussions;
            
         if (!property_exists($Category, 'CountAllComments'))
            $Category->CountAllComments = $Category->CountComments;

         foreach ($Result2 as $Category2) {
            if ($Category2->TreeLeft > $Category->TreeLeft && $Category2->TreeRight < $Category->TreeRight) {
               $Category->CountAllDiscussions += $Category2->CountDiscussions;
               $Category->CountAllComments += $Category2->CountComments;
            }
         }
		}
	}
     /**
    * Get category subtree ids (respecting user permission).
    * 
    * @access public
    *
    * @param string $CategoryID the root of the subtree.
    * @return Gdn_DataSet SQL results.
    */
   public function GetSubTreeIds($CategoryID) {
   	  $mainCat = $this->GetID($CategoryID);
   	  if(!$mainCat){
   	  	//Category not found
   	  	return array();
   	  }
   	  elseif ($mainCat->TreeLeft+1 >= $mainCat->TreeRight){
   	  	//Category is leaf
   	  	return array($mainCat->CategoryID);
   	  }
   	  else{
   	  	 $this->SQL
         ->Select('c.CategoryID')
         ->From('Category c')
         ->BeginWhereGroup()
         ->Where('c.TreeLeft >=', $mainCat->TreeLeft)
         ->Where('c.TreeRight <=', $mainCat->TreeRight)
         ->Permission('Vanilla.Discussions.View', 'c', 'PermissionCategoryID', 'Category')
         ->EndWhereGroup()
         ->OrWhere('AllowDiscussions', '0')
         ->OrderBy('TreeLeft', 'asc');
         $subTreeQuery = $this->SQL->Get();
         $subTree = $subTreeQuery->ResultArray();
         for($i = 0; $i < count($subTree); $i++){
         	$subTree[$i] = $subTree[$i]['CategoryID'];
         }
         return $subTree;
   	  }
   }
}