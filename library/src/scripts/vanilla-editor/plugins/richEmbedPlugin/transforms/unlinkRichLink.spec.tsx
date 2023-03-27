/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

/** @jsx jsx */

import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import { unlinkRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/unlinkRichLink";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { jsx } from "@udecode/plate-test-utils";
jsx;

describe("unlinkRichLink", () => {
    const linkVal = (
        <editor>
            <hp>
                <ha url={"https://github.com"}>
                    Link to Github
                    <cursor />
                </ha>
            </hp>
        </editor>
    ) as any;

    const inlineVal = (
        <editor>
            <hp>
                <htext>
                    Link to Github
                    <cursor />
                </htext>
            </hp>
        </editor>
    ) as any;

    describe("simple links", () => {
        it("unlinks a simple link, leaving the original text", () => {
            const editor = createVanillaEditor(linkVal);
            unlinkRichLink(editor);
            expect(editor.children).toEqual(inlineVal.children);
        });
    });

    describe("rich links", () => {
        it("unlinks an inline rich link, leaving the original text", () => {
            const editor = createVanillaEditor(linkVal);
            setRichLinkAppearance(editor, RichLinkAppearance.INLINE);
            unlinkRichLink(editor);
            expect(editor.children).toEqual(inlineVal.children);
        });

        it("unlinks a card rich link, leaving the original text", () => {
            const editor = createVanillaEditor(linkVal);
            setRichLinkAppearance(editor, RichLinkAppearance.CARD);
            unlinkRichLink(editor);
            expect(editor.children).toEqual(inlineVal.children);
        });
    });
});
