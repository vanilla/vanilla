<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use Garden\Http\HttpResponse;

/**
 * A PageScraper class, limited to local files.
 */
class LocalFilePageScraper extends \Vanilla\PageScraper {

    /** @var string */
    private $htmlDir;

    /**
     * Get the configured HTML directory.
     */
    public function getHtmlDir(): ?string {
        return $this->htmlDir;
    }

    /**
     * Load a file from the file system as a successful HTTP response.
     *
     * @param string $relativePath Path to the file, relative to the configured HTML directory.
     * @return HttpResponse
     */
    protected function getUrl(string $relativePath): HttpResponse {
        if ($this->htmlDir === null) {
            throw new \RuntimeException("HTML directory has not been configured.");
        }

        $fullPath = realpath("{$this->htmlDir}/{$relativePath}");
        if ($fullPath === false) {
            throw new \InvalidArgumentException("File does not exist.");
        }

        if (pathinfo($fullPath, PATHINFO_DIRNAME) !== $this->htmlDir) {
            throw new \RuntimeException("File is not in the HTML directory.");
        }

        if (is_readable($fullPath) === false) {
            throw new \RuntimeException("Unable to read the file.");
        }

        $content = file_get_contents($fullPath);
        $result = new HttpResponse(200, "", $content);
        return $result;
    }

    /**
     * Set the HTML file directory.
     *
     * @param string $dir
     * @return self
     */
    public function setHtmlDir(string $dir): self {
        $dir = rtrim($dir, "\\/");
        if (file_exists($dir) === false) {
            throw new \InvalidArgumentException("Directory does not exist.");
        }
        if (is_dir($dir) === false) {
            throw new \InvalidArgumentException("Filename is not a directory.");
        }

        $this->htmlDir = $dir;
        return $this;
    }
}
