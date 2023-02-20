<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker;

use Vanilla\Utility\StringUtils;

/**
 * Class representing data about the current docker installation.
 */
class InstallData implements \JsonSerializable
{
    private const FILE_PATH = PATH_ROOT . "/docker/install.json";
    private array $data = [];

    /**
     * DI.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Load data from the install file on the system.
     *
     * @return InstallData
     */
    public static function fromInstallFile(): InstallData
    {
        $path = self::FILE_PATH;
        if (!file_exists($path)) {
            throw new InstallNotFoundException();
        }

        $installData = file_get_contents($path);
        $installData = json_decode(trim($installData), true);
        if (!$installData) {
            throw new \Exception("Invalid install data in install file '$path'.");
        }

        return new InstallData($installData);
    }

    /**
     * Persist any changes to the install file.
     */
    public function persist()
    {
        $json = StringUtils::jsonEncodeChecked($this) . "\n";
        file_put_contents(self::FILE_PATH, $json);
    } // Needed until PHP 8.0 in prod and we can have a `mixed` return type.

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function areLogsSetup(): bool
    {
        return $this->data["areLogsSetup"] ?? false;
    }

    /**
     * @param bool $areLogsSetup
     * @return void
     */
    public function setLogsSetup(bool $areLogsSetup): void
    {
        $this->data["areLogsSetup"] = $areLogsSetup;
    }

    /**
     * @return bool
     */
    public function wasDbMigrated(): bool
    {
        return $this->data["wasDbMigrated"] ?? false;
    }

    /**
     * @param bool $wasDbMigrated
     * @return void
     */
    public function setDbMigrated(bool $wasDbMigrated): void
    {
        $this->data["wasDbMigrated"] = $wasDbMigrated;
    }

    /**
     * @return bool
     */
    public function wasQueueCloned(): bool
    {
        return $this->data["wasQueueCloned"] ?? false;
    }

    /**
     * @param bool $wasQueueCloned
     * @return void
     */
    public function setQueueCloned(bool $wasQueueCloned): void
    {
        $this->data["wasQueueCloned"] = $wasQueueCloned;
    }
}
