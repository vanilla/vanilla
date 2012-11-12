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
 * Search Controller
 *
 * @package Dashboard
 */
 
/**
 * Manages basic searching.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class SearchController extends Gdn_Controller {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Database');
   
   // Object initialization
   public $Form;
   public $SearchModel;   

   /**
    * Object instantiation & form prep.
    */
	public function __construct() {
		parent::__construct();
   
      // Object instantiation
      $this->SearchModel = new SearchModel();
		$Form = Gdn::Factory('Form');
		
		// Form prep
		$Form->Method = 'get';
		$Form->InputPrefix = '';
		$this->Form = $Form;
	}
	
	/**
    * Add JS, CSS, modules. Automatically run on every use.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('jquery.expander.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      $this->AddCssFile('menu.css');
      $this->AddModule('GuestModule');
      parent::Initialize();
      $this->SetData('Breadcrumbs', array(array('Name' => T('Search'), 'Url' => '/search')));
   }
	
	/**
    * Default search functionality.
    *
    * @since 2.0.0
    * @access public
    * @param int $Page Page number.
    */
	public function Index($Page = '') {
		$this->AddJsFile('search.js');
		$this->Title(T('Search'));
      
      SaveToConfig('Garden.Format.EmbedSize', '160x90', FALSE);
      
      list($Offset, $Limit) = OffsetLimit($Page, C('Garden.Search.PerPage', 20));
      $this->SetData('_Limit', $Limit);
		
		$Search = $this->Form->GetFormValue('Search');
      $Mode = $this->Form->GetFormValue('Mode');
      if ($Mode)
         $this->SearchModel->ForceSearchMode = $Mode;
      try {
         $ResultSet = $this->SearchModel->Search($Search, $Offset, $Limit);
      } catch (Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
         $ResultSet = array();
      } catch (Exception $Ex) {
         LogException($Ex);
         $this->Form->AddError($Ex);
         $ResultSet = array();
      }
      Gdn::UserModel()->JoinUsers($ResultSet, array('UserID'));
		$this->SetData('SearchResults', $ResultSet, TRUE);
		$this->SetData('SearchTerm', Gdn_Format::Text($Search), TRUE);
		if($ResultSet)
			$NumResults = count($ResultSet);
		else
			$NumResults = 0;
		if ($NumResults == $Offset + $Limit)
			$NumResults++;
		
		// Build a pager
		$PagerFactory = new Gdn_PagerFactory();
		$this->Pager = $PagerFactory->GetPager('MorePager', $this);
		$this->Pager->MoreCode = 'More Results';
		$this->Pager->LessCode = 'Previous Results';
		$this->Pager->ClientID = 'Pager';
		$this->Pager->Configure(
			$Offset,
			$Limit,
			$NumResults,
			'dashboard/search/%1$s/%2$s/?Search='.Gdn_Format::Url($Search)
		);
		
//		if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
//         $this->SetJson('LessRow', $this->Pager->ToString('less'));
//         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
//         $this->View = 'results';
//      }
		
      $this->CanonicalUrl(Url('search', TRUE));

		$this->Render();
	}
}