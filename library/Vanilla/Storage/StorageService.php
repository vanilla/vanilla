<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license proprietary
 */

namespace Vanilla\Storage;

use Exception;
use Gdn;

/**
 * Service for managing storage providers.
 *
 * Class StorageService
 * @package Vanilla\Storage
 */
class StorageService
{
    /**
     * @var StorageProviderInterface[]
     */
    private array $storageProviders = [];

    /**
     * StorageService constructor.
     */
    public function __construct()
    {
    }

    /**
     * Add a storage provider to the service.
     *
     * @param StorageProviderInterface $storageProvider
     * @return void
     */
    public function addProvider(StorageProviderInterface $storageProvider): void
    {
        $this->storageProviders[] = $storageProvider;
    }

    /**
     * Loops through registered storage providers until a valid provider is found.
     *
     * @return StorageProviderInterface|void
     * @throws Exception
     */
    public function getStorage(): ?StorageProviderInterface
    {
        // Check if a provider is already picked from the configs.
        $selectedProvider = Gdn::config("Garden.Storage.Provider") ?? false;

        // If a provider is selected and is available, we will use it.
        if ($selectedProvider) {
            foreach ($this->storageProviders as $provider) {
                if ($provider::STORAGE_TYPE === $selectedProvider) {
                    $provider->setConfig();
                    return $provider;
                }
            }
        }
    }
}
