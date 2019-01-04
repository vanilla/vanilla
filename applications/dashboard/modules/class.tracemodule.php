<?php
/**
 * Trace module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
