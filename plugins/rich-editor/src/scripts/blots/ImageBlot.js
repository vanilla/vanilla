import { BlockEmbed } from "quill/blots/block";
import { setData } from "@core/dom-utility";
import { getData } from "@core/dom-utility";

export default class ImageBlot extends BlockEmbed {
    static create(data) {
        const node = super.create();
        node.classList.add('embed');
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

ImageBlot.blotName = 'image-embed';
ImageBlot.tagName = 'div';
