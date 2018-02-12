<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

abstract class BlotType {

    /** Inline types can be stacked on after another. */
    const INLINE = "inline";

    const FORMAT = "format";

    /** Block types can only be on a line by themselves. */
    const BLOCK = "block";

    /**  */
    const EMBED = "embed";
}
