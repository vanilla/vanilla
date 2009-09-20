<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class SearchController extends GardenController {
	/// CONSTRUCTOR ///
	public function __construct() {
		parent::__construct();
		
		$Form = Gdn::Factory('Form');
		$Form->Method = 'get';
		$Form->InputPrefix = '';
		
		$this->Form = $Form;
	}
	
	/// PROPERTIES ///
   public $Uses = array('Database', 'Gdn_SearchModel');
	
	public $Form;
	
	/// METHODS ///
	public function Index($Offset = 0, $Limit = NULL) {
      if ($this->Head) {
			$this->Head->AddScript('/js/library/jquery.gardenmorepager.js');
         $this->Head->AddScript('/applications/garden/js/search.js');
			$this->Head->Title(Translate('Search'));
      }

		if(!is_numeric($Limit))
			$Limit = Gdn::Config('Garden.Search.PerPage', 20);
		
		$Search = $this->Form->GetFormValue('Search');
		$ResultSet = $this->SearchModel->Search($Search, $Offset, $Limit);
		$this->SetData('SearchResults', $ResultSet, TRUE);
		$this->SetData('SearchTerm', Format::Text($Search), TRUE);
		$NumResults = $ResultSet->NumRows();
		if ($NumResults == $Offset + $Limit)
			$NumResults++;
		
		// Build a pager
		$PagerFactory = new PagerFactory();
		$Pager = $PagerFactory->GetPager('MorePager', $this);
		$Pager->MoreCode = 'More Results';
		$Pager->LessCode = 'Previous Results';
		$Pager->ClientID = 'Pager';
		$Pager->Configure(
			$Offset,
			$Limit,
			$NumResults,
			'garden/search/%1$s/%2$s/?Search='.Format::Url($Search)
		);
		$this->SetData('Pager', $Pager, TRUE);
		
		$this->View = 'results';
		$this->Render();
	}
}