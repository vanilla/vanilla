<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;
use Vanilla\Addon;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\Asset\DeploymentCacheBuster;

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

    /** @var bool */
    private $translationDebugModeEnabled;

    /** @var string */
    private $siteTitle;

    /** @var string[] */
    private $allowedExtensions;

    /** @var int */
    private $maxUploadSize;

    /** @var int */
    private $maxUploads;

    /** @var string */
    private $localeKey;

    /** @var Addon */
    private $activeTheme;

    /** @var string */
    private $favIcon;

    /** @var string */
    private $mobileAddressBarColor;

    /** @var string|null */
    private $shareImage;

    /** @var string|null */
    private $bannerImage;

    /** @var array */
    private $featureFlags;

    /** @var Contracts\Site\SiteSectionInterface */
    private $currentSiteSection;

    /** @var string */
    private $logo;

    /** @var string */
    private $orgName;

    /** @var string */
    private $cacheBuster;

    /** @var string */
    private $staticPathFolder = '';

    /** @var string */
    private $dynamicPathFolder = '';

    /**
     * SiteMeta constructor.
     *
     * @param RequestInterface $request The request to gather data from.
     * @param Contracts\ConfigurationInterface $config The configuration object.
     * @param SiteSectionModel $siteSectionModel
     * @param DeploymentCacheBuster $deploymentCacheBuster
     * @param Contracts\AddonInterface $activeTheme
     */
    public function __construct(
        RequestInterface $request,
        Contracts\ConfigurationInterface $config,
        SiteSectionModel $siteSectionModel,
        DeploymentCacheBuster $deploymentCacheBuster,
        ?Contracts\AddonInterface $activeTheme = null
    ) {
        $this->host = $request->getHost();

        // We the roots from the request in the form of "" or "/asd" or "/asdf/asdf"
        // But never with a trailing slash.
        $this->basePath = rtrim('/'.trim($request->getRoot(), '/'), '/');
        $this->assetPath = rtrim('/'.trim($request->getAssetRoot(), '/'), '/');
        $this->debugModeEnabled = $config->get('Debug');
        $this->translationDebugModeEnabled  = $config->get('TranslationDebug');

        $this->featureFlags = $config->get('Feature', []);

        $this->currentSiteSection = $siteSectionModel->getCurrentSiteSection();

        // Get some ui metadata
        // This title may become knowledge base specific or may come down in a different way in the future.
        // For now it needs to come from some where, so I'm putting it here.
        $this->siteTitle = $config->get('Garden.Title', "");

        $this->orgName = $config->get('Garden.OrgName') ?: $this->siteTitle;

        // Fetch Uploading metadata.
        $this->allowedExtensions = $config->get('Garden.Upload.AllowedFileExtensions', []);
        $maxSize = $config->get('Garden.Upload.MaxFileSize', ini_get('upload_max_filesize'));
        $this->maxUploadSize = \Gdn_Upload::unformatFileSize($maxSize);
        $this->maxUploads = (int)$config->get('Garden.Upload.maxFileUploads', ini_get('max_file_uploads'));

        // localization
        $this->localeKey = $this->currentSiteSection->getContentLocale();

        // DeploymentCacheBuster
        $this->cacheBuster = $deploymentCacheBuster->value();

        // Theming
        $this->activeTheme = $activeTheme;

        if ($favIcon = $config->get("Garden.FavIcon")) {
            $this->favIcon = \Gdn_Upload::url($favIcon);
        }

        if ($logo = $config->get("Garden.Logo")) {
            $this->logo = \Gdn_Upload::url($logo);
        }

        if ($shareImage = $config->get("Garden.ShareImage")) {
            $this->shareImage = \Gdn_Upload::url($shareImage);
        }

        $this->bannerImage = class_exists(BannerImageModel::class) ? BannerImageModel::getCurrentBannerImageLink() ?: null : null;

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
                'translationDebug' => $this->translationDebugModeEnabled,
                'cacheBuster' => $this->cacheBuster,
                'staticPathFolder' => $this->staticPathFolder,
                'dynamicPathFolder' => $this->dynamicPathFolder,
            ],
            'ui' => [
                'siteName' => $this->siteTitle,
                'orgName' => $this->orgName,
                'localeKey' => $this->localeKey,
                'themeKey' => $this->activeTheme ? $this->activeTheme->getKey() : null,
                'logo' => $this->logo,
                'favIcon' => $this->favIcon,
                'shareImage' => $this->shareImage,
                'bannerImage' => $this->bannerImage,
                'mobileAddressBarColor' => $this->mobileAddressBarColor,
            ],
            'upload' => [
                'maxSize' => $this->maxUploadSize,
                'maxUploads' => $this->maxUploads,
                'allowedExtensions' => $this->allowedExtensions,
            ],
            'featureFlags' => $this->featureFlags,
            'siteSection' => $this->currentSiteSection,
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
    public function getOrgName(): string {
        return $this->orgName;
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
    public function getActiveTheme(): ?Contracts\AddonInterface {
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
     * Get the configured "Share Image" for the site.
     *
     * @return string|null
     */
    public function getShareImage(): ?string {
        return $this->shareImage;
    }

    /**
     * @return string
     */
    public function getLogo(): ?string {
        return $this->logo;
    }

    /**
     * Get the configured "theme color" for the site.
     *
     * @return string|null
     */
    public function getMobileAddressBarColor(): ?string {
        return $this->mobileAddressBarColor;
    }

    /**
     * @param string $staticPathFolder
     */
    public function setStaticPathFolder(string $staticPathFolder) {
        $this->staticPathFolder = $staticPathFolder;
    }

    /**
     * @param string $dynamicPathFolder
     */
    public function setDynamicPathFolder(string $dynamicPathFolder) {
        $this->dynamicPathFolder = $dynamicPathFolder;
    }

}
