import Embed from "quill/blots/embed";

export default class ImageBlot extends Embed {
    static create(value) {
        const node = super.create();
        node.setAttribute('alt', value.alt);
        node.setAttribute('src', value.url);
        return node;
    }

    static value(node) {
        return {
            alt: node.getAttribute('alt'),
            url: node.getAttribute('src'),
        };
    }
}

ImageBlot.blotName = 'embeddedImage';
ImageBlot.tagName = 'img';
