<?php if (!defined('APPLICATION')) exit();
/**
 *
 */

/**
 * Renders the discussion sorter.
 */
class DiscussionSorterModule extends Gdn_Module {  
   /** @array Available sort options. data-field => Text for user. */
   var $SortOptions;
   
   /** @string Current sort field user preference. */
   var $SortFieldSelected;
   
   public function __construct($Sender) {
      parent::__construct($Sender, 'Vanilla');
      
      $this->Visible = C('Vanilla.Discussions.UserSortField');
      
      // Default options
      $this->SortOptions = array(
         'd.DateLastComment' => T('SortOptionLastComment', 'by Last Comment'),
         'd.DateInserted' => T('SortOptionStartDate', 'by Start Date')
      );
      
      // Get sort option selected
      $this->SortFieldSelected = Gdn::Session()->GetPreference('Discussions.SortField', 'd.DateLastComment');
   }
   
   public function AssetTarget() {
      return FALSE;
   }

   public function ToString() {
      if (Gdn::Session()->IsValid())
         return parent::ToString();
   }
}