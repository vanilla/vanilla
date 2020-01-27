/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as application from "@library/utility/appUtils";
import gdn from "@library/gdn";

describe("metaDataFunctions", () => {
    it("return a default value if the rtoEqualuested one can't be found", () => {
        expect(application.getMeta("baz", "fallback")).toEqual("fallback");
    });

    it("can set a new meta value", () => {
        application.setMeta("test", "result");
        expect(application.getMeta("test")).toEqual("result");
    });

    it("can override existing values with new ones", () => {
        application.setMeta("foo", "foo2");
        expect(application.getMeta("foo")).toEqual("foo2");
    });

    it("dot syntax for nested getMeta values", () => {
        application.setMeta("ui", { foo: "bar", bar: { baz: "bam" } });
        expect(application.getMeta("ui.foo")).toEqual("bar");
        expect(application.getMeta("ui.bar.baz")).toEqual("bam");
        expect(application.getMeta("ui.bar.bax", "de")).toEqual("de");
        expect(application.getMeta("uiz.bar.bax", "de")).toEqual("de");
        expect(application.getMeta("ui.foo.bax", "de")).toEqual("de");
    });

    it("dot syntax for nested setMeta values", () => {
        application.setMeta("a.b.c", "d");
        expect(application.getMeta("a")).toEqual({ b: { c: "d" } });
    });
});

describe("formatUrl", () => {
    it("passes absolute URLs through directly", () => {
        const paths = ["https://test.com", "//test.com", "http://test.com"];

        paths.forEach(path => {
            expect(application.formatUrl(path)).toEqual(path);
        });
    });

    it("follows the given format", () => {
        application.setMeta("context.basePath", "/test");

        expect(application.formatUrl("/discussions")).toEqual("/test/discussions");
    });

    it("does site root relativeURLs", () => {
        application.setMeta("context.basePath", "/community/subcommunity");
        application.setMeta("context.host", "/community");
        expect(application.formatUrl("/discussions")).toEqual("/community/subcommunity/discussions");
        expect(application.formatUrl("~/discussions")).toEqual("/community/discussions");
    });
});
