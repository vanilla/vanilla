<?php
/**
 * Manages basic searching.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /search endpoint.
 */
class SearchController extends Gdn_Controller {

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Database'];

    /**  @var Gdn_Form */
    public $Form;

    /**  @var SearchModel */
    public $SearchModel;

    /**
     * Object instantiation & form prep.
     */
    public function __construct(SearchModel $searchModel = null) {
        parent::__construct();

        // Object instantiation.
        if ($searchModel === null) {
            $searchModel = new SearchModel();
        }
        $this->SearchModel = $searchModel;
        $form = Gdn::factory('Form');

        // Form prep
        $form->Method = 'get';
        $this->Form = $form;
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
        $this->addModule('GuestModule');
        parent::initialize();
        $this->setData('Breadcrumbs', [['Name' => t('Search'), 'Url' => '/search']]);
    }

    /**
     * Default search functionality.
     *
     * @param string $search The search string.
     * @param string $page Page number.
     */
    public function index($search = '', $page = '') {
        $this->addJsFile('search.js');
        $this->title(t('Search'));

        saveToConfig('Garden.Format.EmbedSize', '160x90', false);
        Gdn_Theme::section('SearchResults');

        list($offset, $limit) = offsetLimit($page, c('Garden.Search.PerPage', 20));
        $this->setData('_Limit', $limit);

        $mode = $this->Form->getFormValue('Mode');
        if ($mode) {
            $this->SearchModel->ForceSearchMode = $mode;
        }
        try {
            $resultSet = $this->SearchModel->search($search, $offset, $limit);
        } catch (Gdn_UserException $ex) {
            $this->Form->addError($ex);
            $resultSet = [];
        } catch (Exception $ex) {
            logException($ex);
            $this->Form->addError($ex);
            $resultSet = [];
        }
        Gdn::userModel()->joinUsers($resultSet, ['UserID']);

        // Fix up the summaries.
        $searchTerms = explode(' ', Gdn_Format::text($search));
        foreach ($resultSet as &$row) {
            $row['Summary'] = searchExcerpt(htmlspecialchars(Gdn_Format::plainText($row['Summary'], $row['Format'])), $searchTerms);
            $row['Summary'] = Emoji::instance()->translateToHtml($row['Summary']);
            $row['Format'] = 'Html';
        }

        $this->setData('SearchResults', $resultSet, true);
        $this->setData('SearchTerm', Gdn_Format::text($search), true);

        $this->setData('_CurrentRecords', count($resultSet));

        $this->canonicalUrl(url('search', true));
        $this->render();
    }
}
