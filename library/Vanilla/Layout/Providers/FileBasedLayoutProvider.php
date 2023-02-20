<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Providers;

use Garden\Web\Exception\NotFoundException;
use Vanilla\FileUtils;

/**
 * Provider for default layouts for a layout view type defined as a YAML file and registered during bootstrapping.
 */
class FileBasedLayoutProvider implements LayoutProviderInterface
{
    //region Properties

    /** @var string $cacheBasePath */
    private $cacheBasePath;

    /** @var array{string, string} $layoutPaths */
    private $layoutPaths;

    /** @var ?string $requestedLayoutPath */
    private $requestedLayoutPath;

    /** @var \UserModel */
    private $userModel;

    //endregion

    //region Constructor

    /**
     * Instantiate a file based layout provider
     *
     * @param string $cacheBasePath Base path used to cache parsed static layout definitions
     */
    public function __construct(string $cacheBasePath, \UserModel $userModel)
    {
        $this->cacheBasePath = $cacheBasePath;
        $this->userModel = $userModel;
        $this->layoutPaths = [];
    }

    //endregion

    //region Public Methods

    /**
     * @inheritdoc
     */
    public function isIDFormatSupported($layoutID): bool
    {
        return is_string($layoutID) && !is_numeric($layoutID);
    }

    /**
     * @inheritdoc
     */
    public function getByID($layoutID): array
    {
        if (!isset($this->layoutPaths[$layoutID])) {
            throw new NotFoundException("Layout");
        }
        $cachePath = "{$this->cacheBasePath}/{$layoutID}.php";
        $this->requestedLayoutPath = $this->layoutPaths[$layoutID];
        if (!file_exists($this->cacheBasePath)) {
            @mkdir($this->cacheBasePath, 0755);
        }
        $layout = (array) FileUtils::getCached($cachePath, [$this, "parseRequestedLayoutFile"]);
        $this->requestedLayoutPath = null;
        return $layout;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return array_map(function ($layoutID) {
            return $this->getByID($layoutID);
        }, array_keys($this->layoutPaths));
    }

    /**
     * Register a static layout with the provider and is typically invoked as part of container initialization.
     *
     * @param string $layoutID Layout view type for the static layout
     * @param string $layoutFilePath Path to the file where the static layout is defined.
     */
    public function registerStaticLayout(string $layoutID, string $layoutFilePath): void
    {
        if (file_exists($layoutFilePath)) {
            $this->layoutPaths[$layoutID] = $layoutFilePath;
        }
    }

    /**
     * Specify cache path for layouts generated from a layout definition file.
     *
     * @param string $cacheBasePath
     */
    public function setCacheBasePath(string $cacheBasePath): void
    {
        $this->cacheBasePath = $cacheBasePath;
    }

    /**
     * Specify cache path for layouts generated from a layout definition file.
     *
     * @return string
     */
    public function getCacheBasePath(): string
    {
        return $this->cacheBasePath;
    }

    /**
     * Parse the static layout definition.
     *
     * @return array
     * @throws NotFoundException Requested layout file not found.
     */
    public function parseRequestedLayoutFile(): array
    {
        try {
            if (!isset($this->requestedLayoutPath)) {
                throw new NotFoundException("Layout");
            }
            $contents = FileUtils::getArray($this->requestedLayoutPath);
            $contents["insertUserID"] = $this->userModel->getSystemUserID();
            return $contents;
        } catch (\Exception $e) {
            throw new NotFoundException("Layout");
        }
    }
    //endregion

    //region Non-Public Methods
    //endregion
}
