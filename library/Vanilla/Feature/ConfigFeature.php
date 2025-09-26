<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Feature;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\InjectableInterface;

/**
 * Feature determined by if a config has true boolean value.
 */
class ConfigFeature extends Feature implements InjectableInterface
{
    private ConfigurationInterface $config;

    public function __construct(string $featureID, private string $configKey)
    {
        parent::__construct($featureID);
    }

    /**
     * @DI.
     */
    public function setDependencies(ConfigurationInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return (bool) $this->config->get($this->configKey);
    }
}
