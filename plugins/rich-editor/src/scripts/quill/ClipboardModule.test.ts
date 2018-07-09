/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import ClipboardModule from "./ClipboardModule";
import { expect } from "chai";

describe("ClipboardModule.splitLinkOperationsOutOfText()", () => {
    it("Can parse out a single link", () => {
        const link = "https://test.com";
        const input = `text${link} moreText`;
        const expected = [{ insert: "text" }, { insert: link, attributes: { link } }, { insert: " moreText" }];

        expect(ClipboardModule.splitLinkOperationsOutOfText(input)).deep.equals(expected);
    });
});
