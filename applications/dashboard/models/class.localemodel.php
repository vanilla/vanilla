<?php
/**
 * Locale model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public function availableLocalePacks() {
        if ($this->_AvailableLocalePacks === null) {
            $localeInfoPaths = safeGlob(PATH_ROOT."/locales/*/definitions.php");
            $availableLocales = [];
            foreach ($localeInfoPaths as $infoPath) {
                $localeInfo = Gdn::pluginManager()->scanPluginFile($infoPath, 'LocaleInfo');
                $this->calculateLocaleInfo($localeInfo);


                if ($icon = val('Icon', $localeInfo)) {
                    $localeInfo['IconUrl'] = "/locales/".basename(dirname($infoPath))."/$icon";
                } else {
                    $localeInfo['IconUrl'] = '/applications/dashboard/design/images/addon-placeholder.png';
                }

                if ($enName = val('EnName', $localeInfo)) {
                    $localeInfo['meta'][] = $enName;
                }

                $availableLocales[$localeInfo['Index']] = $localeInfo;
            }
            $this->_AvailableLocalePacks = $availableLocales;
        }
        return $this->_AvailableLocalePacks;
    }

    /**
     *
     *
     * @return array
     */
    public function availableLocales() {
        // Get the list of locales that are supported.
        $locales = array_column($this->availableLocalePacks(), 'Locale', 'Locale');
        $locales['en'] = 'en'; // the default locale is always available.
        ksort($locales);

        return $locales;
    }

    /**
     *
     *
     * @param $info
     */
    protected function calculateLocaleInfo(&$info) {
        $canonicalLocale = Gdn_Locale::canonicalize($info['Locale']);
        if ($canonicalLocale !== $info['Locale']) {
            $info['LocaleRaw'] = $info['Locale'];
            $info['Locale'] = $canonicalLocale;
        }
    }

    /**
     *
     *
     * @param $sourcePath
     * @param $destPath
     * @return mixed
     * @throws Exception
     */
    public function copyDefinitions($sourcePath, $destPath) {
        // Load the definitions from the source path.
        $definitions = $this->loadDefinitions($sourcePath);

        $tmpPath = dirname($destPath).'/tmp_'.randomString(10);
        $key = trim(strchr($sourcePath, '/'), '/');

        $fp = fopen($tmpPath, 'wb');
        if (!$fp) {
            throw new Exception(sprintf(t('Could not open %s.'), $tmpPath));
        }

        fwrite($fp, $this->getFileHeader());
        fwrite($fp, "/** Definitions copied from $key. **/\n\n");
        $this->writeDefinitions($fp, $definitions);
        fclose($fp);

        $result = rename($tmpPath, $destPath);
        if (!$result) {
            throw new Exception(sprintf(t('Could not open %s.'), $destPath));
        }
        return $destPath;
    }

    /**
     *
     *
     * @param bool $getInfo
     * @return array
     */
    public function enabledLocalePacks($getInfo = false) {
        $result = (array)c('EnabledLocales', []);

        if ($getInfo) {
            foreach ($result as $key => $locale) {
                $infoPath = PATH_ROOT."/locales/$key/definitions.php";
                if (file_exists($infoPath)) {
                    $localeInfo = Gdn::pluginManager()->scanPluginFile($infoPath, 'LocaleInfo');
                    $this->calculateLocaleInfo($localeInfo);
                    $result[$key] = $localeInfo;
                } else {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }

    /**
     *
     *
     * @param $Path
     * @param null $Skip
     * @return array
     */
    public function loadDefinitions($Path, $Skip = null) {
        $Skip = (array)$Skip;

        $Paths = safeGlob($Path.'/*.php');
        $Definition = [];
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
     * @param $path
     * @param $basePath
     * @param null $destPath
     * @return null|string
     * @throws Exception
     */
    public function generateChanges($path, $basePath, $destPath = null) {
        if ($destPath == null) {
            $destPath = $basePath.'/changes.php';
        }

        // Load the given locale pack.
        $definitions = $this->loadDefinitions($path, $destPath);
        $baseDefinitions = $this->loadDefinitions($basePath, $destPath);

        // Figure out the missing definitions.
        $missingDefinitions = array_diff_key($baseDefinitions, $definitions);

        // Figure out the extraneous definitions.
        $extraDefinitions = array_diff($definitions, $baseDefinitions);

        // Generate the changes file.
        $tmpPath = dirname($basePath).'/tmp_'.randomString(10);
        $fp = fopen($tmpPath, 'wb');
        if (!$fp) {
            throw new Exception(sprintf(t('Could not open %s.'), $tmpPath));
        }

        $key = trim(strchr($path, '/'), '/');
        $baseKey = trim(strchr($basePath, '/'), '/');

        fwrite($fp, $this->getFileHeader());
        fwrite($fp, "/** Changes file comparing $key to $baseKey. **/\n\n\n");

        fwrite($fp, "/** Missing definitions that are in the $baseKey, but not $key. **/\n");
        $this->writeDefinitions($fp, $missingDefinitions);

        fwrite($fp, "\n\n/** Extra definitions that are in the $key, but not the $baseKey. **/\n");
        $this->writeDefinitions($fp, $extraDefinitions);

        fclose($fp);

        $result = rename($tmpPath, $destPath);
        if (!$result) {
            throw new Exception(sprintf(t('Could not open %s.'), $destPath));
        }
        return $destPath;
    }

    protected function getFileHeader() {
        $now = Gdn_Format::toDateTime();

        $result = "<?php if (!defined('APPLICATION')) exit();
/** This file was generated by the LocaleModel on $now **/\n\n";

        return $result;
    }

    /**
     * Temporarily enable a locale pack without installing it/
     *
     * @param string $localeKey The key of the folder.
     * @throws NotFoundException
     */
    public function testLocale($localeKey) {
        $available = $this->availableLocalePacks();
        if (!isset($available[$localeKey])) {
            throw notFoundException('Locale');
        }

        // Grab all of the definition files from the locale.
        $paths = safeGlob(PATH_ROOT."/locales/{$localeKey}/*.php");

        // Unload the dynamic config
        Gdn::locale()->unload();

        // Load each locale file, checking for errors
        foreach ($paths as $path) {
            Gdn::locale()->load($path, false);
        }
    }

    /**
     * Write a locale's definitions to a file.
     *
     * @param resource $fp The file to write to.
     * @param array $definitions The definitions to write.
     */
    public static function writeDefinitions($fp, $definitions) {
        // Write the definitions.
        uksort($definitions, 'strcasecmp');
        $lastC = '';
        foreach ($definitions as $key => $value) {
            // Add a blank line between letters of the alphabet.
            if (isset($key[0]) && strcasecmp($lastC, $key[0]) != 0) {
                fwrite($fp, "\n");
                $lastC = $key[0];
            }

            $str = '$Definition['.var_export($key, true).'] = '.var_export($value, true).";\n";
            fwrite($fp, $str);
        }
    }
}
