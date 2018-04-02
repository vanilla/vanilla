/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import WrapperBlot, { ContentBlot, LineBlot } from "../Abstract/WrapperBlot";

export default class BlockquoteLineBlot extends LineBlot {
    static blotName = "blockquote-line";
    static className = "blockquote-line";
    static tagName = 'p';
    static parentName = "blockquote-content";
}

export class BlockquoteContentBlot extends ContentBlot {
    static className = 'blockquote-content';
    static blotName = 'blockquote-content';
    static parentName = 'blockquote';
}

export class BlockquoteWrapperBlot extends WrapperBlot {
    static className = 'blockquote';
    static blotName = 'blockquote';
}
