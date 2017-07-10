<?php
/**
 * IndexPhotos Plugin.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package IndexPhotos
 */

/**
 * Class IndexPhotosPlugin
 *
 * @todo Just make this a core option on the Avatars page.
 */
class IndexPhotosPlugin extends Gdn_Plugin {

    /**
     * We need extra styling on the discussion list view.
     *
     * @param assetModel $Sender
     */
    public function assetModel_styleCss_handler($Sender) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            $Sender->addCssFile('indexphotos.css', 'plugins/IndexPhotos');
        }
    }

    /**
     * Add OP name to start of discussion meta on discussions pages.
     *
     * @param discussionsController $Sender
     * @param array $Args
     */
    public function discussionsController_afterDiscussionLabels_handler($Sender, $Args) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            if (val('FirstUser', $Args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $Args)).'</span>';
            }
        }
    }

    /**
     * Add OP name to start of discussion meta on categories pages
     *
     * @param categoriesController $Sender
     * @param array $Args
     */
    public function categoriesController_afterDiscussionLabels_handler($Sender, $Args) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            if (val('FirstUser', $Args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $Args)).'</span>';
            }
        }
    }

    /**
     * Show user photos on discussions pages.
     *
     * @param discussionsController $Sender
     */
    public function discussionsController_beforeDiscussionContent_handler($Sender) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            $this->displayPhoto($Sender);
        }
    }

    /**
     * Show user photos on categories pages.
     *
     * @param categoriesController $Sender
     */
    public function categoriesController_beforeDiscussionContent_handler($Sender) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            $this->displayPhoto($Sender);
        }
    }

    /**
     * Display user photo for first user in each discussion.
     */
    protected function displayPhoto($Sender) {
        // Build user object & output photo
        $FirstUser = userBuilder($Sender->EventArguments['Discussion'], 'First');
        echo userPhoto($FirstUser, ['LinkClass' => 'IndexPhoto']);
    }
}
