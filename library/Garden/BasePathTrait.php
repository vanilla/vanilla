<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

/**
 * Basic trait for managing base path testing.
 */
trait BasePathTrait {

    /** @var string */
    protected $basePath;

    /**
     * Get the base path.
     *
     * @return string|null
     */
    public function getBasePath(): ?string {
        return $this->basePath;
    }

    /**
     * Is a given path inside the configured base path?
     *
     * @param string $path
     * @return bool
     */
    protected function inBasePath(string $path): bool {
        $result = strcasecmp(substr($path, 0, strlen($this->basePath)), $this->basePath) === 0;
        return $result;
    }

    /**
     * Set the base path.
     *
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void {
        $this->basePath = $basePath;
    }
}
