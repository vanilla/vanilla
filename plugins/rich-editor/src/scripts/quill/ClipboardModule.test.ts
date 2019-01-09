/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ClipboardModule from "@rich-editor/quill/ClipboardModule";
import { expect } from "chai";

describe("ClipboardModule.splitLinkOperationsOutOfText()", () => {
    it("Can parse out a single link", () => {
        const link = "https://test.com";
        const input = `${link}`;
        const expected = [{ insert: link, attributes: { link } }];

        expect(ClipboardModule.splitLinkOperationsOutOfText(input)).deep.equals(expected);
    });

    it("Can parse out multiple links a single link", () => {
        const link = "https://test.com";
        const link2 = "https://othertest.com";
        const input = `text${link} moreText\n\n\n${link2}`;
        const expected = [
            { insert: "text" },
            { insert: link, attributes: { link } },
            { insert: " moreText\n\n\n" },
            { insert: link2, attributes: { link: link2 } },
        ];

        expect(ClipboardModule.splitLinkOperationsOutOfText(input)).deep.equals(expected);
    });

    it("Doesn't alter operations when no links were found.", () => {
        const input = `asdfasdfasfd\n\n\nasdfasdfhtt://asd http// ssh://asdfasfd`;
        expect(ClipboardModule.splitLinkOperationsOutOfText(input)).equals(null);
    });
});
