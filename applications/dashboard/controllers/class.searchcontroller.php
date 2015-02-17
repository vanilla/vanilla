<?php if (!defined('APPLICATION')) exit();

/**
 * Manages basic searching.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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
      $this->AddCssFile('vanillicon.css', 'static');
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
      Gdn_Theme::Section('SearchResults');
      
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
      
      // Fix up the summaries.
      $SearchTerms = explode(' ', Gdn_Format::Text($Search));
      foreach ($ResultSet as &$Row) {
         $Row['Summary'] = SearchExcerpt(Gdn_Format::PlainText($Row['Summary'], $Row['Format']), $SearchTerms);
         $Row['Summary'] = Emoji::instance()->translateToHtml($Row['Summary']);
         $Row['Format'] = 'Html';
      }
      
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
