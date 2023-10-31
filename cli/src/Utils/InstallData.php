<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Utils;

use Vanilla\Utility\StringUtils;

/**
 * Class representing persistent data for the cli.
 */
class InstallData implements \JsonSerializable
{
    use ScriptLoggerTrait;
    private array $data = [];

    /** @var string */
    private string $filePath;

    /**
     * Constructor. Fetches from file path or creates empty data.
     *
     * @param ?string $filePath The file path of the install data.
     */
    public function __construct(string $filePath = null)
    {
        $this->filePath = $path = $filePath ?? PATH_ROOT . "/cli/config.json";
        if (file_exists($path)) {
            $installData = file_get_contents($path);
            $installData = json_decode(trim($installData), true);
            if (!$installData) {
                throw new \Exception("Invalid install data in install file '$path'.");
            }
            $this->data = $installData;
            $this->logger()->debug("Found existing install data at '$path'.");
        } else {
            $this->logger()->info("<yellow>No cli config was found. A new one will be created.</yellow>");
        }
    }

    /**
     * Get a value.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function set(string $key, $value)
    {
        $isUpdate = $value !== $this->get($key);
        $this->data[$key] = $value;
        if ($isUpdate) {
            $this->persist();
        }
    }

    /**
     * Persist any changes to the install file.
     */
    private function persist()
    {
        $json = StringUtils::jsonEncodeChecked($this) . "\n";
        file_put_contents($this->filePath, $json);
    }

    /**
     * @inheritdoc
     */
    // Needed until PHP 8.0 in prod, then we can have a `mixed` return type.
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }
}
