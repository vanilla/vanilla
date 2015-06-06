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
    public function AssetModel_StyleCss_Handler($Sender) {
        if (!$this->hasLayoutTables() || IsMobile()) {
            $Sender->AddCssFile('indexphotos.css', 'plugins/IndexPhotos');
        }
    }

    /**
     * Add OP name to start of discussion meta.
     */
    public function DiscussionsController_AfterDiscussionLabels_Handler($Sender, $Args) {
        if (!$this->hasLayoutTables() || IsMobile()) {
            if (GetValue('FirstUser', $Args)) {
                echo '<span class="MItem DiscussionAuthor">'.UserAnchor(GetValue('FirstUser', $Args)).'</span>';
            }
        }
    }

    /**
     *
     *
     * @param $Sender
     * @param $Args
     */
    public function CategoriesController_AfterDiscussionLabels_Handler($Sender, $Args) {
        if (!$this->hasLayoutTables() || IsMobile()) {
            if (GetValue('FirstUser', $Args)) {
                echo '<span class="MItem DiscussionAuthor">'.UserAnchor(GetValue('FirstUser', $Args)).'</span>';
            }
        }
    }

    /**
     * Trigger on All Discussions.
     */
    public function DiscussionsController_BeforeDiscussionContent_Handler($Sender) {
        if (!$this->hasLayoutTables() || IsMobile()) {
            $this->DisplayPhoto($Sender);
        }
    }

    /**
     * Trigger on Categories.
     */
    public function CategoriesController_BeforeDiscussionContent_Handler($Sender) {
        if (!$this->hasLayoutTables()) {
            $this->DisplayPhoto($Sender);
        }
    }

    /**
     * Display user photo for first user in each discussion.
     */
    protected function DisplayPhoto($Sender) {
        // Build user object & output photo
        $FirstUser = UserBuilder($Sender->EventArguments['Discussion'], 'First');
        echo UserPhoto($FirstUser, array('LinkClass' => 'IndexPhoto'));
    }

    /**
     * Determine whether layout of discussions page is "table" (vs. "modern").
     *
     * @return bool If forum is using table layout, returns true
     */
    public function hasLayoutTables() {
        return (C('Vanilla.Discussions.Layout') == 'table');
    }
}
