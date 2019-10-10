/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { loadTranslations, clearTranslations, translate } from "./translationStore";
import { expect } from "chai";

describe("translate", () => {
    beforeEach(() => {
        loadTranslations({
            foo: "bar",
        });
    });

    afterEach(() => {
        clearTranslations();
    });

    it("Translates a string", () => {
        expect(translate("foo")).eq("bar");
    });

    it("Returns the default string", () => {
        expect(translate("baz", "bam")).eq("bam");
    });

    it("Falls back to the string code", () => {
        expect(translate("moo")).eq("moo");
    });
});
