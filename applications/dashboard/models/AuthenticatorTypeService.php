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

    /**
     * Augment the generated OpenAPI schema with the registered translation services.
     *
     * @param array $openApi
     * @throws \Garden\Container\ContainerException Exception.
     * @throws \Garden\Container\NotFoundException Exception.
     */
    public function __invoke(array &$openApi): void
    {
        // Get schema of the AuthenticationTypeService.oneOf.
        $serviceSchemas = \Vanilla\Utility\ArrayUtils::getByPath(
            "components.schemas.AuthenticationTypeService.oneOf",
            $openApi,
            []
        );
        $authenticatorTypes = $this->getAuthenticatorTypes();
        foreach ($authenticatorTypes as $authenticatorType) {
            $authType = $authenticatorType->getAuthenticatorType();
            $basename = $authType["authenticatorType"];
            $schema = $authType["schema"]["authenticatorConfig:o?"];
            $ref = "#/components/schemas/{$basename}";
            $refPath = "components.schemas.{$basename}";

            // Add the schema to the AuthenticationTypeService schema's available schemas.
            $serviceSchemas[] = ['$ref' => $ref];
            \Vanilla\Utility\ArrayUtils::setByPath($refPath, $openApi, $schema->jsonSerialize());
        }
        \Vanilla\Utility\ArrayUtils::setByPath(
            "components.schemas.AuthenticationTypeService.oneOf",
            $openApi,
            $serviceSchemas
        );
    }
}
