/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "quill/core";
import BaseClipboard from "quill/modules/clipboard";
import { isAllowedUrl } from "@core/application";

/**
 * Extended clipboard module to search for links pasted from the clipboard and transform them into real links.
 */
export default class ClipboardModule extends BaseClipboard {
    constructor(quill: Quill, options: any) {
        super(quill, options);
        this.addMatcher(Node.TEXT_NODE, (node, delta) => {
            const regex = /https?:\/\/[^\s]+/g;
            if (typeof node.data !== "string") {
                return;
            }
            const matches = node.data.match(regex);

            if (matches && matches.length > 0) {
                const ops: any[] = [];
                let str = node.data;
                matches.forEach(match => {
                    const split = str.split(match);
                    const beforeLink = split.shift();
                    ops.push({ insert: beforeLink });
                    if (isAllowedUrl(match)) {
                        ops.push({ insert: match, attributes: { link: match } });
                    } else {
                        ops.push({ insert: match });
                    }
                    str = split.join(match);
                });
                ops.push({ insert: str });
                delta.ops = ops;
            }

            return delta;
        });
    }
}
