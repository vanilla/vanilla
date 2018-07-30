/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import LinkBlot from "quill/formats/link";
import BoldBlot from "quill/formats/bold";
import ItalicBlot from "quill/formats/italic";
import StrikeBlot from "quill/formats/strike";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import HeadingBlot from "quill/formats/header";
import { StringMap } from "quill/core";

export default class OpUtils {
    public static DEFAULT_LINK = "http://link.com";

    public static op(insert: string = "TEST", attributes?: StringMap) {
        const op: any = {
            insert,
        };

        if (attributes) {
            op.attributes = attributes;
        }
        return op;
    }

    public static newline() {
        return OpUtils.op("\n");
    }

    public static bold(content: string = "TEST") {
        return OpUtils.op(content, { [BoldBlot.blotName]: true });
    }
    public static italic(content: string = "TEST") {
        return OpUtils.op(content, { [ItalicBlot.blotName]: true });
    }
    public static strike(content: string = "TEST") {
        return OpUtils.op(content, { [StrikeBlot.blotName]: true });
    }
    public static codeInline(content: string = "TEST") {
        return OpUtils.op(content, { [CodeBlot.blotName]: true });
    }
    public static link(content: string = "TEST", href: string = OpUtils.DEFAULT_LINK) {
        return OpUtils.op(content, { [LinkBlot.blotName]: OpUtils.DEFAULT_LINK });
    }
    public static codeBlock(lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [CodeBlockBlot.blotName]: true });
    }
    public static heading(lineContent: string = "\n", level: number = 2) {
        return OpUtils.op(lineContent, { [HeadingBlot.blotName]: level });
    }
    public static quoteLine(lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [BlockquoteLineBlot.blotName]: true });
    }
    public static spoilerLine(lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [SpoilerLineBlot.blotName]: true });
    }
}
