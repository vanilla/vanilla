<?php
/**
 * Manages basic searching.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /search endpoint.
 */
class SearchController extends Gdn_Controller {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Database');

    /**  @var Gdn_Form */
    public $Form;

    /**  @var SearchModel */
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
        $this->Form = $Form;
    }

    /**
     * Add JS, CSS, modules. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.expander.js');
        $this->addJsFile('global.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('menu.css');
        $this->addModule('GuestModule');
        parent::initialize();
        $this->setData('Breadcrumbs', array(array('Name' => t('Search'), 'Url' => '/search')));
    }

    /**
     * Default search functionality.
     *
     * @since 2.0.0
     * @access public
     * @param int $Page Page number.
     */
    public function index($Page = '') {
        $this->addJsFile('search.js');
        $this->title(t('Search'));

        saveToConfig('Garden.Format.EmbedSize', '160x90', false);
        Gdn_Theme::section('SearchResults');

        list($Offset, $Limit) = offsetLimit($Page, c('Garden.Search.PerPage', 20));
        $this->setData('_Limit', $Limit);

        $Search = $this->Form->getFormValue('Search');
        $Mode = $this->Form->getFormValue('Mode');
        if ($Mode) {
            $this->SearchModel->ForceSearchMode = $Mode;
        }
        try {
            $ResultSet = $this->SearchModel->search($Search, $Offset, $Limit);
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
            $ResultSet = array();
        } catch (Exception $Ex) {
            LogException($Ex);
            $this->Form->addError($Ex);
            $ResultSet = array();
        }
        Gdn::userModel()->joinUsers($ResultSet, array('UserID'));

        // Fix up the summaries.
        $SearchTerms = explode(' ', Gdn_Format::text($Search));
        foreach ($ResultSet as &$Row) {
            $Row['Summary'] = searchExcerpt(htmlspecialchars(Gdn_Format::plainText($Row['Summary'], $Row['Format'])), $SearchTerms);
            $Row['Summary'] = Emoji::instance()->translateToHtml($Row['Summary']);
            $Row['Format'] = 'Html';
        }

        $this->setData('SearchResults', $ResultSet, true);
        $this->setData('SearchTerm', Gdn_Format::text($Search), true);

        $this->setData('_CurrentRecords', count($ResultSet));

        $this->canonicalUrl(url('search', true));
        $this->render();
    }
}
