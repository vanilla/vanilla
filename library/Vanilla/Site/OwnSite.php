<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\Site;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Http\InternalClient;

/**
 * Baseline own site. Contents are entirely configuration based.
 *
 * - IDs are pulled from configuration.
 * - HttpClient is an `InternalClient` so requests don't actually go through HTTP.
 */
class OwnSite extends Site {

    const CONF_ACCOUNT_ID = "Vanilla.AccountID";
    const CONF_SITE_ID = "Vanilla.SiteID";

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param FormatService $formatService
     * @param \Gdn_Request $request
     * @param InternalClient $internalClient
     */
    public function __construct(
        ConfigurationInterface $config,
        FormatService $formatService,
        \Gdn_Request $request,
        InternalClient $internalClient
    ) {
        $name = $formatService->renderPlainText($config->get('Garden.Title', ""), HtmlFormat::FORMAT_KEY);
        $internalClient->setBaseUrl('');
        $internalClient->setThrowExceptions(true);

        parent::__construct(
            $name,
            $request->getSimpleUrl(''),
            $config->get(self::CONF_SITE_ID, -1),
            $config->get(self::CONF_ACCOUNT_ID, -1),
            $internalClient
        );
    }
}
