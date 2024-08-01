<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\RequestInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Factory class used to create RoleToken instances.
 * Encapsulates management of role token parameter encoding / decoding values.
 */
class RoleTokenFactory
{
    //region Constants
    const DEFAULT_ROLE_TOKEN_WINDOW_SEC = 120;
    const DEFAULT_ROLE_TOKEN_ROLLOVER_SEC = 60;
    //endregion

    //region Properties
    /** @var ConfigurationInterface $config */
    private $config;

    /** @var string $secret */
    private $secret;

    /** @var int $windowSec */
    private $windowSec;

    /** @var int $rolloverWithinSec */
    private $rolloverWithinSec;
    //endregion

    //region Constructors
    /**
     * DI constructor
     *
     * @param ConfigurationInterface $config
     * @throws \Exception While generating secret.
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
        $secret = $config->get("Vanilla.RoleToken.Secret", null);
        if (is_null($secret)) {
            $secret = sha1(random_bytes(128));
            $this->config->saveToConfig("Vanilla.RoleToken.Secret", $secret);
        }
        $this->secret = $secret;
        $this->windowSec = intval($config->get("Vanilla.RoleToken.WindowSec", self::DEFAULT_ROLE_TOKEN_WINDOW_SEC));
        $this->rolloverWithinSec = intval(
            $config->get("Vanilla.RoleToken.RolloverWithinSec", self::DEFAULT_ROLE_TOKEN_ROLLOVER_SEC)
        );
    }
    //endregion

    //region Public Instance Methods
    /**
     * Issue a new role token that is valid within a specific time window for the specified role IDs
     *
     * @param array $roleIDs Role IDs to include in role token
     * @param RequestInterface|null $request Optional, request from which role token issuance was initiated
     * @return RoleToken
     *
     * @throws \InvalidArgumentException One or more invalid types role ids specified.
     * @throws \LengthException Zero-length role ID array.
     */
    public function forEncoding(array $roleIDs, ?RequestInterface $request = null): RoleToken
    {
        $roleToken = RoleToken::forEncoding($this->secret, $this->windowSec, $this->rolloverWithinSec)->setRoleIDs(
            $roleIDs
        );
        if (isset($request)) {
            $roleToken = $roleToken->setRequestor($request);
        }
        return $roleToken;
    }

    /**
     * Issue a role token without any claims but with its signing secret that can be populated via its decode method
     *
     * @return RoleToken
     */
    public function forDecoding(): RoleToken
    {
        return RoleToken::withSecret($this->secret);
    }
    //endregion
}
