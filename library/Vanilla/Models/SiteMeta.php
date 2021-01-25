<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\RequestInterface;
use Gdn_Session;
use UserModel;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Search\SearchService;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Theme\ThemeService;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Formatting\FormatService;

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

    /** @var bool */
    private $conversationsEnabled;

    /** @var string */
    private $siteTitle;

    /** @var UserModel $userModel */
    private $userModel;

    /** @var string[] */
    private $allowedExtensions;

    /** @var int */
    private $maxUploadSize;

    /** @var int */
    private $maxUploads;

    /** @var string */
    private $localeKey;


    /** @var ThemeService */
    private $themeService;

    /** @var string */
    private $activeThemeKey;

    /** @var int $activeThemeRevisionID */
    private $activeThemeRevisionID;

    /** @var string */
    private $mobileThemeKey;

    /** @var string */
    private $desktopThemeKey;

    /** @var string */
    private $activeThemeViewPath;

    /** @var ThemeFeatures */
    private $themeFeatures;

    /** @var array $themePreview */
    private $themePreview;

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

    /** @var Gdn_Session */
    private $session;

    /** @var string */
    private $reCaptchaKey = '';

    /** @var FormatService */
    private $formatService;

    /** @var bool */
    private $supportsSearchScope;

    /** @var string */
    private $defaultSearchScope;

    /**
     * SiteMeta constructor.
     *
     * @param RequestInterface $request The request to gather data from.
     * @param Contracts\ConfigurationInterface $config The configuration object.
     * @param SiteSectionModel $siteSectionModel
     * @param DeploymentCacheBuster $deploymentCacheBuster
     * @param ThemeFeatures $themeFeatures
     * @param ThemeService $themeService
     * @param Gdn_Session $session
     * @param FormatService $formatService
     * @param UserModel $userModel
     * @param AddonManager $addonManager
     * @param SearchService $searchService
     */
    public function __construct(
        RequestInterface $request,
        Contracts\ConfigurationInterface $config,
        SiteSectionModel $siteSectionModel,
        DeploymentCacheBuster $deploymentCacheBuster,
        ThemeFeatures $themeFeatures,
        ThemeService $themeService,
        Gdn_Session $session,
        FormatService $formatService,
        UserModel $userModel,
        AddonManager $addonManager,
        SearchService $searchService
    ) {
        $this->host = $request->getHost();

        $this->formatService = $formatService;

        // We the roots from the request in the form of "" or "/asd" or "/asdf/asdf"
        // But never with a trailing slash.
        $this->basePath = rtrim('/'.trim($request->getRoot(), '/'), '/');
        $this->assetPath = rtrim('/'.trim($request->getAssetRoot(), '/'), '/');
        $this->debugModeEnabled = $config->get('Debug');
        $this->translationDebugModeEnabled  = $config->get('TranslationDebug');
        $this->conversationsEnabled = $addonManager->isEnabled('conversations', Addon::TYPE_ADDON);

        $this->featureFlags = $config->get('Feature', []);
        $this->themeFeatures = $themeFeatures;

        $this->currentSiteSection = $siteSectionModel->getCurrentSiteSection();

        // Get some ui metadata
        // This title may become knowledge base specific or may come down in a different way in the future.
        // For now it needs to come from some where, so I'm putting it here.
        $this->siteTitle = $this->formatService->renderPlainText($config->get('Garden.Title', ""), HtmlFormat::FORMAT_KEY);

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

        $this->session = $session;
        $this->userModel = $userModel;

        //Sign Out URL
        $this->signOutUrl = $session->isValid() ? signOutUrl() : null;

        // Theming
        $currentTheme = $themeService->getCurrentTheme();
        $currentThemeAddon = $themeService->getCurrentThemeAddon();

        $this->activeThemeKey = $currentTheme->getThemeID();
        $this->activeThemeRevisionID = $currentTheme->getRevisionID() ?? null;
        $this->activeThemeViewPath = $currentThemeAddon->path('/views/');
        $this->mobileThemeKey = $config->get('Garden.MobileTheme', 'Garden.Theme');
        $this->desktopThemeKey = $config->get('Garden.Theme', ThemeService::FALLBACK_THEME_KEY);
        $this->themePreview = $themeService->getPreviewTheme();
        $this->defaultSearchScope = $config->get('Search.DefaultScope', 'site');
        $this->supportsSearchScope = (bool) $config->get('Search.SupportsScope', false);

        if ($favIcon = $config->get("Garden.FavIcon")) {
            $this->favIcon = \Gdn_Upload::url($favIcon);
        }

        if ($logo = $config->get("Garden.Logo")) {
            $this->logo = \Gdn_Upload::url($logo);
        }

        if ($shareImage = $config->get("Garden.ShareImage")) {
            $this->shareImage = \Gdn_Upload::url($shareImage);
        }

        $this->bannerImage = BannerImageModel::getCurrentBannerImageLink() ?: null;

        $this->mobileAddressBarColor = $config->get("Garden.MobileAddressBarColor", null);

        $this->reCaptchaKey = $config->get("RecaptchaV3.PublicKey", '');
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
                'conversationsEnabled' => $this->conversationsEnabled,
                'cacheBuster' => $this->cacheBuster,
                'staticPathFolder' => $this->staticPathFolder,
                'dynamicPathFolder' => $this->dynamicPathFolder,
            ],
            'ui' => [
                'siteName' => $this->siteTitle,
                'orgName' => $this->orgName,
                'localeKey' => $this->localeKey,
                'themeKey' => $this->activeThemeKey,
                'mobileThemeKey' => $this->mobileThemeKey,
                'desktopThemeKey' => $this->desktopThemeKey,
                'logo' => $this->logo,
                'favIcon' => $this->favIcon,
                'shareImage' => $this->shareImage,
                'bannerImage' => $this->bannerImage,
                'mobileAddressBarColor' => $this->mobileAddressBarColor,
                'fallbackAvatar' => UserModel::getDefaultAvatarUrl(),
                'currentUser' => $this->userModel->currentFragment(),
            ],
            'search' => [
                'defaultScope' => $this->defaultSearchScope,
                'supportsScope' => $this->supportsSearchScope,
            ],
            'upload' => [
                'maxSize' => $this->maxUploadSize,
                'maxUploads' => $this->maxUploads,
                'allowedExtensions' => $this->allowedExtensions,
            ],
            'signOutUrl' => $this->signOutUrl,
            'featureFlags' => $this->featureFlags,
            'themeFeatures' => $this->themeFeatures->allFeatures(),
            'siteSection' => $this->currentSiteSection,
            'themePreview' => $this->themePreview,
            'reCaptchaKey' => $this->reCaptchaKey,
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
     * @return string
     */
    public function getActiveThemeKey(): string {
        return $this->activeThemeKey;
    }

    /**
     * @return int
     */
    public function getActiveThemeRevisionID(): ?int {
        return $this->activeThemeRevisionID;
    }

    /**
     * @return string
     */
    public function getActiveThemeViewPath(): string {
        return $this->activeThemeViewPath;
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
