<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;
use Vanilla\FeatureFlagHelper;
use Vanilla\Addon;

/**
 * A class for gathering particular data about the site.
 */
class SiteMeta implements \JsonSerializable {

    /** @var string */
    private $host;

    /** @var string */
    private $basePath;

    /** @var string */
    private $assetPath;

    /** @var bool */
    private $debugModeEnabled;

    /** @var string */
    private $siteTitle;

    /** @var string[] */
    private $allowedExtensions;

    /** @var int */
    private $maxUploadSize;

    /** @var string */
    private $localeKey;

    /** @var Addon */
    private $activeTheme;

    /** @var string */
    private $favIcon;

    /** @var string */
    private $mobileAddressBarColor;

    /** @var array */
    private $featureFlags;

    /**
     * SiteMeta constructor.
     *
     * @param RequestInterface $request The request to gather data from.
     * @param Contracts\ConfigurationInterface $config The configuration object.
     * @param \Gdn_Locale $locale
     * @param Addon $activeTheme
     */
    public function __construct(RequestInterface $request, Contracts\ConfigurationInterface $config, \Gdn_Locale $locale, Addon $activeTheme) {
        $this->host = $request->getHost();

        // We the roots from the request in the form of "" or "/asd" or "/asdf/asdf"
        // But never with a trailing slash.
        $this->basePath = rtrim('/'.trim($request->getRoot(), '/'), '/');
        $this->assetPath = rtrim('/'.trim($request->getAssetRoot(), '/'), '/');
        $this->debugModeEnabled = $config->get('Debug');

        $this->featureFlags = $config->get('Feature', []);

        // Get some ui metadata
        // This title may become knowledge base specific or may come down in a different way in the future.
        // For now it needs to come from some where, so I'm putting it here.
        $this->siteTitle = $config->get('Garden.Title', "");

        // Fetch Uploading metadata.
        $this->allowedExtensions = $config->get('Garden.Upload.AllowedFileExtensions', []);
        $maxSize = $config->get('Garden.Upload.MaxFileSize', ini_get('upload_max_filesize'));
        $this->maxUploadSize = \Gdn_Upload::unformatFileSize($maxSize);

        // localization
        $this->localeKey = $locale->current();

        // Theming
        $this->activeTheme = $activeTheme;

        if ($favIcon = $config->get("Garden.FavIcon")) {
            $this->favIcon = \Gdn_Upload::url($favIcon);
        }

        $this->mobileAddressBarColor = $config->get("Garden.MobileAddressBarColor", null);
    }

    /**
     * Return array for json serialization.
     */
    public function jsonSerialize(): array {
        return $this->value();
    }

    /**
     * @return array
     */
    public function value(): array {
        return [
            'context' => [
                'host' => $this->assetPath,
                'basePath' => $this->basePath,
                'assetPath' => $this->assetPath,
                'debug' => $this->debugModeEnabled,
            ],
            'ui' => [
                'siteName' => $this->siteTitle,
                'localeKey' => $this->localeKey,
                'themeKey' => $this->activeTheme->getKey(),
                'favIcon' => $this->favIcon,
                'mobileAddressBarColor' => $this->mobileAddressBarColor,
            ],
            'upload' => [
                'maxSize' => $this->maxUploadSize,
                'allowedExtensions' => $this->allowedExtensions,
            ],
            'featureFlags' => $this->featureFlags,
        ];
    }

    /**
     * @return string
     */
    public function getSiteTitle(): string {
        return $this->siteTitle;
    }

    /**
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getBasePath(): string {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getAssetPath(): string {
        return $this->assetPath;
    }

    /**
     * @return bool
     */
    public function getDebugModeEnabled(): bool {
        return $this->debugModeEnabled;
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions(): array {
        return $this->allowedExtensions;
    }

    /**
     * @return int
     */
    public function getMaxUploadSize(): int {
        return $this->maxUploadSize;
    }

    /**
     * @return string
     */
    public function getLocaleKey(): string {
        return $this->localeKey;
    }

    /**
     * @return Addon
     */
    public function getActiveTheme(): Addon {
        return $this->activeTheme;
    }

    /**
     * Get the configured "favorite icon" for the site.
     *
     * @return string|null
     */
    public function getFavIcon(): ?string {
        return $this->favIcon;
    }

    /**
     * Get the configured "theme color" for the site.
     *
     * @return string|null
     */
    public function getMobileAddressBarColor(): ?string {
        return $this->mobileAddressBarColor;
    }
}
