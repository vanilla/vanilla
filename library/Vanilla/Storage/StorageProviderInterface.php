<?php

namespace Vanilla\Storage;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;

/**
 * Describes the interface for a storage provider.
 *
 * Interface StorageProviderInterface
 * @package Vanilla\Storage
 */
class StorageProviderInterface
{
    const STORAGE_TYPE = "";

    /**
     * @return array
     */
    public function getUrls(): array
    {
        return [];
    }

    /**
     * @param array $parsedFile
     * @return string|null
     */
    public function copyLocal(array $parsedFile): string|null
    {
        return null;
    }

    /**
     * @param array $parsedFile
     * @return void
     */
    public function delete(array $parsedFile): void
    {
    }

    /**
     * @param string $sourcePath
     * @param string $target
     * @param array $options
     * @return array
     */
    public function saveAs(string $sourcePath, string $target, array $options = []): array
    {
        return [];
    }

    /**
     * Set the configuration for the storage provider.
     *
     * @param array $configs
     * @return void
     */
    public function setConfig(array $configs = []): void
    {
    }

    /**
     * Check if the config is set up correctly.
     *
     * @return bool
     */
    public function checkConfig(): bool
    {
        return true;
    }
}
