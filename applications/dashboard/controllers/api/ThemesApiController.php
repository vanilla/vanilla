<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Vanilla\ApiUtils;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\Theme;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Theme\ThemeService;
use Vanilla\ThemingApi\Models\ThemeAssetModel;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Web\CacheControlMiddleware;
use VanillaTests\Fixtures\Request;

/**
 * API Controller for the `/themes` resource.
 */
class ThemesApiController extends AbstractApiController {
    use ThemesApiSchemes;

    // Theming
    const GET_THEME_ACTION = "@@themes/GET_DONE";
    const GET_THEME_VARIABLES_ACTION = "@@themes/GET_VARIABLES_DONE";

    /** @var ThemeService */
    private $themeService;

    /** @var ThemeAssetFactory */
    private $assetFactory;

    /**
     * ThemesApiController constructor.
     *
     * @param ThemeService $themeService
     * @param ThemeAssetFactory $assetFactory
     */
    public function __construct(ThemeService $themeService, ThemeAssetFactory $assetFactory) {
        $this->themeService = $themeService;
        $this->assetFactory = $assetFactory;
    }

    /**
     * Get a theme assets.
     *
     * @param string $themeKey The unique theme key or theme ID.
     * @param array $query
     * @return Data
     */
    public function get(string $themeKey, array $query = []): Data {
        $this->permission();
        $out = $this->themeResultSchema();
        $in = $this->schema([
            'allowAddonVariables:b?',
            'revisionID:i?',
            'expand?' => $this->assetExpandDefinition(),
        ]);
        $params = $in->validate($query);

        if (!($params['allowAddonVariables'] ?? true)) {
            $this->themeService->clearVariableProviders();
        }

        $theme = $this->themeService->getTheme($themeKey, $query);
        $this->handleAssetExpansions($theme, $params['expand']);
        return new Data($theme, ['theme' => $theme], [ 'X-App-Cache-Hit' => $theme->isCacheHit() ? '1' : '0' ]);
    }

    /**
     * Get a theme revisions.
     *
     * @param int $themeID The unique theme key or theme ID.
     * @return array
     */
    public function get_revisions(int $themeID): array {
        $this->permission();
        $in = $this->schema([], 'in');
        $out = $this->schema([":a" => $this->themeResultSchema()]);

        $themeRevisions = $this->themeService->getThemeRevisions($themeID);
        foreach ($themeRevisions as $theme) {
            $this->handleAssetExpansions($theme, false);
        }
        $result = $out->validate($themeRevisions);
        return $result;
    }

    /**
     * Get a theme assets.
     *
     * @param array $query
     *
     * @return array
     */
    public function index(array $query = []): array {
        $this->permission();
        $in = $this->schema([
            'allowAddonVariables:b?',
            'expand?' => $this->assetExpandDefinition(),
        ]);
        $out = $this->schema([":a" => $this->themeResultSchema()]);
        $params = $in->validate($query);

        if (!($params['allowAddonVariables'] ?? true)) {
            $this->themeService->clearVariableProviders();
        }

        $themes = $this->themeService->getThemes();
        foreach ($themes as $theme) {
            $this->handleAssetExpansions($theme, $params['expand']);
        }
        return $themes;
    }

    /**
     * Create new theme.
     *
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return Data
     */
    public function post(array $body): Data {
        $this->permission("Garden.Settings.Manage");

        $in = $this->themePostSchema('in');

        $out = $this->themeResultSchema();

        $body = $in->validate($body);

        $normalizedTheme = $this->themeService->postTheme($body);
        $this->handleAssetExpansions($normalizedTheme, true);
        $theme = $out->validate($normalizedTheme);
        return new Data($theme, ['theme' => $theme]);
    }


    /**
     * Update theme name by ID.
     *
     * @param int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return Data
     */
    public function patch(int $themeID, array $body): Data {
        $this->permission("Garden.Settings.Manage");
        $in = $this->themePatchSchema('in');
        $out = $this->themeResultSchema();
        $body = $in->validate($body);

        $normalizedTheme = $this->themeService->patchTheme($themeID, $body);

        $theme = $out->validate($normalizedTheme);
        $this->handleAssetExpansions($normalizedTheme, true);
        return new Data($theme, ['theme' => $theme]);
    }

    /**
     * Delete theme by ID.
     *
     * @param int $themeID Theme ID
     */
    public function delete(int $themeID) {
        $this->permission("Garden.Settings.Manage");
        $this->themeService->deleteTheme($themeID);
    }

    /**
     * Set theme as "current" theme.
     *
     * @param array $body Array of incoming params.
     *        fields: themeID (required)
     * @return Data
     */
    public function put_current(array $body): Data {
        $this->permission("Garden.Settings.Manage");
        $in = $this->themePutCurrentSchema('in');
        $out = $this->themeResultSchema();
        $body = $in->validate($body);

        $theme = $this->themeService->setCurrentTheme($body['themeID']);
        $this->handleAssetExpansions($theme, true);
        $theme = $out->validate($theme);
        return new Data($theme, ['theme' => $theme]);
    }

    /**
     * Set theme as preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param array $body Array of incoming params.
     *        fields: themeID (required)
     * @return Data
     */
    public function put_preview(array $body): Data {
        $this->permission("Garden.Settings.Manage");
        $in = $this->themePutPreviewSchema('in');
        $out = $this->themeResultSchema();
        $body = $in->validate($body);

        $theme = $this->themeService->setPreviewTheme($body['themeID'] ?? null, $body['revisionID'] ?? null);
        $this->handleAssetExpansions($theme, true);
        $theme = $out->validate($theme);
        return new Data($theme, ['theme' => $theme]);
    }

    /**
     * Get "current" theme.
     *
     * @return Data
     */
    public function get_current(): Data {
        $this->permission();
        $out = $this->themeResultSchema();

        $theme = $this->themeService->getCurrentTheme();
        $this->handleAssetExpansions($theme, true);
        $result = $out->validate($theme);
        return new Data($theme, ['theme' => $theme], [ 'X-App-Cache-Hit' => $theme->isCacheHit() ? '1' : '0' ]);
    }

    /**
     * PUT theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetPath Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param RequestInterface $request The request.
     *
     * @return Data
     */
    public function put_assets(int $themeID, string $assetPath, RequestInterface $request): Data {
        $this->permission("Garden.Settings.Manage");
        $theme = $this->themeService->getTheme($themeID);

        /** @var ThemeAsset $asset */
        [$asset, $assetName] = $this->extractInputAsset($theme, $assetPath, $request);

        // Try to create the asset.
        $this->themeService->setAsset($themeID, $assetName, $asset->__toString());

        return $this->get_assets($themeID, $assetPath);
    }

    /**
     * PATCH theme asset variables.json.
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetPath Asset key.
     * @param RequestInterface $request The request.
     *
     * @return Data
     */
    public function patch_assets(int $themeID, string $assetPath, RequestInterface $request): Data {
        $this->permission("Garden.Settings.Manage");
        $theme = $this->themeService->getTheme($themeID);
        [$asset, $assetName] = $this->extractInputAsset($theme, $assetPath, $request);

        // Try to create the asset.
        $this->themeService->sparseUpdateAsset($themeID, $assetName, $asset->__toString());

        return $this->get_assets($themeID, $assetPath);
    }

    /**
     * DELETE theme asset.
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     */
    public function delete_assets(int $themeID, string $assetKey) {
        $this->permission("Garden.Settings.Manage");

        $theme = $this->themeService->getTheme($themeID);
        /** @var ThemeAsset $asset */
        [$asset, $assetKey, $ext] = $this->extractAssetForPath($theme, $assetKey);
        if (!$asset) {
            throw new NotFoundException('Asset');
        }

        $this->themeService->deleteAsset($themeID, $assetKey);
    }

    /**
     * Get theme asset.
     *
     * @param string $id The unique theme key or theme ID (ex: keystone).
     * @param string $assetKey Unique asset key (ex: header, footer, fonts, styles)
     *        Note: assetKey can be filename (ex: header.html, styles.css)
     *              in that case file content returned instaed of json structure
     * @link https://github.com/vanilla/roadmap/blob/master/theming/theming-data.md#api
     *
     * @return Data
     */
    public function get_assets(string $id, string $assetKey): Data {
        $this->permission();
        $theme = $this->themeService->getTheme($id);
        /** @var ThemeAsset $asset */
        [$asset, $assetName, $ext] = $this->extractAssetForPath($theme, $assetKey);
        if (!$asset) {
            throw new NotFoundException('Asset');
        }

        if ($ext) {
            if (!in_array($ext, $asset->getAllowedTypes())) {
                throw new ClientException("Invalid extension '.$ext' for asset '$assetName'.");
            }
            return $asset->render($ext);
        } else {
            $result = new Data($asset);
        }

        // Set maximum cache durations for these static assets.
        // We apply a cache buster when generating these URLs.
        $result->setHeader('Cache-Control', CacheControlMiddleware::MAX_CACHE);
        $result->setMeta(CacheControlMiddleware::META_NO_VARY, true);
        $result->setMeta('X-App-Cache-Hit', $theme->isCacheHit() ? '1' : '0');

        return $result;
    }

    /**
     * Extract an asset for input and validate it.
     *
     * @param Theme $theme The theme the asset will be inserted for.
     * @param string $assetPath
     * @param RequestInterface $request
     *
     * @return array [ThemeAsset, $assetName]
     */
    private function extractInputAsset(Theme $theme, string $assetPath, RequestInterface $request): array {
        [$existingAsset, $assetName, $assetType] = $this->extractAssetForPath($theme, $assetPath);

        if (!$assetType) {
            $body = $request->getBody();
            $body = $this->assetInputSchema($assetName)->validate($body);
            $assetType = $body['type'];
            $assetBody = is_array($body['data']) ? json_encode($body['data'], JSON_UNESCAPED_UNICODE) : $body['data'];
        } else {
            $assetBody = $request->getRawBody();
        }

        // Try to create the asset.
        $asset = $this->assetFactory->createAsset($theme, $assetType, $assetName, $assetBody);
        $asset->validate();
        return [$asset, $assetName];
    }

    /**
     * Validate an asset exists on a theme and return the asset.
     *
     * @param Theme $theme
     * @param string $assetPath
     * @return array [ThemeAsset, string $assetName, string $extension]
     */
    private function extractAssetForPath(Theme $theme, string $assetPath): array {
        $assetName = pathinfo($assetPath, PATHINFO_FILENAME);
        $ext = pathinfo($assetPath, PATHINFO_EXTENSION);

        $asset = $theme->getAssets()[$assetName] ?? null;
        return [$asset, $assetName, $ext];
    }

    /**
     * Expand/unexpand assets on a theme.
     *
     * @param Theme $theme
     * @param array|bool $expandDefinition
     */
    private function handleAssetExpansions(Theme $theme, $expandDefinition) {
        foreach ($theme->getAssets() as $assetName => $asset) {
            $asset->setIncludeValueInJson($this->isExpandField("$assetName.data", $expandDefinition));
        }
    }
}
