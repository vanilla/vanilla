/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { setData, getData } from "@core/dom-utility";
import { BlockEmbed } from "quill/blots/block";

export default class ImageBlot extends BlockEmbed {

    static blotName = 'embed-image';
    static className = 'embed-image';
    static tagName = 'div';

    static create(data) {
        const node = super.create();
        node.classList.add('embed');
        node.classList.add('embed-image');
        node.classList.add('embedImage');

        const image = document.createElement('img');
        image.classList.add('embedImage-img');
        image.setAttribute('src', data.url);
        image.setAttribute('alt', data.alt || '');

        node.appendChild(image);

        setData(node, "data", data);
        return node;
    }

    static value(node) {
        return getData(node, "data");
    }
}
