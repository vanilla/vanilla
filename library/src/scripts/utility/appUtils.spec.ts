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

function stubLocation(url: string) {
    // @ts-ignore
    delete window.location;
    window.location = new URL("https://mysite.com") as any;
}

describe("formatUrl", () => {
    let location = window.location;

    afterEach(() => {
        window.location = location;
    });

    it("passes absolute URLs from other domains through directly", () => {
        stubLocation("https://other.com");
        const paths = ["https://test.com", "//test.com", "http://test.com", "   http://test.com", " https://test.com"];

        paths.forEach((path) => {
            expect(application.formatUrl(path)).toEqual(path);
        });
    });

    it("handles complicated site section injection", () => {
        stubLocation("https://mysite.com");

        application.setMeta("context.host", "");
        application.setMeta("siteSectionSlugs", ["/sub1", "/sub2"]);
        application.setMeta("context.basePath", "/sub1");

        // Injects site section where it can.
        expect(application.formatUrl("https://mysite.com/somepath/otherpath")).toBe(
            "https://mysite.com/sub1/somepath/otherpath",
        );
        expect(application.formatUrl("https://mysite.com/somepath/sub1")).toBe("https://mysite.com/sub1/somepath/sub1");

        // Empty paths are left alone (so that any explicit navigation to the top level still works).
        expect(application.formatUrl("https://mysite.com/")).toBe("https://mysite.com/");
        expect(application.formatUrl("https://mysite.com")).toBe("https://mysite.com");

        // Does not inject site section if there already is one.
        expect(application.formatUrl("https://mysite.com/sub2")).toBe("https://mysite.com/sub2");
        expect(application.formatUrl("https://mysite.com/sub2/other-path")).toBe("https://mysite.com/sub2/other-path");

        // If we aren't in a site section don't do anything.
        application.setMeta("context.basePath", "");
        expect(application.formatUrl("https://mysite.com/sub2/other-path")).toBe("https://mysite.com/sub2/other-path");
        expect(application.formatUrl("https://mysite.com/profile/user")).toBe("https://mysite.com/profile/user");
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

describe("isUrl", () => {
    const urls = [
        "https://example.com",
        "http://some-other-site.thing/place/place////more?otherThing%22&otherThing=true&query=[]1",
    ];
    for (const url of urls) {
        it(`"${url} is a URL"`, () => {
            expect(application.isURL(url)).toBe(true);
        });
    }

    const notUrls = [
        "www.thing.com", // No protocol.
        "./relative/path.html", // No TLD.
        "/absolute/path.html", // No TLD.
        "javascript:alert('hello')", // Bad protocol,
        "ftp://ftp.somePlace.com", // Bad protocol.
    ];

    for (const notUrl of notUrls) {
        it(`"${notUrl} is not a URL"`, () => {
            expect(application.isURL(notUrl)).toBe(false);
        });
    }
});

describe("makeProfileUrl", () => {
    beforeEach(() => {
        stubLocation("https://mysite.com");
        application.setMeta("context.basePath", "/test");
    });
    it("can make a simple url", () => {
        expect(application.makeProfileUrl(6, "hello")).toBe("https://mysite.com/test/profile/6/hello");
    });

    it("can encoded various url characters", () => {
        expect(application.makeProfileUrl(6, "hello-$%^^*()")).toBe(
            "https://mysite.com/test/profile/6/hello-%24%25%5E%5E*()",
        );
    });

    it("can has special encoding for / and & characters", () => {
        // These are double encoded for compatibility with quirks in our backed router.
        // The backend also encodes these characters this way in user urls (see \userUrl() and UserModel::getProfileUrl())
        expect(application.makeProfileUrl(6, "he&llo/user")).toBe(
            "https://mysite.com/test/profile/6/he%2526llo%252fuser",
        );
    });
});

describe("createSourceSetValue", () => {
    it("generates a source string", () => {
        const mock = {
            100: "test-100",
            200: "test-200",
        };
        const expected = "test-100 100w,test-200 200w";
        const actual = application.createSourceSetValue(mock);
        expect(actual).toEqual(expected);
    });
    it("omits empty values source string", () => {
        const mock = {
            100: "test-100",
            200: "",
            300: "test-300",
        };
        const expected = "test-100 100w,test-300 300w";
        const actual = application.createSourceSetValue(mock);
        expect(actual).toEqual(expected);
    });
    it("returns empty source string for an object with empty values", () => {
        const mock = {
            100: "",
            200: "",
        };
        const expected = "";
        const actual = application.createSourceSetValue(mock);
        expect(actual).toEqual(expected);
    });
    it("returns empty source string for an empty object", () => {
        const mock = {};
        const expected = "";
        const actual = application.createSourceSetValue(mock);
        expect(actual).toEqual(expected);
    });
});

describe("buildUrl", () => {
    beforeEach(() => {
        stubLocation("https://mysite.com");
        application.setMeta("context.basePath", "/test");
    });

    it("returns absolute URLs unchanged", () => {
        const urls = ["https://example.com", "http://test.com", "https://mysite.com/path"];
        urls.forEach((url) => {
            expect(application.buildUrl(url)).toBe(url);
        });
    });

    it("returns full URLs for relative paths when alwaysFullUrl is true", () => {
        const urls = [
            ["path", "https://mysite.com/test/path"],
            ["/path", "https://mysite.com/test/path"],
            ["/path/to/file", "https://mysite.com/test/path/to/file"],
        ];
        urls.forEach(([input, expected]) => {
            expect(application.buildUrl(input, true)).toBe(expected);
        });
    });

    it("adds http:// to hostnames without protocol", () => {
        const urls = [
            ["example.com", "http://example.com"],
            ["test.com/path", "http://test.com/path"],
            ["sub.domain.com/path/to/file", "http://sub.domain.com/path/to/file"],
        ];
        urls.forEach(([input, expected]) => {
            expect(application.buildUrl(input)).toBe(expected);
        });
    });

    it("prevents javascript: protocol", () => {
        expect(application.buildUrl("javascript:alert('xss')")).toBe("/");
    });

    it("trims whitespace from input", () => {
        expect(application.buildUrl("  path  ")).toBe("/path");
        expect(application.buildUrl("  https://example.com  ")).toBe("https://example.com");
    });
});
