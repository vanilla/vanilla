<?php
/**
 * Hero Image Plugin.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

/**
 * Hero Image Plugin.
 */
class HeroImagePlugin extends Gdn_Plugin {

    const DEFAULT_CONFIG_KEY = "Garden.HeroImage";
    const SETTINGS_URL = 'settings/heroimage';

    /**
     * This will run when you "Enable" the plugin.
     *
     * @return void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Runs structure.php on /utility/update and on enabling the plugin.
     *
     * @return void
     */
    public function structure() {
        Gdn::structure()
            ->table('Category')
            ->column("HeroImage", 'varchar(255)', true)
            ->set();
    }

    /**
     * Get the slug of the banner image for a given category
     *
     * Categories will inherit their parents CategoryBanner if they don't have
     * their own set. If no BannerImage can be found the default from the config will be returned
     *
     * @param int $categoryID Set an explicit category.
     *
     * @return string The category's slug on success, the default otherwise.
     */
    public static function getHeroImageSlug($categoryID) {
        $categoryID = filter_var($categoryID, FILTER_VALIDATE_INT);
        if (!$categoryID || $categoryID < 1) {
            return c(self::DEFAULT_CONFIG_KEY);
        }

        $category = CategoryModel::instance()->getID($categoryID);
        $slug = val("HeroImage", $category);

        if (!$slug) {
            $parentID = val('ParentCategoryID', $category);
            $slug = self::getHeroImageSlug($parentID);
        }
        return $slug;
    }

    /**
     * Get a fully qualified HeroImage link for the current category.
     *
     * @return string The URL to the category image. Will be empty if no slug could be found.
     */
    public static function getCurrentHeroImageLink() {
        $categoryID = valr('Category.CategoryID', Gdn::controller());

        $imageSlug = self::getHeroImageSlug($categoryID);
        $url = $imageSlug ? Gdn_Upload::url($imageSlug) : '';
        return $url;
    }

    /**
     * Handle the postback for the additional form field
     *
     * @param SettingsController $sender The settings controller
     *
     * @return void
     */
    public function settingsController_addEditCategory_handler($sender) {
        $categoryID = val('CategoryID', $sender->Data);
        if ($sender->Form->authenticatedPostBack()) {
            $upload = new Gdn_Upload();
            $tmpImage = $upload->validateUpload('HeroImage_New', false);
            if ($tmpImage) {
                // Generate the target image name
                $targetImage = $upload->generateTargetName(PATH_UPLOADS);
                $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

                // Save the uploaded image
                $parts = $upload->saveAs(
                    $tmpImage,
                    $imageBaseName
                );
                $sender->Form->setFormValue("HeroImage", $parts['SaveName']);
            }
        }
    }


    /**
     * Add additional image upload input to the category page form.
     *
     * @param VanillaSettingsController $sender The controller for the settings page.
     *
     * @return void
     */
    public function vanillaSettingsController_afterCategorySettings_handler($sender) {
        echo $sender->Form->imageUploadPreview(
            "HeroImage",
            t('Hero Image'),
            t('The hero image displayed at the top of each page.'),
            'vanilla/settings/deleteheroimage/'.$sender->Category->CategoryID
        );
    }


    /**
     * Endpoints for deleting the extra category image from the category
     *
     * @param VanillaSettingsController $sender The controller for the settings page.
     * @param string $categoryID The id of the category being loaded (comes from url param)
     *
     * @return void
     */
    public function vanillaSettingsController_deleteHeroImage_create($sender, $categoryID = '') {
        // Check permission
        $sender->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        if ($categoryID && Gdn::request()->isAuthenticatedPostBack(true)) {
            // Do removal, set message
            $categoryModel = CategoryModel::instance();
            $categoryModel->setField($categoryID, "HeroImage", null);
            $sender->informMessage(t('Hero image was successfully deleted.'));
        }

        $sender->RedirectUrl = '/vanilla/settings/categories';
        $sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Create the configuration page for the plugin
     *
     * @param VanillaSettingsController $sender The settings controller
     *
     * @return void
     */
    public function settingsController_heroImage_create($sender) {
        $sender->permission('Garden.Community.Manage');
        $sender->setHighlightRoute(self::SETTINGS_URL);
        $sender->title(t('Hero Image'));
        $configurationModule = new ConfigurationModule($sender);
        $configurationModule->initialize([
            self::DEFAULT_CONFIG_KEY => [
                'LabelCode' => t('Default Hero Image'),
                'Control' => 'imageupload',
                'Description' => t('LogoDescription', 'The default hero image across the site. This can be overriden on a per category basis.'),
                'Options' => [
                    'RemoveConfirmText' => sprintf(t('Are you sure you want to delete your %s?'), t('hero image'))
                ]
            ]
        ]);
        $sender->setData('ConfigurationModule', $configurationModule);
        $configurationModule->renderAll();
    }

    /**
     * Hook the Smarty init to add our directory containing our custom Smarty functions
     *
     * @param object $sender Smarty object.
     *
     * @return void
     */
    public function gdn_smarty_init_handler($sender) {
        $sender->addPluginsDir(paths('plugins', $this->getPluginIndex(), 'SmartyPlugins'));
    }
}
