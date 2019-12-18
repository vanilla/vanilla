<?php
/**
 *
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Themes\Keystone;

use Vanilla\Web\TwigFormWrapper;
use Vanilla\Web\TwigRenderTrait;

/**
 * Class KeystoneThemeHooks
 */
class FoundationThemeHooks extends \Gdn_Plugin {

    use TwigRenderTrait;

    /**
     * Runs every page load
     *
     * @param \Gdn_Controller $sender This could be any controller
     *
     * @return void
     */
    public function base_render_before($sender) {
        $themeOptions = c("Garden.ThemeOptions.Options", []);
        $sender->setData('hasAdvancedSearch', class_exists(\AdvancedSearchPlugin::class));

        foreach ($themeOptions as $key => &$value) {
            $sender->setData("themeOptions", $themeOptions);
        }
    }

    /**
     * Add custom toggles "hasHeroBanner", "hasFeatureSearchbox", "panelToLeft" to Theme Options
     *
     * @param \SettingsController $sender
     *
     * @return void
     */
    public function settingsController_afterCustomStyles_handler($sender) {
        $form = $sender->Form;
        echo $this->renderTwig("@keystone/customStyles.twig", ['form' => new TwigFormWrapper($form)]);
    }

    /**
     * Add support to `hasHeroBanner`, `hasFeatureSearchbox` and `panelToLeft` custom fields
     *
     * @param \SettingsController $sender
     */
    public function settingsController_themeOptions_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $form = $sender->Form;

        $sender->addJsFile('addons.js');
        $sender->setHighlightRoute('dashboard/settings/themeoptions');

        $themeManager = \Gdn::themeManager();
        $sender->setData('ThemeInfo', $themeManager->enabledThemeInfo());
        $sender->setData('hasAdvancedSearch', class_exists('AdvancedSearchPlugin'));

        //get toggle values from config
        $checkboxes = c("Garden.ThemeOptions.Options");

        foreach ($checkboxes as $key => $value) {
            $form->setValue("ThemeOptions.Options.".$key, $value);
        }

        if ($form->authenticatedPostBack()) {
            // Save the styles to the config.
            $styleKey = $form->getFormValue('StyleKey');

            $configSaveData = [
                'Garden.ThemeOptions.Styles.Key' => $styleKey,
                'Garden.ThemeOptions.Styles.Value' => $sender->data("ThemeInfo.Options.Styles.$styleKey.Basename")];

            // Save the text to the locale.
            foreach ($sender->data('ThemeInfo.Options.Text', []) as $key => $default) {
                $value = $form->getFormValue($form->escapeFieldName('Text_'.$key));
                $configSaveData["ThemeOption.{$key}"] = $value;
            }

            foreach ($form->_FormValues["Checkboxes"] as $key => $fieldName) {
                $value = $form->getFormValue($fieldName) === false ? false : true;
                $configSaveData["Garden.{$fieldName}"] = $value;
            }

            saveToConfig($configSaveData);
            $sender->informMessage(t("Your changes have been saved."));
        }

        $sender->setData('ThemeOptions', c('Garden.ThemeOptions'));
        $styleKey = $sender->data('ThemeOptions.Styles.Key');

        if (!$form->isPostBack()) {
            foreach ($sender->data('ThemeInfo.Options.Text', []) as $key => $options) {
                $default = val('Default', $options, '');
                $value = c("ThemeOption.{$key}", '#DEFAULT#');
                if ($value === '#DEFAULT#') {
                    $value = $default;
                }

                $form->setValue($form->escapeFieldName('Text_'.$key), $value);
            }
        }

        $sender->setData('ThemeFolder', $themeManager->getEnabledDesktopThemeKey());
        $sender->title(t('Theme Options'));
        $form->addHidden('StyleKey', $styleKey);

        $sender->render();
    }
}
