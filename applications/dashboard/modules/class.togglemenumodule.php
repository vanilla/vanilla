<?php
/**
 * Toggle menu module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Class ToggleMenuModule.
 */
class ToggleMenuModule extends Gdn_Module {

    /** @var array  */
    private $_Labels = [];

    /** @var bool  */
    private $_CurrentLabelCode = false;

    /**
     *
     *
     * @param $name
     * @param string $code
     * @param string $url
     */
    public function addLabel($name, $code = '', $url = '') {
        if ($code == '') {
            $code = Gdn_Format::url(ucwords(trim(Gdn_Format::plainText($name))));
        }

        $this->_Labels[] = ['Name' => $name, 'Code' => $code, 'Url' => $url];
    }

    /**
     *
     *
     * @param string $label
     * @return bool|string
     */
    public function currentLabelCode($label = '') {
        if ($label != '') {
            $this->_CurrentLabelCode = $label;
        }

        // If the current code hasn't been assigned, use the first available label
        if (!$this->_CurrentLabelCode && count($this->_Labels) > 0) {
            return $this->_Labels[0]['Code'];
        }

        return $this->_CurrentLabelCode;
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        $return = '<ul class="FilterMenu ToggleMenu">';
        foreach ($this->_Labels as $label) {
            $url = val('Url', $label, '');
            if ($url == '') {
                $url = '#';
            }

            $name = val('Name', $label, '');
            $code = val('Code', $label, '');
            $active = strcasecmp($code, $this->currentLabelCode()) == 0;
            $cssClass = 'Handle-'.$code;
            $anchorClass = '';
            if ($active) {
                $cssClass .= ' Active';
                $anchorClass = 'TextColor';
            }

            $return .= '<li class="'.$cssClass.'">';
            $return .= anchor($name, $url, $anchorClass);
            $return .= '</li>';
        }
        $return .= '</ul>';
        return $return;
    }
}
