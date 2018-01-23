<?php
/**
 * Category Moderators module
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the moderators in the specified category. Built for use in a side panel.
 */
class CategoryModeratorsModule extends Gdn_Module {

    /**
     * CategoryModeratorsModule constructor.
     *
     * @param object|string $sender
     * @param bool $applicationFolder
     */
    public function __construct($sender = '', $applicationFolder = false) {
        parent::__construct($sender, $applicationFolder);
    }

    /**
     * Load the data for this module.
     *
     * @param array|object|null $category
     */
    protected function getData($category = null) {
        $data = $this->data('Moderators', null);

        // Only attempt to fetch data if we do not already have it.
        if ($data === null) {
            $data = false;

            // If we received a category, try to use it. If not, try to pull one from the current controller.
            if ($category === null) {
                $controller = Gdn::controller();
                $category = $controller->data('Category');
            } elseif (!is_array($category)) {
                $category = (array)$category;
            }

            // Moderators are fetched via the PermissionCategoryID property. Make sure we have it.
            $hasPermissionCategoryID = val('PermissionCategoryID', $category) !== false;
            if ($hasPermissionCategoryID) {
                // CategoryModel::joinModerators expects an array of category records.
                $category = [$category];
                CategoryModel::joinModerators($category);
                $moderators = val('Moderators', $category[0]);
                if (is_array($moderators) && count($moderators) > 0) {
                    // Success. Stash the moderators.
                    $data = $moderators;
                }
            }

            $this->setData('Moderators', $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * @inheritdoc
     */
    public function toString() {
        $result = '';
        $this->getData();

        $moderators = $this->data('Moderators');
        if (is_array($moderators)) {
            $result = parent::toString();
        }

        return $result;
    }
}
