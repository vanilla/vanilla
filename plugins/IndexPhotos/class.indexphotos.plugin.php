<?php
/**
 * IndexPhotos Plugin.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package IndexPhotos
 */

use Vanilla\Theme\BoxThemeShim;

/**
 * Class IndexPhotosPlugin
 *
 * @todo Just make this a core option on the Avatars page.
 */
class IndexPhotosPlugin extends Gdn_Plugin {

    use \Garden\StaticCacheConfigTrait;

    /**
     * We need extra styling on the discussion list view.
     *
     * @param \Vanilla\Web\Asset\LegacyAssetModel $sender
     */
    public function assetModel_styleCss_handler($sender) {
        if (self::c('Vanilla.Discussions.Layout') != 'table' && !BoxThemeShim::isActive()) {
            $sender->addCssFile('indexphotos.css', 'plugins/IndexPhotos');
        }
    }

    /**
     * Add OP name to start of discussion meta on discussions pages.
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionsController_afterDiscussionLabels_handler($sender, $args) {
        if (self::c('Vanilla.Discussions.Layout') != 'table') {
            if (val('FirstUser', $args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $args)).'</span>';
            }
        }
    }

    /**
     * Add OP name to start of discussion meta on categories pages
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_afterDiscussionLabels_handler($sender, $args) {
        if (self::c('Vanilla.Discussions.Layout') != 'table') {
            if (val('FirstUser', $args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $args)).'</span>';
            }
        }
    }

    /**
     * Show user photos on discussions pages.
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionsController_beforeDiscussionContent_handler($sender, array $args) {
        if (self::c('Vanilla.Discussions.Layout') != 'table') {
            $this->displayPhoto($sender, $args);
        }
    }

    /**
     * Show user photos on categories pages.
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_beforeDiscussionContent_handler($sender, array $args) {
        if (self::c('Vanilla.Discussions.Layout') != 'table') {
            $this->displayPhoto($sender, $args);
        }
    }

    /**
     * Add CSSClass on discussions pages.
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionsController_beforeDiscussionName_handler($sender, array $args) {
        if (self::c('Vanilla.Discussions.Layout') != 'table') {
            $this->addCSSClass($args);
        }
    }

    /**
     * Add CSSClass on categories pages.
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_beforeDiscussionName_handler($sender, array $args) {
        if (self::c('Vanilla.Discussions.Layout') != 'table') {
            $this->addCSSClass($args);
        }
    }

    /**
     * Update the CSS class for the base `beforeDiscussionName` event.
     *
     * @param array $args
     */
    private function addCSSClass(array $args) {
        $cssClass = $args['CssClass'] ?? '';
        $cssClass .= ' ItemDiscussion-withPhoto';
        $args['CssClass'] = $cssClass;
    }

    /**
     * Display user photo for first user in each discussion.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    protected function displayPhoto($sender, array $args) {
        // Build user object & output photo
        $firstUser = userBuilder($sender->EventArguments['Discussion'], 'First');
        echo userPhoto($firstUser, ['LinkClass' => 'IndexPhoto']);
    }
}
