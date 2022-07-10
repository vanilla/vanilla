<?php
/**
 * Provides a way to widgetize modules.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /module endpoint.
 */
class ModuleController extends Gdn_Controller {
    /**
     * {@inheritDoc}
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('global.js');
        $this->addJsFile('cropimage.js');
        $this->addJsFile('vendors/clipboard.min.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        parent::initialize();
    }

    /**
     * Creates and renders an instance of a module.
     *
     * @param string $module
     * @param string $appFolder
     * @param string $deliveryType
     */
    public function index($module, $appFolder = '', $deliveryType = '') {
        if (!$deliveryType) {
            $this->deliveryType(DELIVERY_TYPE_VIEW);
        }

        $moduleClassExists = class_exists($module);

        if ($moduleClassExists) {
            // Make sure that the class implements Gdn_IModule
            $reflectionClass = new ReflectionClass($module);
            if ($reflectionClass->implementsInterface("Gdn_IModule")) {
                // Check any incoming app folder against real application list.
                $appWhitelist = Gdn::applicationManager()->enabledApplicationFolders();

                // Set the proper application folder on this controller so that things render properly.
                if ($appFolder && in_array($appFolder, $appWhitelist)) {
                    $this->ApplicationFolder = $appFolder;
                } else {
                    $filename = str_replace('\\', '/', substr($reflectionClass->getFileName(), strlen(PATH_ROOT)));
                    // Figure our the application folder for the module.
                    $parts = explode('/', trim($filename, '/'));
                    if ($parts[0] == 'applications' && in_array($parts[1], $appWhitelist)) {
                        $this->ApplicationFolder = $parts[1];
                    }
                }


                $moduleInstance = new $module($this);
                $moduleInstance->Visible = true;

                $whiteList = ['Limit', 'Help'];
                foreach ($this->Request->get() as $key => $value) {
                    if (in_array($key, $whiteList)) {
                        // Set a sane max limit for this open-ended way of calling modules.
                        if ($key == 'Limit' && $value > 200) {
                            throw new Exception(t('Invalid limit.'), 400);
                        }
                        $moduleInstance->$key = $value;
                    }
                }

                $this->setData('_Module', $moduleInstance);
                $this->render('Index', false, 'dashboard');
                return;
            }
        }
        throw notFoundException(htmlspecialchars($module));
    }
}
