import { BlockEmbed } from "quill/blots/block";
import { setData } from "@core/dom-utility";
import { getData } from "@core/dom-utility";

export default class BlockquoteBlot extends BlockEmbed {
    static create(data) {
        const node = super.create(data);
        node.classList.add("embed");
        node.classList.add("blockquote");

        const quote = document.createElement('div');
        quote.classList.add('blockquote-main');
        quote.innerHTML = data.content;

        node.appendChild(quote);

        setData(node, "data", data);

        return node;
    }

    static value(node) {
        return getData(node, "data");
    }
}

BlockquoteBlot.blotName = 'blockquote-block';
BlockquoteBlot.tagName = 'blockquote';
