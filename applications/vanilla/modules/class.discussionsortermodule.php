<?php
/**
 * Discussion Sort module
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

    public function __construct($sender) {
        deprecated('DiscussionSorterModule', 'DiscussionSortFilterModule', 'March 2016');

        parent::__construct($sender, 'Vanilla');

        $this->Visible = false;

        // Default options
        $this->SortOptions = [
            'd.DateLastComment' => t('SortOptionLastComment', 'by Last Comment'),
            'd.DateInserted' => t('SortOptionStartDate', 'by Start Date')
        ];

        // Get sort option selected
        $this->SortFieldSelected = Gdn::session()->getPreference('Discussions.SortField', 'd.DateLastComment');
    }

    public function assetTarget() {
        return false;
    }

    public function toString() {
        if (Gdn::session()->isValid()) {
            return parent::toString();
        }
    }
}
