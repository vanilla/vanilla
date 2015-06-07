<?php
/**
 * IndexPhotos Plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package IndexPhotos
 */

$PluginInfo['IndexPhotos'] = array(
    'Name' => 'Discussion Photos',
    'Description' => "Displays photo and name of the user who started each discussion anywhere discussions are listed. Note that this plugin will not have any affect when table layouts are enabled.",
    'Version' => '1.2.2',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'RegisterPermissions' => false,
    'MobileFriendly' => true,
    'Author' => "Lincoln Russell",
    'AuthorEmail' => 'lincolnwebs@gmail.com',
    'AuthorUrl' => 'http://lincolnwebs.com'
);

/**
 * Class IndexPhotosPlugin
 */
class IndexPhotosPlugin extends Gdn_Plugin {

    /**
     *
     *
     * @param $Sender
     */
    public function assetModel_styleCss_handler($Sender) {
        if (!$this->hasLayoutTables() || IsMobile()) {
            $Sender->addCssFile('indexphotos.css', 'plugins/IndexPhotos');
        }
    }

    /**
     * Add OP name to start of discussion meta.
     */
    public function discussionsController_afterDiscussionLabels_handler($Sender, $Args) {
        if (!$this->hasLayoutTables() || isMobile()) {
            if (val('FirstUser', $Args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $Args)).'</span>';
            }
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function categoriesController_afterDiscussionLabels_handler($Sender, $Args) {
        if (!$this->hasLayoutTables() || isMobile()) {
            if (val('FirstUser', $Args)) {
                echo '<span class="MItem DiscussionAuthor">'.userAnchor(val('FirstUser', $Args)).'</span>';
            }
        }
    }

    /**
     * Trigger on All Discussions.
     */
    public function discussionsController_beforeDiscussionContent_handler($Sender) {
        if (!$this->hasLayoutTables() || isMobile()) {
            $this->displayPhoto($Sender);
        }
    }

    /**
     * Trigger on Categories.
     */
    public function categoriesController_beforeDiscussionContent_handler($Sender) {
        if (!$this->hasLayoutTables()) {
            $this->displayPhoto($Sender);
        }
    }

    /**
     * Display user photo for first user in each discussion.
     */
    protected function displayPhoto($Sender) {
        // Build user object & output photo
        $FirstUser = userBuilder($Sender->EventArguments['Discussion'], 'First');
        echo userPhoto($FirstUser, array('LinkClass' => 'IndexPhoto'));
    }

    /**
     * Determine whether layout of discussions page is "table" (vs. "modern").
     *
     * @return bool If forum is using table layout, returns true
     */
    public function hasLayoutTables() {
        return (c('Vanilla.Discussions.Layout') == 'table');
    }
}
