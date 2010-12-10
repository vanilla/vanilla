<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class SearchController extends Gdn_Controller {

	public function __construct() {
		parent::__construct();
		
		$Form = Gdn::Factory('Form');
		$Form->Method = 'get';
		$Form->InputPrefix = '';
		
		$this->Form = $Form;
	}
	
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      $this->AddCssFile('menu.css');
      $GuestModule = new GuestModule($this);
      $this->AddModule($GuestModule);
      parent::Initialize();
   }

   public $Uses = array('Database', 'SearchModel');
	
	public $Form;
	
	public function Index($Offset = 0, $Limit = NULL) {
		$this->AddJsFile('jquery.gardenmorepager.js');
		$this->AddJsFile('search.js');
		$this->Title(T('Search'));

		if(!is_numeric($Limit))
			$Limit = Gdn::Config('Garden.Search.PerPage', 20);
		
		$Search = $this->Form->GetFormValue('Search');
      $Mode = $this->Form->GetFormValue('Mode');
      if ($Mode)
         $this->SearchModel->ForceSearchMode = $Mode;
		$ResultSet = $this->SearchModel->Search($Search, $Offset, $Limit);
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
		
		if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'results';
      }
		
      $this->CanonicalUrl(Url('search', TRUE));

		$this->Render();
	}
}