<?php
/**
 * Provides a way to widgetize modules.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /module endpoint.
 */
class ModuleController extends Gdn_Controller {

    /**
     * Creates and renders an instance of a module.
     *
     * @param string $Module
     * @param string $AppFolder
     * @param string $DeliveryType
     * @throws NotFoundException
     */
    public function index($Module, $AppFolder = '', $DeliveryType = '') {
        if (!$DeliveryType) {
            $this->deliveryType(DELIVERY_TYPE_VIEW);
        }

        $ModuleClassExists = class_exists($Module);

        if ($ModuleClassExists) {
            // Make sure that the class implements Gdn_IModule
            $ReflectionClass = new ReflectionClass($Module);
            if ($ReflectionClass->implementsInterface("Gdn_IModule")) {
                // Set the proper application folder on this controller so that things render properly.
                if ($AppFolder) {
                    $this->ApplicationFolder = $AppFolder;
                } else {
                    $Filename = str_replace('\\', '/', substr($ReflectionClass->getFileName(), strlen(PATH_ROOT)));
                    // Figure our the application folder for the module.
                    $Parts = explode('/', trim($Filename, '/'));
                    if ($Parts[0] == 'applications') {
                        $this->ApplicationFolder = $Parts[1];
                    }
                }


                $ModuleInstance = new $Module($this);
                $ModuleInstance->Visible = true;

                $WhiteList = array('Limit', 'Help');
                foreach ($this->Request->get() as $Key => $Value) {
                    if (in_array($Key, $WhiteList)) {
                        $ModuleInstance->$Key = $Value;
                    }
                }

                $this->setData('_Module', $ModuleInstance);
                $this->render('Index', false, 'dashboard');
                return;
            }
        }
        throw notFoundException(htmlspecialchars($Module));
    }
}
