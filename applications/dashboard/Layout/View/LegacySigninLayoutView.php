<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Layout\View;

use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Legacy view type for signin.
 */
class LegacySigninLayoutView implements LegacyLayoutViewInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Sign In";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "signin";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Dashboard/Entry/Signin";
    }
}
