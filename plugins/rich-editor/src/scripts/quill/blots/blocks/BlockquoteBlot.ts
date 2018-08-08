/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

/* tslint:disable:max-classes-per-file */

import WrapperBlot from "../abstract/WrapperBlot";
import ContentBlot from "../abstract/ContentBlot";
import LineBlot from "../abstract/LineBlot";

export default class BlockquoteLineBlot extends LineBlot {
    public static blotName = "blockquote-line";
    public static className = "blockquote-line";
    public static tagName = "p";
    public static parentName = "blockquote-content";
}

export class BlockquoteContentBlot extends ContentBlot {
    public static className = "blockquote-content";
    public static blotName = "blockquote-content";
    public static parentName = "blockquote";
}

export class BlockquoteWrapperBlot extends WrapperBlot {
    public static className = "blockquote";
    public static blotName = "blockquote";
}
