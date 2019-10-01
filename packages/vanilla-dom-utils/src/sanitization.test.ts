/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import { escapeHTML, unescapeHTML } from "./sanitization";

describe("escapeHtml() / unescapeHtml()", () => {
    const input = `<script>alert("Got you!")</script>`;
    const output = `&lt;script&gt;alert("Got you!")&lt;/script&gt;`;

    it("escapes html", () => {
        expect(escapeHTML(input)).to.deep.equal(output);
    });

    it("unescapes html", () => {
        expect(unescapeHTML(output)).to.deep.equal(input);
    });
});
