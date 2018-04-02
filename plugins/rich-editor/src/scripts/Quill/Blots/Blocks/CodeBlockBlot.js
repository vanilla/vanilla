/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import CodeBlock from "quill/formats/code";

export default class CodeBlockBlot extends CodeBlock {

    static blotName = 'code-block';
    static tagName = 'code';
    static className = 'code-block';

    static create() {
        const domNode = super.create();
        domNode.setAttribute('spellcheck', false);
        domNode.classList.add('code');
        domNode.classList.add('isBlock');
        domNode.classList.add("code-block");
        return domNode;
    }
}
