import { getMeta } from "@library/utility/appUtils";

/**
 * Get a cleaned up list of trusted domains
 *
 * Will disregard any domains or dangerous wildcards
 */
export const getTrustedDomains = (): string => {
    const trustedDomains = getMeta("trustedDomains");
    return (
        trustedDomains
            // Get rid off protocols
            .replace(/(http?s?:)?(\/)/gim, "")
            // Strip off any wildcards which are not followed by . for subdomains or follow a slash for paths
            .replace(/(?<!\/)\*(?!\.)/gim, "")
    );
};
