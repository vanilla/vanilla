/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

/** @jsx jsx */

import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
    RichLinkAppearance,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { jsx } from "@udecode/plate-test-utils";
jsx;

describe("setRichLinkAppearance", () => {
    const linkVal = (
        <editor>
            <hp>
                <ha url={"https://github.com"}>
                    https://github.com
                    <cursor />
                </ha>
            </hp>
        </editor>
    ) as any;
    const inlineVal = (
        <editor>
            <hp>
                <htext />
                <element
                    dataSourceType="url"
                    embedData={undefined}
                    url={"https://github.com"}
                    type={ELEMENT_RICH_EMBED_INLINE}
                >
                    <cursor />
                </element>
                <htext />
            </hp>
        </editor>
    ) as any;
    it("can convert a link and into an inline", () => {
        const editor = createVanillaEditor(linkVal);
        setRichLinkAppearance(editor, RichLinkAppearance.INLINE);
        expect(editor.children).toEqual(inlineVal.children);
    });

    it("can convert a link into a card", () => {
        const expected = (
            <editor>
                <hp>
                    <htext />
                </hp>
                <element
                    dataSourceType="url"
                    embedData={undefined}
                    url={"https://github.com"}
                    type={ELEMENT_RICH_EMBED_CARD}
                >
                    <htext />
                </element>
                <hp>
                    <htext />
                </hp>
                {/* Added by trailing block plugin */}
            </editor>
        ) as any;

        const editor = createVanillaEditor(linkVal);
        setRichLinkAppearance(editor, RichLinkAppearance.CARD);
        expect(editor.children).toEqual(expected.children);
    });

    it("can convert an inline into a link", () => {
        const editor = createVanillaEditor(inlineVal);
        setRichLinkAppearance(editor, RichLinkAppearance.CARD);
        expect(editor.children).toEqual(linkVal.children);
    });
});
