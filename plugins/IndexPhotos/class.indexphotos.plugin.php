<?php
/**
 * IndexPhotos Plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param assetModel $sender
     */
    public function assetModel_styleCss_handler($sender) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            $sender->addCssFile('indexphotos.css', 'plugins/IndexPhotos');
        }
    }

    /**
     * Add OP name to start of discussion meta on discussions pages.
     *
     * @param discussionsController $sender
     * @param array $args
     */
    public function discussionsController_afterDiscussionLabels_handler($sender, $args) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            if (val('FirstUser', $args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $args)).'</span>';
            }
        }
    }

    /**
     * Add OP name to start of discussion meta on categories pages
     *
     * @param categoriesController $sender
     * @param array $args
     */
    public function categoriesController_afterDiscussionLabels_handler($sender, $args) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            if (val('FirstUser', $args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $args)).'</span>';
            }
        }
    }

    /**
     * Show user photos on discussions pages.
     *
     * @param discussionsController $sender
     */
    public function discussionsController_beforeDiscussionContent_handler($sender) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            $this->displayPhoto($sender);
        }
    }

    /**
     * Show user photos on categories pages.
     *
     * @param categoriesController $sender
     */
    public function categoriesController_beforeDiscussionContent_handler($sender) {
        if (c('Vanilla.Discussions.Layout') != 'table') {
            $this->displayPhoto($sender);
        }
    }

    /**
     * Display user photo for first user in each discussion.
     */
    protected function displayPhoto($sender) {
        // Build user object & output photo
        $firstUser = userBuilder($sender->EventArguments['Discussion'], 'First');
        echo userPhoto($firstUser, ['LinkClass' => 'IndexPhoto']);
    }
}
