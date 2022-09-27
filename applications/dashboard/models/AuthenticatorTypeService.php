<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Higher Logic.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Vanilla\Models\AuthenticatorTypeInterface;

/**
 * Service to obtain authenticator types data.
 */
class AuthenticatorTypeService
{
    /** @var AuthenticatorTypeInterface[] */
    private $authenticatorTypes = [];

    /**
     * Add an authenticator type.
     *
     * @param AuthenticatorTypeInterface $authenticatorType
     * @return void
     */
    public function addAuthenticatorType(AuthenticatorTypeInterface $authenticatorType): void
    {
        $this->authenticatorTypes[] = $authenticatorType;
    }

    /**
     * Return an array of the available authenticator types.
     *
     * @return AuthenticatorTypeInterface[]
     */
    public function getAuthenticatorTypes(): array
    {
        return $this->authenticatorTypes;
    }
}
