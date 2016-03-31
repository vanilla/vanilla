<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license GNU GPLv2
 */

/**
 * Class Pocket
 */
class Pocket {

    const ENABLED = 0;
    const DISABLED = 1;
    const TESTING = 2;

    const REPEAT_BEFORE = 'before';
    const REPEAT_AFTER = 'after';
    const REPEAT_ONCE = 'once';
    const REPEAT_EVERY = 'every';
    const REPEAT_INDEX = 'index';

    const TYPE_AD = 'ad';
    const TYPE_DEFAULT = 'default';

    /** $var string The text to display in the pocket. */
    public $Body = '';

    /** @var int Whether or not the pocket is disabled. The pocket can also be in testing-mode. */
    public $Disabled = Pocket::ENABLED;

    /** @var string The format of the pocket. */
    public $Format = 'Raw';

    /** $var string The location on the page to display the pocket. */
    public $Location;

    /** $var string A descriptive name for the pocket to help keep it organized. */
    public $Name = '';

    /** $var string The name of the page to put the pocket on. */
    public $Page = '';

    /** $var string How the pocket repeats on the page. */
    public $RepeatType = Pocket::REPEAT_INDEX;

    /** $var array The repeat frequency. */
    public $RepeatFrequency = array(1);

    /** $var array The repeat frequency. */
    public $MobileOnly = false;

    /** $var array The repeat frequency. */
    public $MobileNever = false;

    /** $var string Pocket type */
    public $Type;

    /** $var bool Whether to disable the pocket for embedded comments. * */
    public $EmbeddedNever = false;

    /** @var bool  */
    public $ShowInDashboard = false;

    /** @var array */
    public static $NameTranslations = array('conversations' => 'inbox', 'messages' => 'inbox', 'categories' => 'discussions', 'discussion' => 'comments');

    /**
     * Pocket constructor.
     *
     * @param string $Location
     */
    public function __construct($Location = '') {
        $this->Location = $Location;
    }

    /**
     * Whether or not this pocket should be processed based on its state.
     *
     * @param array $Data Data specific to the request.
     * @return bool
     */
    public function canRender($Data) {
        if (!$this->ShowInDashboard && inSection('Dashboard')) {
            return false;
        }

        $IsMobile = isMobile();
        if (($this->MobileOnly && !$IsMobile) || ($this->MobileNever && $IsMobile)) {
            return false;
        }

        if ($this->isAd() && checkPermission('Garden.NoAds.Allow')) {
            return false;
        }

        if ($this->EmbeddedNever && strcasecmp(Gdn::controller()->RequestMethod, 'embed') == 0) {
            return false;
        }

        // Check to see if the pocket is enabled.
        switch ($this->Disabled) {
            case Pocket::DISABLED:
                return false;
            case Pocket::TESTING:
                if (!checkPermission('Plugins.Pockets.Manage'))
                    return false;
                break;
        }

        // Check to see if the page matches.
        if ($this->Page && strcasecmp($this->Page, val('PageName', $Data)) != 0) {
            return false;
        }

        // Check to see if this is repeating.
        $Count = val('Count', $Data);
        if ($Count) {
            switch ($this->RepeatType) {
                case Pocket::REPEAT_AFTER:
                    if (strcasecmp($Count, Pocket::REPEAT_AFTER) != 0)
                        return false;
                    break;
                case Pocket::REPEAT_BEFORE:
                    if (strcasecmp($Count, Pocket::REPEAT_BEFORE) != 0)
                        return false;
                    break;
                case Pocket::REPEAT_ONCE:
                    if ($Count != 1)
                        return false;
                    break;
                case Pocket::REPEAT_EVERY:
                    $Frequency = (array)$this->RepeatFrequency;
                    $Every = val(0, $Frequency, 1);
                    if ($Every < 1)
                        $Every = 1;
                    $Begin = val(1, $Frequency, 1);
                    if (($Count % $Every) > 0 || ($Count < $Begin))
                        return false;
                    break;
                case Pocket::REPEAT_INDEX:
                    if (!in_array($Count, (array)$this->RepeatFrequency))
                        return false;
                    break;
            }
        }

        // If we've passed all of the tests then the pocket can be processed.
        return true;
    }

    /**
     * Load the pocket's data from an array.
     *
     * @param array $Data
     */
    public function load($Data) {
        $this->Body = $Data['Body'];
        $this->Disabled = $Data['Disabled'];
        $this->Format = $Data['Format'];
        $this->Location = $Data['Location'];
        $this->Name = $Data['Name'];
        $this->Page = $Data['Page'];
        $this->MobileOnly = $Data['MobileOnly'];
        $this->MobileNever = $Data['MobileNever'];
        $this->Type = val('Type', $Data, Pocket::TYPE_DEFAULT);
        $this->EmbeddedNever = val('EmbeddedNever', $Data);
        $this->ShowInDashboard = val('ShowInDashboard', $Data);

        // parse the frequency.
        $Repeat = $Data['Repeat'];
        list($this->RepeatType, $this->RepeatFrequency) = Pocket::parseRepeat($Repeat);
    }

    /**
     * Determine whether the pocket is of type 'ad'.
     *
     * @return bool
     */
    public function isAd() {
        return $this->Type == Pocket::TYPE_AD;
    }

    /**
     *
     *
     * @param null $NameOrObject
     * @return mixed|null|string
     */
    public static function pageName($NameOrObject = null) {
        if (is_object($NameOrObject))
            $Name = val('PageName', $NameOrObject, val('ControllerName', $NameOrObject, get_class($NameOrObject)));
        else
            $Name = $NameOrObject;

        $Name = strtolower($Name);
        if (stringEndsWith($Name, 'controller', false))
            $Name = substr($Name, 0, -strlen('controller'));

        if (array_key_exists($Name, self::$NameTranslations))
            $Name = self::$NameTranslations[$Name];
        return $Name;
    }

    /**
     *
     *
     * @param $Repeat
     * @return array
     */
    public static function parseRepeat($Repeat) {
        if (StringBeginsWith($Repeat, Pocket::REPEAT_EVERY)) {
            $RepeatType = Pocket::REPEAT_EVERY;
            $Frequency = substr($Repeat, strlen(Pocket::REPEAT_EVERY));
        } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_INDEX)) {
            $RepeatType = Pocket::REPEAT_INDEX;
            $Frequency = substr($Repeat, strlen(Pocket::REPEAT_INDEX));
        } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_ONCE)) {
            $RepeatType = Pocket::REPEAT_ONCE;
        } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_BEFORE)) {
            $RepeatType = Pocket::REPEAT_BEFORE;
        } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_AFTER)) {
            $RepeatType = Pocket::REPEAT_AFTER;
        }

        if (isset($Frequency)) {
            $Frequency = explode(',', $Frequency);
            $Frequency = array_map('trim', $Frequency);
        } else {
            $Frequency = array();
        }

        return array($RepeatType, $Frequency);
    }

    /**
     * Render the pocket to the page.
     *
     *  @param array $Data additional data for the pocket.
     */
    public function render($Data = NULL) {
        echo $this->toString($Data);
    }

    /** Set the repeat of the pocket.
     *
     *  @param string $Type The repeat type, contained in the various Pocket::REPEAT_* constants.
     *    - every: Repeats every x times. If $Frequency is an array then it will be interpretted as array($Frequency, $Begin).
     *    - indexes: Renders only at the given indexes, starting at 1.
     *  @param int|array $Frequency The frequency of the repeating, see the $Type parameter for how this works.
     */
    public function repeat($Type, $Frequency) {
        $this->RepeatType = $Type;
        $this->RepeatFrequency = $Frequency;
    }

    /**
     *
     *
     * @param null $Data
     * @return mixed|string
     * @throws Exception
     */
    public function toString($Data = NULL) {
        static $Plugin;
        if (!isset($Plugin)) {
            $Plugin = Gdn::pluginManager()->getPluginInstance('PocketsPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
        }

        $Plugin->EventArguments['Pocket'] = $this;
        $Plugin->fireEvent('ToString');

        if (strcasecmp($this->Format, 'raw') == 0) {
            return $this->Body;
        } else {
            return Gdn_Format::to($this->Body, $this->Format);
        }
    }

    /**
     *
     *
     * @param $Name
     * @param $Value
     */
    public static function touch($Name, $Value) {
        $Model = new Gdn_Model('Pocket');
        $Pockets = $Model->getWhere(array('Name' => $Name))->resultArray();

        if (empty($Pockets)) {
            $Pocket = array(
                'Name' => $Name,
                'Location' => 'Content',
                'Sort' => 0,
                'Repeat' => Pocket::REPEAT_BEFORE,
                'Body' => $Value,
                'Format' => 'Raw',
                'Disabled' => Pocket::DISABLED,
                'MobileOnly' => 0,
                'MobileNever' => 0,
                'EmbeddedNever' => 0,
                'ShowInDashboard' => 0,
                'Type' => 'default'
                );
            $Model->save($Pocket);
        }
    }

}
