/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { Code } from "quill/formats/code";

export default class CodeBlot extends Code {
    static blotName = 'code-inline';
    static tagName = 'code';
    static className = 'code-inline';

    constructor(domNode) {
        super(domNode);
        domNode.classList.add('code');
        domNode.classList.add('code-inline');
        domNode.classList.add('isInline');
    }
}
