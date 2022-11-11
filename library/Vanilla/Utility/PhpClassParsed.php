<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Data object representing a class in an Addon.
 */
final class PhpClassParsed
{
    /** @var string */
    public $className;

    /** @var string */
    private $filePath;

    /** @var string|null */
    public $addonKey;

    /**
     * Constructor.
     *
     * @param string $className
     * @param string $filePath
     * @param string|null $addonKey
     */
    public function __construct(string $className, string $filePath = "", ?string $addonKey = null)
    {
        $this->className = $className;
        // Make sure we trim off the application root.
        $this->filePath = str_replace(PATH_ROOT . "/", "", $filePath);
        $this->addonKey = $addonKey;
    }

    /**
     * Get the namespace of the class string.
     *
     * @return string|null Null if the class has no namespace.
     */
    public function getNamespace(): ?string
    {
        $pieces = explode("\\", $this->className);
        array_pop($pieces);
        if (count($pieces) === null) {
            return null;
        }
        $namespace = implode("\\", $pieces) . "\\";
        return $namespace;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return PATH_ROOT . "/" . $this->filePath;
    }

    /**
     * @return string
     */
    public function getShortClassName(): string
    {
        $pieces = explode("\\", $this->className);
        return array_pop($pieces);
    }

    /**
     * @param string|null $addonKey
     */
    public function setAddonKey(?string $addonKey): void
    {
        $this->addonKey = $addonKey;
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return PhpClassParsed Returns a new addon with the properties from {@link $array}.
     */
    public static function __set_state(array $array)
    {
        return new PhpClassParsed($array["className"] ?? "", $array["filePath"] ?? "", $array["addonKey"] ?? null);
    }
}
