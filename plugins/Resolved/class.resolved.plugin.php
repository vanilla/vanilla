<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Resolved'] = array(
   'Name' => 'Resolved Discussions',
   'Description' => "Adds an option to mark discussions as Resolved with custom permission. Resolved discussions are Closed to new participants, however additional posts by the OP unresolve it. Only users with the custom permission see its Resolved status.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'RegisterPermissions' => array('Plugins.Resolved.Manage'),
   'MobileFriendly' => TRUE,
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class ResolvedPlugin extends Gdn_Plugin {
   /**
    * Add 'Resolved' columns
    */
   public function Setup() {
      Gdn::Structure()
         ->Table('Discussion')
         ->Column('Resolved', 'int', '0')
         ->Column('DateResolved', 'datetime', TRUE)
         ->Column('ResolvedUserID', 'int', TRUE)
         ->Set();
   }
   
   /**
    * Allow staff to Resolve via checkbox under comment form.
    */
   public function Base_AfterBodyField_Handler($Sender, $Args) {
      if (CheckPermission('Plugins.Resolved.Manage'))
         echo '<span class="ResolvedCheckbox">'.
            $Sender->Form->CheckBox('Resolved', T('Resolved'), array('value' => '1')).'</span>';
   }
   
   /**
    * Allow staff to Resolve via discussion options.
    */
   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];
      $Resolved = GetValue('Resolved', $Discussion);
      $NewResolved = (int)!$Resolved; 
      if (CheckPermission('Plugins.Resolved.Manage')) {
         $Label = T($Resolved ? 'Unresolve' : 'Resolve');
         $Url = "/discussion/resolve?discussionid={$Discussion->DiscussionID}&resolve=$NewResolved";
         // Deal with inconsistencies in how options are passed
         if (isset($Sender->Options)) {
            $Sender->Options .= Wrap(Anchor($Label, $Url, 'ResolveDiscussion Hijack'), 'li');
         }
         else {
            $Args['DiscussionOptions']['ResolveDiscussion'] = array(
               'Label' => $Label,
               'Url' => $Url,
               'Class' => 'ResolveDiscussion Hijack'
            );
         }
      }
   }
   
   /**
    * Show Unresolved meta tag.
    */
   public function Base_BeforeDiscussionMeta_Handler($Sender, $Args) {
      $Resolved = GetValue('Resolved', GetValue('Discussion', $Args));
      if (CheckPermission('Plugins.Resolved.Manage') && !$Resolved) {
         echo ' <span class="Tag Tag-Unresolved">'.T('Unresolved').'</span> ';
      }
   }
   
   /**
    * Show [RESOLVED] in discussion title when viewing single.
    */
   public function DiscussionController_BeforeDiscussionOptions_Handler($Sender, $Args) {
      $Discussion = $Sender->Data('Discussion');
      if (CheckPermission('Plugins.Resolved.Manage') && $Discussion->Resolved) {
         $NewName = '<span class="DiscussionResolved">[RESOLVED]</span> '.GetValue('Name', $Discussion);
         SetValue('Name', $Discussion, $NewName);
         $Sender->SetData('Discussion', $Discussion);
      }
   }
   
   /**
    * Handle discussion option menu Resolve action.
    */
   public function DiscussionController_Resolve_Create($Sender, $Args) {
      $Sender->Permission('Plugins.Resolved.Manage');  
      $DiscussionID = $Sender->Request->Get('discussionid');
      $Resolve = $Sender->Request->Get('resolve');
      
      // Make sure we are posting back.
      if (!$Sender->Request->IsPostBack())
         throw PermissionException('Javascript');
      
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      
      if (!$Discussion)
         throw NotFoundException('Discussion');
      
      // Resolve the discussion.
      $Sender->DiscussionModel->SetField($DiscussionID, 'Resolved', $Resolve);
      $Discussion->Resolved = $Resolve;
      
      $Sender->SendOptions($Discussion);
      
      if (!$Resolve) {
         require_once $Sender->FetchViewLocation('helper_functions', 'Discussions');
         $Sender->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Meta-Discussion", 
            '<span class="Tag Tag-Unresolved" title="Unresolved">'.T('Unresolved').'</span>', 'Prepend');
         $Sender->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", 'Unresolved', 'AddClass');
      } else {
         $Sender->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Tag-Unresolved", NULL, 'Remove');
         $Sender->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", 'Unresolved', 'RemoveClass');
      }
      
      $Sender->JsonTarget("#Discussion_$DiscussionID", NULL, 'Highlight');
      $Sender->JsonTarget(".Discussion #Item_0", NULL, 'Highlight');
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    * Handle comment form Resolved checkbox & new user comments.
    */
   public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
      $Resolved = GetValueR('FormPostValues.Resolved', $Args);
      if (!CheckPermission('Plugins.Resolved.Manage')) {
         // Unset Resolved flag
         $DiscussionModel = new DiscussionModel();
         $DiscussionID = GetValue('DiscussionID', $Args['FormPostValues']);
         $DiscussionModel->SetField($DiscussionID, 'Resolved', 0);
      }
      else if ($Resolved) {
         // Set Resolved flag
         $DiscussionModel = new DiscussionModel();
         $DiscussionID = GetValue('DiscussionID', $Args['FormPostValues']);
         $DiscussionModel->SetField($DiscussionID, 'Resolved', 1);
      }
   }

   /**
    * Disallow comments in Resolved discussions by new participants.
    * 
    * @param DiscussionController $Sender
    */
   public function DiscussionController_BeforeDiscussionRender_Handler($Sender, $Args) {
      $Discussion = $Sender->Data('Discussion');
      $Resolved = GetValue('Resolved', $Discussion);
      $IsStarter = (GetValue('InsertUserID', $Discussion) == Gdn::Session()->UserID);
      if (!CheckPermission('Plugins.Resolved.Manage') && $Resolved && !$IsStarter) {
         // Pretend we're closed 
         SetValue('Closed', $Discussion, 1);  
         $Sender->SetData('Discussion', $Discussion);
      }
   }
   
   /**
    * Add 'Unresolved' discussions filter to menu.
    */
   public function Base_AfterDiscussionFilters_Handler($Sender) {
      if (CheckPermission('Plugins.Resolved.Manage')) {
         $Unresolved .= T('Unresolved') . FilterCountString(self::CountUnresolved());
         echo '<li class="Unresolved">'.Anchor(Sprite('SpUnresolved').' '.$Unresolved, '/discussions/unresolved').'</li>';
      }
   }

   /**
    * Count the number of unresolved discussions.
    *
    * @return int
    */
   public static function CountUnresolved() {
      $NumUnresolved = Gdn::SQL()
         ->Select('count(DISTINCT d.DiscussionID)', '', 'NumUnresolved')
         ->From('Discussion d')
         ->Where('d.Resolved', 0)
         ->Get()
         ->FirstRow()
         ->NumUnresolved;

      return $NumUnresolved;
   }
   
   /**
    * Discussions filter: Unresolved.
    */
   public function DiscussionsController_Unresolved_Create($Sender, $Args) {
      $Sender->Permission('Plugins.Resolved.Manage');
      $Page = ArrayValue(0, $Args, 0);
      
      // Determine offset from $Page
      list($Page, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
      
      // Validate $Page
      if (!is_numeric($Page) || $Page < 0)
         $Page = 0;
      
      $DiscussionModel = new DiscussionModel();
      $Wheres = array('d.Resolved' => '0');
      
      $Sender->DiscussionData = $DiscussionModel->Get($Page, $Limit, $Wheres);
      $Sender->SetData('Discussions', $Sender->DiscussionData);
      $CountDiscussions = $DiscussionModel->GetCount($Wheres);
      $Sender->SetData('CountDiscussions', $CountDiscussions);
      $Sender->Category = FALSE;
      
      $Sender->SetJson('Loading', $Page . ' to ' . $Limit);
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->EventArguments['PagerType'] = 'Pager';
      $Sender->FireEvent('BeforeBuildBookmarkedPager');
      $Sender->Pager = $PagerFactory->GetPager($Sender->EventArguments['PagerType'], $Sender);
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Page,
         $Limit,
         $CountDiscussions,
         'discussions/unresolved/%1$s'
      );
      
      if (!$Sender->Data('_PagerUrl'))
         $Sender->SetData('_PagerUrl', 'discussions/unresolved/{Page}');
      $Sender->SetData('_Page', $Page);
      $Sender->SetData('_Limit', $Limit);
      $Sender->FireEvent('AfterBuildBookmarkedPager');
      
      // Deliver JSON data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }
      
      // Add modules
      $Sender->AddModule('DiscussionFilterModule');
      $Sender->AddModule('NewDiscussionModule');
      $Sender->AddModule('CategoriesModule');
      
      // Render default view
      $Sender->SetData('Title', T('Unresolved'));
      $Sender->SetData('Breadcrumbs', array(array('Name' => T('Unresolved'), 'Url' => '/discussions/unresolved')));
      $Sender->Render('index');
   }
}