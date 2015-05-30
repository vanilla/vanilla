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
 * Renders recently active discussions
 */
class DiscussionsModule extends Gdn_Module {
    public $Limit = 10;
    public $Prefix = 'Discussion';

    /**
     * @var array Limit the discussions to just this list of categories, checked for view permission.
     */
    protected $categoryIDs;


    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'vanilla';
        $this->FireEvent('Init');
    }

    /**
     * Get the data for the module.
     *
     * @param int|bool $limit Override the number of discussions to display.
     */
    public function GetData($limit = FALSE) {
        if (!$limit) {
            $limit = $this->Limit;
        }

        $discussionModel = new DiscussionModel();

        $categoryIDs = $this->getCategoryIDs();
        $where = array('Announce' => 'all');

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $discussionModel->Watching = TRUE;
        }

        $this->SetData('Discussions', $discussionModel->Get(0, $limit, $where));
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if (!$this->Data('Discussions')) {
            $this->GetData();
        }

        require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

        return parent::ToString();
    }

    /**
     * Get a list of category IDs to limit.
     *
     * @return array
     */
    public function getCategoryIDs() {
        return $this->categoryIDs;
    }

    /**
     * Set a list of category IDs to limit.
     *
     * @param array $categoryIDs
     */
    public function setCategoryIDs($categoryIDs) {
        $this->categoryIDs = $categoryIDs;
    }
}
