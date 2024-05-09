/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import { getFormData } from "./domData";

describe("getFormData()", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <form>
                <input type="text" name="foo" value="foo">
            </form>
        `;
    });

    it("can get data out of a form", () => {
        const form = document.querySelector("form");
        expect(getFormData(form)).deep.equals({ foo: "foo" });
    });
});
