<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

/**
 * Implement this on classes that need to provide authenticator type data.
 */
interface AuthenticatorTypeInterface
{
    /**
     * Return data for the authenticator type.
     *
     * @return array
     */
    public function getAuthenticatorType(): array;
}
