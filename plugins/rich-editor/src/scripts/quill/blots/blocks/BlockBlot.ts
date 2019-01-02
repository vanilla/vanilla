/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Block from "quill/blots/block";

export default class BlockBlot extends Block {
    public static allowedChildren = [...Block.allowedChildren];
}
