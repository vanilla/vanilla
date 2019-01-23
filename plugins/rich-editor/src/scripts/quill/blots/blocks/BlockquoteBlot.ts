/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/* tslint:disable:max-classes-per-file */

import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import ContentBlot from "@rich-editor/quill/blots/abstract/ContentBlot";
import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";

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
