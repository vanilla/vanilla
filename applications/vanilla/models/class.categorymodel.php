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
         }
         
         // Delete the category
         $this->SQL->Delete('Category', array('CategoryID' => $Category->CategoryID));
         
         // If there are no parent categories left, make sure that all other
         // categories are not assigned
         if ($this->SQL
            ->Select('CategoryID')
            ->From('Category')
            ->Where('AllowDiscussions', '0')
            ->Get()
            ->NumRows() == 0) {
            $this->SQL
               ->Update('Category')
               ->Set('ParentCategoryID', 'null', FALSE)
               ->Put();
         }
         
         // If there is only one category, make sure that Categories are not used
         $CountCategories = $this->Get()->NumRows();
         SaveToConfig('Vanilla.Categories.Use', $CountCategories > 1);
      }
      // Make sure to reorganize the categories after deletes
      $this->Organize();
   }
      
   /**
    * Get data for a single category selected by ID
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
    * @return object SQL results.
    */
   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->SQL
         ->Select('c.ParentCategoryID, c.CategoryID, c.Name, c.Description, c.CountDiscussions, c.AllowDiscussions, c.UrlCode')
         ->From('Category c')
         ->BeginWhereGroup()
         ->Permission('Vanilla.Discussions.View', 'c', 'CategoryID')
         ->EndWhereGroup()
         ->OrWhere('AllowDiscussions', '0')
         ->OrderBy('Sort', 'asc');
         
      return $this->SQL->Get();
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
   public function GetAll($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->SQL
         ->Select('c.ParentCategoryID, c.CategoryID, c.Name, c.Description, c.CountDiscussions, c.AllowDiscussions, c.UrlCode')
         ->From('Category c')
         ->OrderBy('Sort', 'asc');
         
      return $this->SQL->Get();
   }

   /**
    * Get full data for a single category or all categories.
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
      // Build base query
      $this->SQL
         ->Select('c.Name, c.CategoryID, c.Description, c.CountDiscussions, c.UrlCode')
         ->Select('p.CategoryID', '', 'ParentCategoryID')
         ->Select('p.Name', '', 'ParentName')
         ->From('Category c')
         ->Join('Category p', 'c.ParentCategoryID = p.CategoryID', 'left')
         ->Where('c.AllowDiscussions', '1');

      // Minimally check for view discussion permission
      if (!$Permissions)
         $Permissions = 'Vanilla.Discussions.View';

      $this->SQL->Permission($Permissions, 'c', 'CategoryID');

      // Single record or full list?
      if (is_numeric($CategoryID) && $CategoryID > 0)
         return $this->SQL->Where('c.CategoryID', $CategoryID)->Get()->FirstRow();
      else
         return $this->SQL->OrderBy('c.Sort')->Get();
   }
   
   /**
    * Get full data for a single category by its URL slug.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param string $UrlCode Unique category slug from URL.
    * @return object SQL results.
    */
   public function GetFullByUrlCode($UrlCode) {
      $this->SQL
         ->Select('c.CategoryID, c.Description, c.CountDiscussions, c.UrlCode')
         ->Select("' &rarr; ', p.Name, c.Name", 'concat_ws', 'Name')
         ->From('Category c')
         ->Join('Category p', 'c.ParentCategoryID = p.CategoryID', 'left')
         ->Where('c.AllowDiscussions', '1')
         ->Where('c.UrlCode', $UrlCode);
      
      // Require permission   
      $this->SQL->Permission('Vanilla.Discussions.View', 'c', 'CategoryID');
         
      return $this->SQL
         ->Get()
         ->FirstRow();
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
    * Organizes the category table so that all child categories are sorted
    * below the appropriate parent category.
    * 
    * They can get out of wack when parent categories are deleted and their 
    * children are re-assigned to a new parent category.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Organize() {
      // Load all categories
      $CategoryData = $this->Get('Sort');
      $ParentsExist = FALSE;
      foreach ($CategoryData->Result() as $Category) {
         if ($Category->AllowDiscussions == '0')
            $ParentsExist = TRUE;
      }
      // Only reorder if there are parent categories present.
      if ($ParentsExist) {
         // If parent categories exist, make sure that:
         // 1. Child categories fall underneath parent categories
         // 2. When a child appears under a parent, it becomes a child of that parent.
         $FirstParent = FALSE;
         $CurrentParent = FALSE;
         $Orphans = array();
         $i = 0;
         foreach ($CategoryData->Result() as $Category) {
            if ($Category->AllowDiscussions == '0')
               $CurrentParent = $Category;
               
            // If there hasn't been a parent yet OR
            // $Category isn't a parent category, and it is not a child of the
            // current parent, add it to the orphans collection
            if (!$CurrentParent) {
               $Orphans[] = $Category->CategoryID;
            } else if ($Category->CategoryID != $CurrentParent->CategoryID
               && $Category->ParentCategoryID != $CurrentParent->CategoryID) {
               // Make this category a child of the current parent and assign the sort
               $i++;
               $this->Update(
                  array(
                     'ParentCategoryID' => $CurrentParent->CategoryID,
                     'Sort' => $i
                  ),
                  array('CategoryID' => $Category->CategoryID)
               );
            } else {
               // Otherwise, assign the sort
               $i++;
               $this->Update(array('Sort' => $i), array('CategoryID' => $Category->CategoryID));
            }
         }
         // And now sort the orphans and assign them to the last parent
         foreach ($Orphans as $Key => $ID) {
            $i++;
            $this->Update(array('Sort' => $i, 'ParentCategoryID' => $CurrentParent->CategoryID), array('CategoryID' => $ID));
         }
      }
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
            // Make sure this category gets added to the end of the sort
            $SortData = $this->SQL
               ->Select('Sort')
               ->From('Category')
               ->OrderBy('Sort', 'desc')
               ->Limit(1)
               ->Get()
               ->FirstRow();
            $Fields['Sort'] = $SortData ? $SortData->Sort + 1 : 1;            
            $CategoryID = $this->Insert($Fields);
            
            if ($AllowDiscussions) {
               // If there are any parent categories, make this a child of the last one
               $ParentData = $this->SQL
                  ->Select('CategoryID')
                  ->From('Category')
                  ->Where('AllowDiscussions', '0')
                  ->OrderBy('Sort', 'desc')
                  ->Limit(1)
                  ->Get();
               if ($ParentData->NumRows() > 0) {
                  $this->SQL
                     ->Update('Category')
                     ->Set('ParentCategoryID', $ParentData->FirstRow()->CategoryID)
                     ->Where('CategoryID', $CategoryID)
                     ->Put();
               }               
            } else {
               // If there are any categories without parents, make this one the parent
               $this->SQL
                  ->Update('Category')
                  ->Set('ParentCategoryID', $CategoryID)
                  ->Where('ParentCategoryID is null')
                  ->Where('AllowDiscussions', '1')
                  ->Put();
            }
            $this->Organize();
         }
         
         // Save the permissions
         if ($AllowDiscussions) {
            $PermissionModel = Gdn::PermissionModel();
            $Permissions = $PermissionModel->PivotPermissions(GetValue('Permission', $FormPostValues, array()), array('JunctionID' => $CategoryID));
            $PermissionModel->SaveAll($Permissions, array('JunctionID' => $CategoryID));
         }
         
         // Force the user permissions to refresh.
         $this->SQL->Put('User', array('Permissions' => ''), array('Permissions <>' => ''));
      } else {
         $CategoryID = FALSE;
      }
      
      return $CategoryID;
   }
}