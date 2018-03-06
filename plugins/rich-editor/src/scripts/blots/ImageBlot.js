import { setData, getData } from "@core/dom-utility";
import Parchment from 'parchment';

export default class ImageBlot extends Parchment.Embed {

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
