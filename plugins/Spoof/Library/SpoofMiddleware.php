<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Plugins\Spoof\Library;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Garden\Web\RequestInterface;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\SmartIDMiddleware;
use Vanilla\Logging\LogDecorator;

/**
 * Allow spoofing a request as a user by sending an X-Vanilla-Spoof header.
 */
class SpoofMiddleware {

    // Permission required to use the spoof header.
    public const PERMISSION = "Garden.Settings.Manage";

    // Full ame of the spoof request header.
    public const SPOOF_HEADER = "X-Vanilla-Spoof";

    // Response header where the original user's name will be returned.
    public const SPOOF_BY_HEADER = "X-Vanilla-Spoof-By";

    /** @var LogDecorator */
    private $logger;

    /** @var Gdn_Session */
    private $session;

    /** @var SmartIDMiddleware */
    private $smartIDMiddleware;

    /**
     * Middleware setup routine.
     *
     * @param Gdn_Session $session
     * @param SmartIDMiddleware $smartIDMiddleware
     * @param LogDecorator $logger
     */
    public function __construct(Gdn_Session $session, SmartIDMiddleware $smartIDMiddleware, LogDecorator $logger) {
        $this->logger = $logger;
        $this->session = $session;
        $this->smartIDMiddleware = $smartIDMiddleware;
    }

    /**
     * Invoke the middleware on a request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     * @throws PermissionException If current user has invalid permissions to spoof.
     * @throws ClientException If an issue is encountered resolving the user reference.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $value = $request->getHeader(self::SPOOF_HEADER) ?: null;

        if ($value !== null) {
            if ($this->session->checkPermission(self::PERMISSION) !== true) {
                throw new PermissionException(self::PERMISSION);
            }
            $originalUser = $this->session->User;
            $userID = $this->resolveUserID(trim($value));
            $this->session->start($userID, false, false);
            $this->logger->addStaticContextDefaults([
                "spoofBy" => [
                    "userID" => $originalUser->UserID ?? null,
                    "name" => $originalUser->Name ?? null,
                ],
            ]);
        }

        $response = Data::box($next($request));

        if (isset($originalUser)) {
            $response->setHeader(self::SPOOF_BY_HEADER, $originalUser->Name ?? "Unknown");
        }

        return $response;
    }

    /**
     * Given a user reference, either an integer or a smart ID, return the real user ID.
     *
     * @param int|string $ref
     * @return int
     * @throws ClientException If user reference is neither an integer or a smart ID.
     * @throws ClientException If a smart ID is provided, but is invalid.
     */
    private function resolveUserID($ref): int {
        if (is_numeric($ref)) {
            return $ref;
        }

        if (!SmartIDMiddleware::valueIsSmartID($ref)) {
            throw new ClientException(self::SPOOF_HEADER . " is not a valid smart ID: {$ref}", 400);
        }

        return $this->smartIDMiddleware->replaceSmartID("UserID", $ref);
    }
}
