<?php
/**
 * Discussion Sort module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
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

        $this->Visible = c('Vanilla.Discussions.UserSortField');

        // Default options
        $this->SortOptions = array(
            'd.DateLastComment' => t('SortOptionLastComment', 'by Last Comment'),
            'd.DateInserted' => t('SortOptionStartDate', 'by Start Date')
        );

        // Get sort option selected
        $this->SortFieldSelected = Gdn::session()->GetPreference('Discussions.SortField', 'd.DateLastComment');
    }

    public function assetTarget() {
        return false;
    }

    public function toString() {
        if (Gdn::session()->isValid()) {
            return parent::ToString();
        }
    }
}
