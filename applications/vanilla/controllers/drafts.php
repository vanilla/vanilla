<?php if (!defined('APPLICATION')) exit();

/// <summary>
/// Discussions Controller
/// </summary>
class DraftsController extends VanillaController {
   
   public $Uses = array('Database', 'DiscussionModel', 'CommentModel');
   
   public function Index($Offset = '0') {
      $this->Permission('Garden.SignIn.Allow');
      $this->AddCssFile('vanilla.screen.css');
      $this->AddCssFile('form.screen.css');
      $Session = Gdn::Session();
      if ($this->Head) {
         $this->Head->AddScript('/applications/vanilla/js/discussions.js');
         $this->Head->AddScript('/applications/vanilla/js/options.js');
      }
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;
      
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 30);
      $Session = Gdn::Session();
      $Wheres = array('d.InsertUserID' => $Session->UserID);
      $DiscussionModel = new DiscussionModel();
      $this->DraftData = $DiscussionModel->GetDrafts($Session->UserID, $Offset, $Limit);
      $CountDrafts = $DiscussionModel->GetDraftCount($Session->UserID);
      
      // Build a pager
      $PagerFactory = new PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'More drafts';
      $this->Pager->LessCode = 'Newer drafts';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $CountDrafts,
         'drafts/%1$s'
      );
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'drafts';
      }
      
      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      // Render the controller
      $this->Render();
   }
}