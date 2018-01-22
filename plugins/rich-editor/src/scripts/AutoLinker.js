/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * A Quill plugin to automatically link text.
 */
export default class AutoLinker {

    static REGEXP_HTTP_GLOBAL = /https?:\/\/[^\s]+/g;
    static REGEXP_HTTP_GLOBAL_WITH_WS = /(?:\s|^)(https?:\/\/[^\s]+)/;

    /**
     * Hook into a quill instance.
     *
     * @param {Quill} quill - A quill instance.
     */
    constructor(quill) {
        this.registerTypeListener(quill);
        this.registerPasteListener(quill);
    }

    /**
     * Get the text contents from the previous whitespace up until the
     *
     * @param {string} str - The string to slice.
     *
     * @returns {string} - The slice from the final whitespace onwards.
     */
    sliceFromLastWhitespace(str) {
        const whiteSpaceIndex = str.lastIndexOf(' ');
        const sliceIndex = whiteSpaceIndex === -1 ? 0 : whiteSpaceIndex + 1;
        return str.slice(sliceIndex);
    }

    /**
     * Add handler to match links as they are typed.
     *
     * @param {Quill} quill - A quill instance.
     */
    registerTypeListener(quill) {
        quill.keyboard.addBinding({
            collapsed: true,
            key: ' ',
            prefix: this.REGEXP_HTTP_GLOBAL_WITH_WS,
            handler: (range, context) => {
                const url = this.sliceFromLastWhitespace(context.prefix);
                const ops = [
                    { retain: range.index - url.length },
                    { delete: url.length },
                    { insert: url, attributes: { link: url } },
                ];
                quill.updateContents({ ops });
                return true;
            },
        });
    }

    /**
     * Add handler match links as they are pasted.
     *
     * @param {Quill} quill - A quill instance.
     */
    registerPasteListener(quill) {
        quill.clipboard.addMatcher(Node.TEXT_NODE, (node, delta) => {
            if (typeof node.data !== 'string') {
                return undefined;
            }
            const matches = node.data.match(this.REGEXP_HTTP_GLOBAL);
            if (matches && matches.length > 0) {
                const ops = [];
                let str = node.data;
                matches.forEach(match => {
                    const split = str.split(match);
                    const beforeLink = split.shift();
                    ops.push({ insert: beforeLink });
                    ops.push({ insert: match, attributes: { link: match } });
                    str = split.join(match);
                });
                ops.push({ insert: str });
                delta.ops = ops;
            }

            return delta;
        });
    }
}
