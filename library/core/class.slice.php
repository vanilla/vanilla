<?php
/**
 * Slice manager: views.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Allows views to implement small asynchronously refreshable portions of the page - slices.
 */
class Gdn_Slice {

    /** @var Gdn_Dispatcher */
    protected $Dispatcher;

    /**
     *
     */
    public function __construct() {
        $this->Dispatcher = new Gdn_Dispatcher();
        $EnabledApplications = Gdn::Config('EnabledApplications');
        $this->Dispatcher->EnabledApplicationFolders($EnabledApplications);
        $this->Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);
    }

    /**
     *
     *
     * @return string
     */
    public function Execute() {
        $SliceArgs = func_get_args();
        switch (count($SliceArgs)) {
            case 1:
                //die('slice request: '.$SliceArgs[0]);
                $Request = Gdn::Request()->Create()
                    ->FromEnvironment()
                    ->WithURI($SliceArgs[0])
                    ->WithDeliveryType(DELIVERY_TYPE_VIEW);

                ob_start();
                $this->Dispatcher->Dispatch($Request, FALSE);
                return ob_get_clean();

                break;
            case 2:

                break;
        }
    }

}

