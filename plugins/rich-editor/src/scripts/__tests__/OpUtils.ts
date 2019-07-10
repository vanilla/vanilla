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
import { StringMap, DeltaStatic, DeltaOperation } from "quill/core";
import { ListType } from "@rich-editor/quill/blots/blocks/ListBlot";
import ExternalEmbedBlot, { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";

/**
 * Operation generation utilities for testing.
 */
export default class OpUtils {
    public static DEFAULT_LINK = "http://link.com";

    public static op(insert: any = "TEST", attributes?: StringMap): DeltaOperation {
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
    public static list(type: ListType = ListType.ORDERED, depth: number = 0, lineContent: string = "\n") {
        return OpUtils.op(lineContent, {
            [ListBlot.blotName]: {
                type,
                depth,
            },
        });
    }

    public static image(url: string, alt: string | null = null) {
        alt = alt || "";
        const imageData: IEmbedValue = {
            loaderData: {
                type: "image",
            },
            data: {
                embedType: "image",
                url,
                name: alt,
                attributes: {},
            },
        };
        return OpUtils.op({
            [ExternalEmbedBlot.blotName]: imageData,
        });
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
    {
        op: OpUtils.list(ListType.ORDERED),
        name: "orderedList",
    },
    {
        op: OpUtils.list(ListType.BULLETED),
        name: "bulletedList",
    },
];
