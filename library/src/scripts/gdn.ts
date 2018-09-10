/**
 * A module to isolate meta data passed from the server into a single dependency.
 * This should always be used instead of accessing window.gdn directly.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

interface IGdn {
    meta: AnyObject;
    permissions: AnyObject;
    translations: AnyObject;
    [key: string]: any;
}

/** The gdn object may be set in an inline script in the head of the document. */
const gdn = window.gdn || {};

if (!("meta" in gdn)) {
    gdn.meta = {};
}

if (!("permissions" in gdn)) {
    gdn.permissions = {};
}

if (!("translations" in gdn)) {
    gdn.translations = {};
}

export default gdn as IGdn;
