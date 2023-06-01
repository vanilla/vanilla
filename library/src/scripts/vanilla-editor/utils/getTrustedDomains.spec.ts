import { setMeta } from "@library/utility/appUtils";
import { getTrustedDomains } from "@library/vanilla-editor/utils/getTrustedDomains";

const MOCK_DOMAINS = ["vanillaforums.com"];

function makeMockDomainString(domains: string[] = []): string {
    return [...MOCK_DOMAINS, ...domains].join("\n");
}

describe("getTrustedDomains", () => {
    it("returns domain without wildcard", () => {
        setMeta("trustedDomains", makeMockDomainString());
        const result = getTrustedDomains();
        expect(result.split("\n")[0]).toBe(MOCK_DOMAINS[0]);
    });
    it("returns domain without protocols", () => {
        setMeta("trustedDomains", makeMockDomainString(["http://mockdomain.com", "https://mockdomain.com"]));
        const result = getTrustedDomains().split("\n");
        expect(result[1]).toBe("mockdomain.com");
        expect(result[2]).toBe("mockdomain.com");
    });
    it("returns domain without wildcard if its not followed by a period", () => {
        setMeta("trustedDomains", makeMockDomainString(["*.mockdomain.com", "*mockdomain.com"]));
        const result = getTrustedDomains().split("\n");
        expect(result[1]).toBe("*.mockdomain.com");
        expect(result[2]).toBe("mockdomain.com");
    });
    it("returns domain without wildcard if its wildcard is a path", () => {
        setMeta("trustedDomains", makeMockDomainString(["mockdomain.com/*", "mockdomain.com*"]));
        const result = getTrustedDomains().split("\n");
        expect(result[1]).toBe("mockdomain.com");
        expect(result[2]).toBe("mockdomain.com");
    });
    it("returns domains with wildcard only if its a subdomain", () => {
        setMeta("trustedDomains", makeMockDomainString(["*.mockdomain.com/*", "*mockdomain.com*"]));
        const result = getTrustedDomains().split("\n");
        expect(result[1]).toBe("*.mockdomain.com");
        expect(result[2]).toBe("mockdomain.com");
    });
});
