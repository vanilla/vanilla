/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { loadTranslations, clearTranslations, translate, t } from "./translationStore";
import { debug } from "@vanilla/utils";

describe("translate", () => {
    beforeEach(() => {
        loadTranslations({
            foo: "bar",
        });
    });

    afterEach(() => {
        clearTranslations();
        process.env.NODE_ENV = "test";
    });

    it("Translates a string", () => {
        expect(translate("foo")).toEqual("bar");
    });

    it("Returns the default string", () => {
        expect(translate("baz", "bam")).toEqual("bam");
    });

    it("Falls back to the string code", () => {
        expect(translate("moo")).toEqual("moo");
    });

    it("Throws a fatal for an unitialized store in dev mode", () => {
        clearTranslations();
        process.env.NODE_ENV = "development";
        let error: any;
        try {
            t("test", "test");
        } catch (err) {
            error = err;
        }
        expect(error).toBeInstanceOf(Error);
    });

    it("Doesn't throw a fatal error in prod mode", () => {
        clearTranslations();
        process.env.NODE_ENV = "production";
        t("test", "test");
    });
});
