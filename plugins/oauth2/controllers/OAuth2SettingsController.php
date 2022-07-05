<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Web\Data;
use Vanilla\Webhooks\Controllers\Api\ActionConstants;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Webhooks\Controllers\Api\WebhooksApiController;
use Vanilla\Webhooks\Models\WebhookDeliveryModel;
use Vanilla\Webhooks\Models\WebhookModel;

/**
 * Controller for oauth2 settings.
 */
class OAuth2SettingsController extends SettingsController
{
    /** @var Gdn_Request */
    private $request;

    /**
     * OAuth2SettingsController constructor.
     *
     * @param Gdn_Request $request
     */
    public function __construct(Gdn_Request $request)
    {
        parent::__construct();
        $this->request = $request;
    }

    /**
     * Serve all paths.
     *
     * @param string $path Any path.
     */
    public function index(string $path = null)
    {
        $this->permission("Garden.Settings.Manage");
        $this->setHighlightRoute("oauth2-settings");
        $this->title(t("OAuth2"));

        $this->render();
    }
}
