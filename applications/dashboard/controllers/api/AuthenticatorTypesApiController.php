<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Vanilla\Dashboard\AuthenticatorTypeService;
use Vanilla\Models\AuthenticatorTypeFragmentSchema;

/**
 * Class AuthenticatorTypesApiController
 */
class AuthenticatorTypesApiController extends AbstractApiController
{
    /** @var AuthenticatorTypeService $authenticatorTypeService */
    private $authenticatorTypeService;

    /**
     * Class constructor.
     *
     * @param AuthenticatorTypeService $authenticatorTypeService
     */
    public function __construct(AuthenticatorTypeService $authenticatorTypeService)
    {
        $this->authenticatorTypeService = $authenticatorTypeService;
    }

    /**
     * Return a list of every available authenticator types.
     *
     * @return Data
     */
    public function index(): Data
    {
        $this->permission("Garden.Settings.Manage");

        // Get the outbound schema.
        $out = $this->schema([":a" => new AuthenticatorTypeFragmentSchema()], "out");

        $authenticatorTypes = $this->authenticatorTypeService->getAuthenticatorTypes();
        $results = [];
        if (is_array($authenticatorTypes)) {
            foreach ($authenticatorTypes as $authenticatorType) {
                $results[] = $authenticatorType->getAuthenticatorType();
            }
        }

        $data = $out->validate($results);

        return new Data($data);
    }
}
