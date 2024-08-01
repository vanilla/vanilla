/* eslint-disable react/no-unknown-property */
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
    RichLinkAppearance,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";
import { jsx } from "@udecode/plate-test-utils";
jsx;

describe("setRichLinkAppearance", () => {
    const linkVal = {
        type: "p",
        children: [{ type: "a", url: "https://github.com", children: [{ text: "https://github.com" }] }],
    };

    const inlineVal = {
        type: "p",
        children: [
            { text: "" },
            {
                type: ELEMENT_RICH_EMBED_INLINE,
                dataSourceType: "url",
                embedData: undefined,
                url: "https://github.com",
                children: [{ text: "https://github.com" }],
            },
            { text: "" },
        ],
    };

    const selectLocation = {
        anchor: { path: [0, 1], offset: 0 },
        focus: { path: [0, 1], offset: 0 },
    };

    it("can convert a link and into an inline", () => {
        const editor = createVanillaEditor();
        editor.insertNode(linkVal);
        editor.select(selectLocation);
        setRichLinkAppearance(editor, RichLinkAppearance.INLINE);

        expect(editor.children).toStrictEqual([inlineVal]);
    });

    it("can convert a link into a card", () => {
        const expected = [
            { type: "p", children: [{ text: "" }] },
            {
                type: ELEMENT_RICH_EMBED_CARD,
                dataSourceType: "url",
                embedData: undefined,
                url: "https://github.com",
                children: [{ text: "https://github.com" }],
            },
            { type: "p", children: [{ text: "" }] },
        ];

        const editor = createVanillaEditor();
        editor.insertNode(linkVal);
        editor.select(selectLocation);
        setRichLinkAppearance(editor, RichLinkAppearance.CARD);

        expect(editor.children).toStrictEqual(expected);
    });

    it("can convert an inline into a link", () => {
        const expected = [
            {
                type: "p",
                children: [
                    { text: "" },
                    {
                        type: "a",
                        url: "https://github.com",
                        children: [{ text: "https://github.com" }],
                        embedData: undefined,
                        forceBasicLink: true,
                    },
                    { text: "" },
                ],
            },
        ];
        const editor = createVanillaEditor();
        editor.insertNode(inlineVal);
        editor.select(selectLocation);
        setRichLinkAppearance(editor, RichLinkAppearance.LINK);

        expect(editor.children).toStrictEqual(expected);
    });
});
