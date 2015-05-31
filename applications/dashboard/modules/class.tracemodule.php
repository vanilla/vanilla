<?php
/**
 * Trace module.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Assist with debugging.
 *
 * @see Trace()
 */
class TraceModule extends Gdn_Module {

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    public function AssetTarget() {
        return 'Content';
    }

    public function ToString() {
        try {
            $Traces = Trace();
            if (!$Traces)
                return '';

            $this->SetData('Traces', $Traces);

            return $this->FetchView();
        } catch (Exception $Ex) {
            return $Ex->getMessage();
        }
    }
}
