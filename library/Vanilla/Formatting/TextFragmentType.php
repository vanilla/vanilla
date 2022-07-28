<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * An enum of valid types for
 */
class TextFragmentType
{
    public const HTML = "html";
    public const TEXT = "text";
    public const URL = "url";
    public const CODE = "code";
    /**
     * Use "other" when a text fragment might need special handling that the programmer must analyze. Please limit its
     * use as much as possible.
     */
    public const OTHER = "other";
}
