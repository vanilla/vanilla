<?php
/**
 * Trace module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
            $traces = trace();
            if (!$traces) {
                return '';
            }

            $this->setData('Traces', $traces);

            return $this->fetchView();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
}
