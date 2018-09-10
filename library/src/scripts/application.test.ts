/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { expect } from "chai";
import * as application from "@library/application";
import gdn from "@library/gdn";

describe("metaDataFunctions", () => {
    it("return a default value if the requested one can't be found", () => {
        expect(application.getMeta("baz", "fallback")).eq("fallback");
    });

    it("can set a new meta value", () => {
        application.setMeta("test", "result");
        expect(application.getMeta("test")).eq("result");
    });

    it("can override existing values with new ones", () => {
        application.setMeta("foo", "foo2");
        expect(application.getMeta("foo")).eq("foo2");
    });

    it("dot syntax for nested getMeta values", () => {
        application.setMeta("ui", { foo: "bar", bar: { baz: "bam" } });
        expect(application.getMeta("ui.foo")).eq("bar");
        expect(application.getMeta("ui.bar.baz")).eq("bam");
        expect(application.getMeta("ui.bar.bax", "de")).eq("de");
        expect(application.getMeta("uiz.bar.bax", "de")).eq("de");
        expect(application.getMeta("ui.foo.bax", "de")).eq("de");
    });

    it("dot syntax for nested setMeta values", () => {
        application.setMeta("a.b.c", "d");
        expect(application.getMeta("a")).deep.equals({ b: { c: "d" } });
    });
});

describe("translate", () => {
    gdn.translations.foo = "bar";

    it("Translates a string", () => {
        expect(application.translate("foo")).eq("bar");
    });

    it("Returns the default string", () => {
        expect(application.translate("baz", "bam")).eq("bam");
    });

    it("Falls back to the string code", () => {
        expect(application.translate("moo")).eq("moo");
    });
});

describe("formatUrl", () => {
    it("passes absolute URLs through directly", () => {
        const paths = ["https://test.com", "//test.com", "http://test.com"];

        paths.forEach(path => {
            expect(application.formatUrl(path)).eq(path);
        });
    });

    it("follows the given format", () => {
        application.setMeta("context.basePath", "/test");

        expect(application.formatUrl("/discussions")).eq("/test/discussions");
    });
});
