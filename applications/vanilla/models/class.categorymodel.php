<?php if (!defined('APPLICATION')) exit();

/**
 * Manages discussion categories.
 */
class Gdn_CategoryModel extends Gdn_Model {
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Category');
   }
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
         throw new Exception(Gdn::Translate('Invalid category for deletion.'));
      } else {
         // Remove permissions.
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

   public function GetID($CategoryID) {
      return $this->SQL->GetWhere('Category', array('CategoryID' => $CategoryID))->FirstRow();
   }

   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->SQL
         ->Select('c.ParentCategoryID, c.CategoryID, c.Name, c.Description, c.CountDiscussions, c.AllowDiscussions, c.UrlCode')
         ->From('Category c')
         ->BeginWhereGroup()
         ->Permission('c', 'CategoryID', 'Vanilla.Discussions.View')
         ->EndWhereGroup()
         ->OrWhere('AllowDiscussions', '0')
         ->OrderBy('Sort', 'asc');
         
      return $this->SQL->Get();
   }
   
   public function GetFull($CategoryID = '') {
      $this->SQL
         ->Select('c.CategoryID, c.Description, c.CountDiscussions, c.UrlCode')
         ->Select("' &rarr; ', p.Name, c.Name", 'concat_ws', 'Name')
         ->From('Category c')
         ->Join('Category p', 'c.ParentCategoryID = p.CategoryID', 'left')
         ->Where('c.AllowDiscussions', '1');
         
      $this->SQL->Permission('c', 'CategoryID', 'Vanilla.Discussions.View');

      if (is_numeric($CategoryID) && $CategoryID > 0)
         return $this->SQL->Where('c.CategoryID', $CategoryID)->Get()->FirstRow();
      else
         return $this->SQL->OrderBy('c.Sort')->Get();
   }

   public function GetFullByUrlCode($UrlCode) {
      $this->SQL
         ->Select('c.CategoryID, c.Description, c.CountDiscussions')
         ->Select("' &rarr; ', p.Name, c.Name", 'concat_ws', 'Name')
         ->From('Category c')
         ->Join('Category p', 'c.ParentCategoryID = p.CategoryID', 'left')
         ->Where('c.AllowDiscussions', '1')
         ->Where('c.UrlCode', $UrlCode);
         
      $this->SQL->Permission('c', 'CategoryID', 'Vanilla.Discussions.View');
         
      return $this->SQL
         ->Get()
         ->FirstRow();
   }

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
    * below the appropriate parent category (they can get out of wack when
    * parent categories are deleted and their children are re-assigned to a new
    * parent category).
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
         // If parent categories exist, make sure that child
         // categories fall underneath parent categories
         // and when a child appears under a parent, it becomes a child of that parent.
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
    * @param array $FormPostValue The values being posted back from the form.
    */
   public function Save($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      $CategoryID = ArrayValue('CategoryID', $FormPostValues);
      $NewName = ArrayValue('Name', $FormPostValues, '');
      $UrlCode = ArrayValue('UrlCode', $FormPostValues, '');
      $Insert = $CategoryID > 0 ? FALSE : TRUE;
      if ($Insert)
         $this->AddInsertFields($FormPostValues);               

      $this->AddUpdateFields($FormPostValues);
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
            $Permissions = $PermissionModel->PivotPermissions($FormPostValues['Permission'], array('JunctionID' => $CategoryID));
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