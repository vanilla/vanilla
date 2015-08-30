<?php
/**
 * Toggle menu module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Class ToggleMenuModule.
 */
class ToggleMenuModule extends Gdn_Module {

    /** @var array  */
    private $_Labels = array();

    /** @var bool  */
    private $_CurrentLabelCode = false;

    /**
     *
     *
     * @param $Name
     * @param string $Code
     * @param string $Url
     */
    public function addLabel($Name, $Code = '', $Url = '') {
        if ($Code == '') {
            $Code = Gdn_Format::url(ucwords(trim(Gdn_Format::plainText($Name))));
        }

        $this->_Labels[] = array('Name' => $Name, 'Code' => $Code, 'Url' => $Url);
    }

    /**
     *
     *
     * @param string $Label
     * @return bool|string
     */
    public function currentLabelCode($Label = '') {
        if ($Label != '') {
            $this->_CurrentLabelCode = $Label;
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
        $Return = '<ul class="FilterMenu ToggleMenu">';
        foreach ($this->_Labels as $Label) {
            $Url = val('Url', $Label, '');
            if ($Url == '') {
                $Url = '#';
            }

            $Name = val('Name', $Label, '');
            $Code = val('Code', $Label, '');
            $Active = strcasecmp($Code, $this->CurrentLabelCode()) == 0;
            $CssClass = 'Handle-'.$Code;
            $AnchorClass = '';
            if ($Active) {
                $CssClass .= ' Active';
                $AnchorClass = 'TextColor';
            }

            $Return .= '<li class="'.$CssClass.'">';
            $Return .= anchor($Name, $Url, $AnchorClass);
            $Return .= '</li>';
        }
        $Return .= '</ul>';
        return $Return;
    }
}
