<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Http\InternalClient;
use Vanilla\Web\JsInterpop\RawReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Page preloader for profile fields.
 */
class ProfileFieldsPreloadProvider implements ReduxActionProviderInterface
{
    /** @var InternalClient */
    private $internalClient;

    /**
     * DI.
     *
     * @param InternalClient $internalClient
     */
    public function __construct(InternalClient $internalClient)
    {
        $this->internalClient = $internalClient;
    }

    /**
     * @inheritdoc
     */
    public function createActions(): array
    {
        $profileFields = $this->internalClient->get("/api/v2/profile-fields", ["enabled" => true])->getBody();
        return [
            new RawReduxAction([
                "type" => "@@userProfiles/fetchProfileFields/fulfilled",
                "payload" => $profileFields,
                "meta" => [
                    "arg" => [
                        "enabled" => true,
                    ],
                ],
            ]),
        ];
    }
}
