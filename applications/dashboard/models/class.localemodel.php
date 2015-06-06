<?php
/**
 * Locale model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Used to manage adding/removing different locale files.
 */
class LocaleModel {

    /** @var array|null Locales in the system.  */
    protected $_AvailableLocalePacks = null;

    /**
     *
     *
     * @return array|null
     */
    public function AvailableLocalePacks() {
        if ($this->_AvailableLocalePacks === null) {
            $LocaleInfoPaths = SafeGlob(PATH_ROOT."/locales/*/definitions.php");
            $AvailableLocales = array();
            foreach ($LocaleInfoPaths as $InfoPath) {
                $LocaleInfo = Gdn::PluginManager()->ScanPluginFile($InfoPath, 'LocaleInfo');
                $this->CalculateLocaleInfo($LocaleInfo);
                $AvailableLocales[$LocaleInfo['Index']] = $LocaleInfo;
            }
            $this->_AvailableLocalePacks = $AvailableLocales;
        }
        return $this->_AvailableLocalePacks;
    }

    /**
     *
     *
     * @return array
     */
    public function AvailableLocales() {
        // Get the list of locales that are supported.
        $Locales = array_column($this->AvailableLocalePacks(), 'Locale', 'Locale');
        $Locales['en'] = 'en'; // the default locale is always available.
        ksort($Locales);

        return $Locales;
    }

    /**
     *
     *
     * @param $info
     */
    protected function CalculateLocaleInfo(&$info) {
        $canonicalLocale = Gdn_Locale::Canonicalize($info['Locale']);
        if ($canonicalLocale !== $info['Locale']) {
            $info['LocaleRaw'] = $info['Locale'];
            $info['Locale'] = $canonicalLocale;
        }
    }

    /**
     *
     *
     * @param $SourcePath
     * @param $DestPath
     * @return mixed
     * @throws Exception
     */
    public function CopyDefinitions($SourcePath, $DestPath) {
        // Load the definitions from the source path.
        $Definitions = $this->LoadDefinitions($SourcePath);

        $TmpPath = dirname($DestPath).'/tmp_'.RandomString(10);
        $Key = trim(strchr($SourcePath, '/'), '/');

        $fp = fopen($TmpPath, 'wb');
        if (!$fp) {
            throw new Exception(sprintf(T('Could not open %s.'), $TmpPath));
        }

        fwrite($fp, $this->GetFileHeader());
        fwrite($fp, "/** Definitions copied from $Key. **/\n\n");
        $this->WriteDefinitions($fp, $Definitions);
        fclose($fp);

        $Result = rename($TmpPath, $DestPath);
        if (!$Result) {
            throw new Exception(sprintf(T('Could not open %s.'), $DestPath));
        }
        return $DestPath;
    }

    /**
     *
     *
     * @param bool $GetInfo
     * @return array
     */
    public function EnabledLocalePacks($GetInfo = false) {
        $Result = (array)C('EnabledLocales', array());

        if ($GetInfo) {
            foreach ($Result as $Key => $Locale) {
                $InfoPath = PATH_ROOT."/locales/$Key/definitions.php";
                if (file_exists($InfoPath)) {
                    $LocaleInfo = Gdn::PluginManager()->ScanPluginFile($InfoPath, 'LocaleInfo');
                    $this->CalculateLocaleInfo($LocaleInfo);
                    $Result[$Key] = $LocaleInfo;
                } else {
                    unset($Result[$Key]);
                }
            }
        }

        return $Result;
    }

    /**
     *
     *
     * @param $Path
     * @param null $Skip
     * @return array
     */
    public function LoadDefinitions($Path, $Skip = null) {
        $Skip = (array)$Skip;

        $Paths = SafeGlob($Path.'/*.php');
        $Definition = array();
        foreach ($Paths as $Path) {
            if (in_array($Path, $Skip)) {
                continue;
            }
            include $Path;
        }
        return $Definition;
    }

    /**
     *
     *
     * @param $Path
     * @param $BasePath
     * @param null $DestPath
     * @return null|string
     * @throws Exception
     */
    public function GenerateChanges($Path, $BasePath, $DestPath = null) {
        if ($DestPath == null) {
            $DestPath = $BasePath.'/changes.php';
        }

        // Load the given locale pack.
        $Definitions = $this->LoadDefinitions($Path, $DestPath);
        $BaseDefinitions = $this->LoadDefinitions($BasePath, $DestPath);

        // Figure out the missing definitions.
        $MissingDefinitions = array_diff_key($BaseDefinitions, $Definitions);

        // Figure out the extraneous definitions.
        $ExtraDefinitions = array_diff($Definitions, $BaseDefinitions);

        // Generate the changes file.
        $TmpPath = dirname($BasePath).'/tmp_'.RandomString(10);
        $fp = fopen($TmpPath, 'wb');
        if (!$fp) {
            throw new Exception(sprintf(T('Could not open %s.'), $TmpPath));
        }

        $Key = trim(strchr($Path, '/'), '/');
        $BaseKey = trim(strchr($BasePath, '/'), '/');

        fwrite($fp, $this->GetFileHeader());
        fwrite($fp, "/** Changes file comparing $Key to $BaseKey. **/\n\n\n");

        fwrite($fp, "/** Missing definitions that are in the $BaseKey, but not $Key. **/\n");
        $this->WriteDefinitions($fp, $MissingDefinitions);

        fwrite($fp, "\n\n/** Extra definitions that are in the $Key, but not the $BaseKey. **/\n");
        $this->WriteDefinitions($fp, $ExtraDefinitions);

        fclose($fp);

        $Result = rename($TmpPath, $DestPath);
        if (!$Result) {
            throw new Exception(sprintf(T('Could not open %s.'), $DestPath));
        }
        return $DestPath;
    }

    protected function GetFileHeader() {
        $Now = Gdn_Format::ToDateTime();

        $Result = "<?php if (!defined('APPLICATION')) exit();
/** This file was generated by the LocaleModel on $Now **/\n\n";

        return $Result;
    }

    /**
     * Temporarily enable a locale pack without installing it/
     *
     * @param string $LocaleKey The key of the folder.
     * @throws NotFoundException
     */
    public function TestLocale($LocaleKey) {
        $Available = $this->AvailableLocalePacks();
        if (!isset($Available[$LocaleKey])) {
            throw NotFoundException('Locale');
        }

        // Grab all of the definition files from the locale.
        $Paths = SafeGlob(PATH_ROOT."/locales/{$LocaleKey}/*.php");

        // Unload the dynamic config
        Gdn::Locale()->Unload();

        // Load each locale file, checking for errors
        foreach ($Paths as $Path) {
            Gdn::Locale()->Load($Path, false);
        }
    }

    /**
     * Write a locale's definitions to a file.
     *
     * @param resource $fp The file to write to.
     * @param array $Definitions The definitions to write.
     */
    public static function WriteDefinitions($fp, $Definitions) {
        // Write the definitions.
        uksort($Definitions, 'strcasecmp');
        $LastC = '';
        foreach ($Definitions as $Key => $Value) {
            // Add a blank line between letters of the alphabet.
            if (isset($Key[0]) && strcasecmp($LastC, $Key[0]) != 0) {
                fwrite($fp, "\n");
                $LastC = $Key[0];
            }

            $Str = '$Definition['.var_export($Key, true).'] = '.var_export($Value, true).";\n";
            fwrite($fp, $Str);
        }
    }
}
