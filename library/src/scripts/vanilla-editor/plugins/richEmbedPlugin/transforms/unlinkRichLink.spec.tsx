/* eslint-disable react/no-unknown-property */
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import { unlinkRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/unlinkRichLink";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";
import { jsx } from "@udecode/plate-test-utils";
jsx;

describe("unlinkRichLink", () => {
    const linkVal = {
        type: "p",
        children: [
            {
                type: "a",
                url: "https://github.com",
                children: [{ text: "Link to Github" }],
            },
        ],
    };

    const inlineVal = [{ type: "p", children: [{ text: "Link to Github" }] }];

    const selectLocation = {
        anchor: { path: [0, 1], offset: 0 },
        focus: { path: [0, 1], offset: 0 },
    };

    describe("simple links", () => {
        it("unlinks a simple link, leaving the original text", () => {
            const editor = createVanillaEditor();
            editor.insertNode(linkVal);
            editor.select(selectLocation);
            unlinkRichLink(editor);

            expect(editor.children).toStrictEqual(inlineVal);
        });
    });

    describe("rich links", () => {
        it("unlinks an inline rich link, leaving the original text", () => {
            const editor = createVanillaEditor();
            editor.insertNode(linkVal);
            editor.select(selectLocation);
            setRichLinkAppearance(editor, RichLinkAppearance.INLINE);
            unlinkRichLink(editor, selectLocation);

            expect(editor.children).toStrictEqual(inlineVal);
        });

        it("unlinks a card rich link, leaving the original text", () => {
            const editor = createVanillaEditor();
            editor.insertNode(linkVal);
            editor.select(selectLocation);
            setRichLinkAppearance(editor, RichLinkAppearance.CARD);
            unlinkRichLink(editor);

            expect(editor.children).toStrictEqual([{ type: "p", children: [{ text: "" }] }, ...inlineVal]);
        });
    });
});
