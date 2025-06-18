<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Container\Container;
use Garden\Web\RequestInterface;
use Gdn_Session;
use UserModel;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Widgets\SiteTotalsWidget;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Site\OwnSite;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Theme\ThemeService;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Formatting\FormatService;
use Vanilla\Web\RoleTokenFactory;

/**
 * A class for gathering particular data about the site.
 */
class SiteMeta implements \JsonSerializable
{
    /** @var SiteMetaExtra[] */
    private $extraMetas = [];

    /**
     * SiteMeta constructor.
     */
    public function __construct(
        private Container $container,
        private RequestInterface $request,
        private Contracts\ConfigurationInterface $config,
        private Gdn_Session $session,
        private AddonManager $addonManager,
        private OwnSite $site,
        private SiteSectionModel $siteSectionModel,
        private ThemeService $themeService,
        private FormatService $formatService,
        private UserModel $userModel
    ) {
    }

    private function getRoleTokenEncoded(): string
    {
        if ($this->session->isValid()) {
            $roleIDs = $this->userModel->getRoleIDs($this->session->UserID);
            if (!empty($roleIDs)) {
                $factory = $this->container->get(RoleTokenFactory::class);
                $roleToken = $factory->forEncoding($roleIDs);
                return $roleToken->encode();
            }
        }
        return "";
    }

    /**
     * Add an extra meta to the site meta.
     *
     * Notably `SiteMeta` is often used as a singleton, so extas given here will apply everywhere.
     * if you want a localized instance use the `$localizedExtraMetas` param when fetching the value.
     *
     * @param SiteMetaExtra $extra
     */
    public function addExtra(SiteMetaExtra $extra)
    {
        $this->extraMetas[] = $extra;
    }

    /**
     * Return array for json serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->value();
    }

    /**
     * Make a method call and catch any throwables it provides.
     *
     * @param callable $fn
     * @param mixed $fallback
     *
     * @return mixed
     */
    private function tryWithFallback(callable $fn, $fallback)
    {
        try {
            return call_user_func($fn);
        } catch (\Throwable $t) {
            logException($t);
            return $fallback;
        }
    }

    /**
     * Get the value of the site meta.
     *
     * @param SiteMetaExtra[] $localizedExtraMetas Extra metas for this one specific fetch of the value.
     * Since `SiteMeta` is often used as a singleton, `SiteMeta::addExtra` will apply globally.
     * By passing extra metas here they can be used for one specific instance.
     *
     * @return array
     */
    public function value(array $localizedExtraMetas = []): array
    {
        $extras = array_map(function (SiteMetaExtra $extra) {
            try {
                return $extra->getValue();
            } catch (\Throwable $throwable) {
                ErrorLogger::error(
                    "Failed to load site meta   value for class " . get_class($extra),
                    ["siteMeta"],
                    [
                        "exception" => $throwable,
                    ]
                );
                return [];
            }
        }, array_merge($this->extraMetas, $localizedExtraMetas));

        $embedAllowValue = $this->config->get("Garden.Embed.Allow", false);
        $hasNewEmbed = FeatureFlagHelper::featureEnabled("newEmbedSystem");

        $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();

        $siteSectionSlugs = [];
        foreach ($this->siteSectionModel->getAll() as $siteSection) {
            if ($basePath = $siteSection->getBasePath()) {
                $siteSectionSlugs[] = $basePath;
            }
        }

        $defaultSiteSection = $this->siteSectionModel->getDefaultSiteSection();

        // Deferred for performance reasons.
        $themeFeatures = \Gdn::getContainer()->get(ThemeFeatures::class);
        $activeSearchDriver = $this->config->get("Vanilla.Search.Driver", "MySQL");

        $currentTheme = $this->themeService->getCurrentTheme();
        $activeThemeKey = $currentTheme->getThemeID();
        $mobileThemeKey = $this->config->get("Garden.MobileTheme", "Garden.Theme");
        $desktopThemeKey = $this->config->get("Garden.Theme", ThemeService::FALLBACK_THEME_KEY);
        $themePreview = $this->themeService->getPreviewTheme();

        return array_replace_recursive(
            [
                "context" => [
                    "version" => APPLICATION_VERSION,
                    "requestID" => $this->request->getMeta("requestID"),
                    "host" => $this->getAssetPath(), // Notably not the actual host.
                    "basePath" => $this->getBasePath(),
                    "assetPath" => $this->getAssetPath(),
                    "debug" => $this->getDebugModeEnabled(),
                    "translationDebug" => $this->config->get("TranslationDebug"),
                    "conversationsEnabled" => $this->addonManager->isEnabled("conversations", Addon::TYPE_ADDON),
                    "cacheBuster" => $this->container->get(DeploymentCacheBuster::class)->value(),
                    "siteID" => $this->site->getSiteID(),
                ],
                "embed" => [
                    "enabled" => (bool) $embedAllowValue,
                    "isAdvancedEmbed" => !$hasNewEmbed && $embedAllowValue === 2,
                    "isModernEmbed" => $hasNewEmbed,
                    "forceModernEmbed" => (bool) $this->config->get("Garden.Embed.ForceModernEmbed", false),
                    "remoteUrl" => $this->config->get("Garden.Embed.RemoteUrl", null),
                ],
                "ui" => [
                    "siteName" => $this->getSiteTitle(),
                    "orgName" => $this->getOrgName(),
                    "localeKey" => $this->getLocaleKey(),
                    "themeKey" => $activeThemeKey,
                    "mobileThemeKey" => $mobileThemeKey,
                    "desktopThemeKey" => $desktopThemeKey,
                    "logo" => $this->getLogo(),
                    "favIcon" => $this->getFavIcon(),
                    "shareImage" => $this->getShareImage(),
                    "bannerImage" => BannerImageModel::getCurrentBannerImageLink() ?: null,
                    "mobileAddressBarColor" => $this->getMobileAddressBarColor(),
                    "fallbackAvatar" => UserModel::getDefaultAvatarUrl(),
                    "currentUser" => $this->userModel->currentFragment(),
                    "editContentTimeout" => intval($this->config->get("Garden.EditContentTimeout")),
                    "bannedPrivateProfile" => $this->config->get("Vanilla.BannedUsers.PrivateProfiles", false),
                    "useAdminCheckboxes" => boolval($this->config->get("Vanilla.AdminCheckboxes.Use", false)),
                    "autoOffsetComments" => boolval($this->config->get("Vanilla.Comments.AutoOffset", true)),
                    "allowSelfDelete" => boolval($this->config->get("Vanilla.Comments.AllowSelfDelete", false)),
                    "isDirectionRTL" => $this->getDirectionRTL(),
                    "userMentionsEnabled" => boolval($this->config->get("Garden.Mentions.Enabled", true)),
                ],
                "search" => [
                    "defaultScope" => $this->config->get("Search.DefaultScope", "site"),
                    "supportsScope" =>
                        (bool) $this->config->get("Search.SupportsScope", false) &&
                        $activeSearchDriver === "ElasticSearch",
                    "activeDriver" => $activeSearchDriver,
                    "externalSearch" => [
                        "query" => $this->config->get("Garden.ExternalSearch.Query", false),
                        "resultsInNewTab" => $this->config->get("Garden.ExternalSearch.ResultsInNewTab", false),
                    ],
                ],
                "upload" => [
                    "maxSize" => $this->getMaxUploadSize(),
                    "maxUploads" => (int) $this->config->get(
                        "Garden.Upload.maxFileUploads",
                        ini_get("max_file_uploads")
                    ),
                    "allowedExtensions" => $this->getAllowedExtensions(),
                ],
                "signatures" => [
                    "enabled" => (bool) $this->config->get("EnabledPlugins.Signatures"),
                    "hideMobile" => (bool) $this->config->get("Signatures.Hide.Mobile"),
                    "imageMaxHeight" => (int) $this->config->get("Signatures.Images.MaxHeight", 0),
                ],

                "siteTotals" => [
                    "availableOptions" => SiteTotalsWidget::getRecordOptions(),
                ],

                // In case there is a some failure here we don't want the site to crash.
                "registrationUrl" => $this->tryWithFallback("registerUrl", ""),
                "signInUrl" => $this->tryWithFallback("signInUrl", ""),
                "signOutUrl" => $this->tryWithFallback("signOutUrl", ""),
                "featureFlags" => $this->config->get("Feature", []),
                "themeFeatures" => $themeFeatures->allFeatures(),
                "addonFeatures" => $themeFeatures->allAddonFeatures(),
                "defaultSiteSection" => $defaultSiteSection->jsonSerialize(),
                "siteSection" => $currentSiteSection->jsonSerialize(),
                "siteSectionSlugs" => $siteSectionSlugs,
                "themePreview" => $themePreview,
                "reCaptchaKey" => $this->config->get("RecaptchaV3.PublicKey", ""),
                "TransientKey" => $this->session->transientKey(),
                "roleToken" => $this->getRoleTokenEncoded(),
                "isConfirmEmailRequired" => $this->config->get("Garden.Registration.ConfirmEmail", true),
            ],
            ...$extras
        );
    }

    /**
     * @return string
     */
    public function getSiteTitle(): string
    {
        return $this->formatService->renderPlainText($this->config->get("Garden.Title", ""), HtmlFormat::FORMAT_KEY);
    }

    /**
     * @return string
     */
    public function getOrgName(): string
    {
        return $this->config->get("Garden.OrgName") ?: $this->getSiteTitle();
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->request->getHost();
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return rtrim("/" . trim($this->request->getRoot(), "/"), "/");
    }

    /**
     * @return string
     */
    public function getAssetPath(): string
    {
        return rtrim("/" . trim($this->request->getAssetRoot(), "/"), "/");
    }

    /**
     * @return bool
     */
    public function getDebugModeEnabled(): bool
    {
        return $this->config->get("Debug");
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions(): array
    {
        $extensions = $this->config->get("Garden.Upload.AllowedFileExtensions", []);
        if ($this->session->getPermissions()->has("Garden.Community.Manage")) {
            $extensions = array_merge($extensions, \MediaApiController::UPLOAD_RESTRICTED_ALLOWED_FILE_EXTENSIONS);
        }
        return $extensions;
    }

    /**
     * @return int
     */
    private function getMaxUploadSize(): int
    {
        // Fetch Uploading metadata.
        $maxSize = $this->config->get("Garden.Upload.MaxFileSize", ini_get("upload_max_filesize"));
        return \Gdn_Upload::unformatFileSize($maxSize);
    }

    /**
     * @return string
     */
    public function getLocaleKey(): string
    {
        return $this->siteSectionModel->getCurrentSiteSection()->getContentLocale();
    }

    /**
     * Get the configured "favorite icon" for the site.
     *
     * @return string|null
     */
    public function getFavIcon(): ?string
    {
        if ($favIcon = $this->config->get("Garden.FavIcon")) {
            return \Gdn_Upload::url($favIcon);
        }
        return null;
    }

    /**
     * Get the configured "Share Image" for the site.
     *
     * @return string|null
     */
    public function getShareImage(): ?string
    {
        $shareImage = $this->config->get("Garden.ShareImage");
        if (!empty($shareImage)) {
            return \Gdn_Upload::url($shareImage);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        if ($logo = $this->config->get("Garden.Logo")) {
            return \Gdn_Upload::url($logo);
        }
        return null;
    }

    /**
     * Get the configured "theme color" for the site.
     *
     * @return string|null
     */
    public function getMobileAddressBarColor(): ?string
    {
        return $this->config->get("Garden.MobileAddressBarColor", null);
    }

    /**
     * @return bool
     */
    public function getDirectionRTL(): bool
    {
        return in_array($this->getLocaleKey(), \LocaleModel::getRTLLocales()) &&
            in_array($this->getLocaleKey(), $this->config->get("Garden.RTLLocales", []));
    }
}
