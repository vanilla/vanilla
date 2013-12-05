<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class TagModel extends Gdn_Model {
   public function  __construct($Name = '') {
      parent::__construct('Tag');
   }

   public function  Save($FormPostValues, $Settings = FALSE) {
      // Get the ID of an existing tag with the same name.
      $ExistingTag = $this->GetWhere(array('Name' => $FormPostValues['Name'], 'TagID <>' => GetValue('TagID', $FormPostValues)))->FirstRow(DATASET_TYPE_ARRAY);
      if ($ExistingTag) {
         if (!GetValue('TagID', $FormPostValues))
            return $ExistingTag['TagID'];

         // This tag will be merged with the existing one.
         $Px = $this->Database->DatabasePrefix;
         $FromID = $FormPostValues['TagID'];
         $ToID = $ExistingTag['TagID'];

         try {
            $this->Database->BeginTransaction();

            // Delete all of the overlapping tags.
            $Sql = "delete tg.*
               from {$Px}TagDiscussion tg
               join {$Px}TagDiscussion tg2
                 on tg.DiscussionID = tg2.DiscussionID
                   and tg.TagID = :FromID and tg2.TagID = :ToID";
            $this->Database->Query($Sql, array(':FromID' => $FromID, ':ToID' => $ToID));

            // Update the tagged discussions.
            $Sql = "update {$Px}TagDiscussion
               set TagID = :ToID
               where TagID = :FromID";
            $this->Database->Query($Sql, array(':FromID' => $FromID, ':ToID' => $ToID));

            // Update the counts
            $this->UpdateTagCountDiscussions($ToID);

            /*
            // Update the counts.
            $Sql = "update {$Px}Tag t
               set CountDiscussions = (
                  select count(DiscussionID)
                  from {$Px}TagDiscussion td
                  where td.TagID = t.TagID)
                where t.TagID = :ToID";
            $this->Database->Query($Sql, array(':ToID' => $ToID));
             */

            // Delete the old tag.
            $Sql = "delete from {$Px}Tag where TagID = :FromID";
            $this->Database->Query($Sql, array(':FromID' => $FromID));

            $this->Database->CommitTransaction();
         } catch (Exception $Ex) {
            $this->Database->RollbackTransaction();
            throw $Ex;
         }

         return $ToID;
      } else {
         if (Gdn::Session()->CheckPermission('Plugins.Tagging.Add')) {
            return parent::Save($FormPostValues, $Settings);
         } else {
            return FALSE;
         }
      }
   }

   /**
    * Update the tag count per discussion in the Tag table
    *
    * @param int $TagID
    */
   public function UpdateTagCountDiscussions($TagID) {
      $Px = $this->Database->DatabasePrefix;
      // Update the counts.
      $Sql = "update {$Px}Tag t
         set CountDiscussions = (
            select count(DiscussionID)
            from {$Px}TagDiscussion td
            where td.TagID = t.TagID)
          where t.TagID = :TagID";
      $this->Database->Query($Sql, array(':TagID' => $TagID));
   }

   public function GetDiscussions($Tag, $Limit, $Offset, $Op = 'or') {
      $DiscussionModel = new DiscussionModel();
      $this->_SetTagSql($DiscussionModel->SQL, $Tag, $Limit, $Offset, $Op);
      $Result = $DiscussionModel->Get($Offset, $Limit, array('Announce' => 'all'));

      return $Result;
   }

   /**
    *
    * @param Gdn_SQLDriver $Sql
    */
   public function _SetTagSql($Sql, $Tag, &$Limit, &$Offset = 0, $Op = 'or') {
      $SortField = 'd.DateLastComment';
      $SortDirection = 'desc';

      $TagSql = clone Gdn::Sql();

      if ($DateFrom = Gdn::Request()->Get('DateFrom')) {
         // Find the discussion ID of the first discussion created on or after the date from.
         $DiscussionIDFrom = $TagSql->GetWhere('Discussion', array('DateInserted >= ' => $DateFrom), 'DiscussionID', 'asc', 1)->Value('DiscussionID');
         $SortField = 'd.DiscussionID';
      }

      if (!is_array($Tag)) {
         $Tags = array_map('trim', explode(',', $Tag));
      }
      $TagIDs = $TagSql
         ->Select('TagID')
         ->From('Tag')
         ->WhereIn('Name', $Tags)
         ->Get()->ResultArray();

      $TagIDs = ConsolidateArrayValuesByKey($TagIDs, 'TagID');

      if ($Op == 'and' && count($Tags) > 1) {
         $DiscussionIDs = $TagSql
            ->Select('DiscussionID')
            ->Select('TagID', 'count', 'CountTags')
            ->From('TagDiscussion')
            ->WhereIn('TagID', $TagIDs)
            ->GroupBy('DiscussionID')
            ->Having('CountTags >=', count($Tags))
            ->Limit($Limit, $Offset)
            ->OrderBy('DiscussionID', 'desc')
            ->Get()->ResultArray();
         $Limit = '';
         $Offset = 0;

         $DiscussionIDs = ConsolidateArrayValuesByKey($DiscussionIDs, 'DiscussionID');

         $Sql->WhereIn('d.DiscussionID', $DiscussionIDs);
         $SortField = 'd.DiscussionID';
      } else {
         $Sql
            ->Join('TagDiscussion td', 'd.DiscussionID = td.DiscussionID')
            ->Limit($Limit, $Offset)
            ->WhereIn('td.TagID', $TagIDs);

         if ($Op == 'and')
            $SortField = 'd.DiscussionID';
      }

      // Set up the sort field and direction.
      SaveToConfig(array(
          'Vanilla.Discussions.SortField' => $SortField,
          'Vanilla.Discussions.SortDirection' => $SortDirection),
          '',
          FALSE);
   }

   public static function ValidateTag($Tag) {
      // Tags can't contain commas.
      if (preg_match('`,`', $Tag))
         return FALSE;
      return TRUE;
   }

   public static function ValidateTags($Tags) {
      if (is_string($Tags))
         $Tags = self::SplitTags($Tags);

      foreach ($Tags as $Tag) {
         if (!self::ValidateTag($Tag))
            return FALSE;
      }
      return TRUE;
   }

   public static function SplitTags($TagsString) {
      $Tags = preg_split('`[,]`', $TagsString);
      // Trim each tag.
      foreach ($Tags as $Index => $Tag) {
         $Tag = trim($Tag);
         if (!$Tag)
            unset($Tags[$Index]);
         else
            $Tags[$Index] = $Tag;
      }
      $Tags = array_unique($Tags);
      return $Tags;
   }
}