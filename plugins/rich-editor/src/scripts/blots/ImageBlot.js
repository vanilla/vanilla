import Quill from "quill";
let Embed = Quill.import('blots/embed');


export default class ImageBlot extends Embed {
    static create(value) {
        let node = super.create();
        node.setAttribute('alt', value.alt);
        node.setAttribute('src', value.url);
        return node;
    }

    static value(node) {
        return {
            alt: node.getAttribute('alt'),
            url: node.getAttribute('src')
        };
    }
}

ImageBlot.blotName = 'image';
ImageBlot.tagName = 'img';
