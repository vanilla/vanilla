<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\View;

/**
 * Defines methods for legacy layout views.
 */
interface LegacyLayoutViewInterface extends LayoutViewInterface
{
    /**
     * Get the legacy type name. Eg. Vanilla/Post/Question.
     *
     * @return string
     */
    public function getLegacyType(): string;
}
