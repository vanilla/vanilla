/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
import ListBlot from "quill/formats/list";
import { StringMap } from "quill/core";

/**
 * Operation generation utilities for testing.
 */
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
    public static code(content: string = "TEST") {
        return OpUtils.op(content, { [CodeBlot.blotName]: true });
    }
    public static link(href: string = OpUtils.DEFAULT_LINK, content: string = "TEST") {
        return OpUtils.op(content, { [LinkBlot.blotName]: href });
    }
    public static codeBlock(lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [CodeBlockBlot.blotName]: true });
    }
    public static heading(level: number = 2, lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [HeadingBlot.blotName]: { level, ref: "" } });
    }
    public static quoteLine(lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [BlockquoteLineBlot.blotName]: true });
    }
    public static spoilerLine(lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [SpoilerLineBlot.blotName]: true });
    }
    public static list(listType: "ordered" | "bullet" = "ordered", lineContent: string = "\n") {
        return OpUtils.op(lineContent, { [ListBlot.blotName]: listType });
    }
}

export const inlineFormatOps = [
    {
        op: OpUtils.op(),
        name: "plainText",
    },
    {
        op: OpUtils.bold(),
        name: "bold",
    },
    {
        op: OpUtils.italic(),
        name: "italic",
    },
    {
        op: OpUtils.strike(),
        name: "strike",
    },
    {
        op: OpUtils.link(),
        name: "link",
    },
    {
        op: OpUtils.code(),
        name: "code",
    },
];
export const blockFormatOps = [
    {
        op: OpUtils.heading(2),
        name: "h2",
    },
    {
        op: OpUtils.heading(3),
        name: "h3",
    },
    {
        op: OpUtils.quoteLine(),
        name: "blockquote",
    },
    {
        op: OpUtils.spoilerLine(),
        name: "spoiler",
    },
    {
        op: OpUtils.codeBlock(),
        name: "codeBlock",
    },
];
