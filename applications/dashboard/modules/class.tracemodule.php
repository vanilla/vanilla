<?php
/**
 * Trace module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Assist with debugging.
 *
 * @see trace()
 */
class TraceModule extends Gdn_Module {

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    public function assetTarget() {
        return 'Content';
    }

    public function toString() {
        try {
            $Traces = trace();
            if (!$Traces) {
                return '';
            }

            $this->setData('Traces', $Traces);

            return $this->fetchView();
        } catch (Exception $Ex) {
            return $Ex->getMessage();
        }
    }
}
