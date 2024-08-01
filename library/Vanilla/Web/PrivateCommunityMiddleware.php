<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web;

use Garden\Web\RequestInterface;
use Vanilla\Permissions;

/**
 * A middleware that sets a permission ban for private communities.
 */
class PrivateCommunityMiddleware
{
    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * @var bool
     */
    private $isPrivate;

    /**
     * @var \Gdn_Locale
     */
    private $locale;

    /**
     * PrivateCommunityMiddleware constructor.
     *
     * @param bool $isPrivate Whether or not the community is private.
     * @param \Gdn_Session $session The session to set the ban on.
     * @param \Gdn_Locale $locale For translating messages.
     */
    public function __construct(bool $isPrivate, \Gdn_Session $session, \Gdn_Locale $locale)
    {
        $this->session = $session;
        $this->isPrivate = $isPrivate;
        $this->locale = $locale;
    }

    /**
     * Invoke the middleware that sets the ban.
     *
     * @param RequestInterface $request The current request.
     * @param callable $next The next middleware
     * @return mixed
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        if ($this->isPrivate && !$this->session->isValid()) {
            $this->session->getPermissions()->addBan(Permissions::BAN_PRIVATE, [
                "msg" => $this->locale->translate("You must sign in to the private community."),
                "code" => 403,
            ]);
        }

        return $next($request);
    }

    /**
     * Whether or not this is a private community.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    /**
     * Set whether or not this is a private community.
     *
     * @param bool $isPrivate
     */
    public function setIsPrivate(bool $isPrivate): void
    {
        $this->isPrivate = $isPrivate;
    }
}
