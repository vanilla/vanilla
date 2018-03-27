/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import * as application from "@core/application.js";
import gdn from "@core/gdn";
import * as utility from "@core/utility";

describe("metaDataFunctions", () => {
    it("return a default value if the requested one can't be found", () => {
        expect(application.getMeta("baz", "fallback")).toBe("fallback");
    })

    it("can set a new meta value", () => {
        application.setMeta("test", "result");
        expect(application.getMeta("test")).toBe("result");
    })

    it("can override existing values with new ones", () => {
        application.setMeta("foo", "foo2");
        expect(application.getMeta("foo")).toBe("foo2");
    })
})

describe("translate", () => {
    gdn.translations['foo'] = 'bar';

    it("Translates a string", () => {
        expect(application.translate('foo')).toBe('bar');
    });

    it("Returns the default string", () => {
        expect(application.translate('baz', 'bam')).toBe('bam');
    });

    it("Falls back to the string code", () => {
        expect(application.translate('moo')).toBe('moo');
    });
});

describe("formatUrl", () => {
    it("passes absolute URLs through directly", () => {
        const paths = [
            "https://test.com",
            "//test.com",
            "http://test.com",
        ];

        paths.forEach(path => {
            expect(application.formatUrl(path)).toBe(path);
        })
    });

    it("follows the given format", () => {
        application.setMeta("UrlFormat", "/test/{Path}");

        expect(application.formatUrl("/discussions")).toBe("/test/discussions");
    });
});