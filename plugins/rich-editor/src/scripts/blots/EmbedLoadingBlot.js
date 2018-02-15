import { BlockEmbed } from "quill/blots/block";
import { t } from "@core/utility";

export default class EmbedLoadingBlot extends BlockEmbed {
    static create() {
        const node = super.create();
        node.classList.add('embed');
        node.setAttribute('role', 'alert');

        node.innerHTML = "<div class=\"embedLoader\">\n" +
            "            <div class=\"embedLoader-box\">\n" +
            "                <div class=\"embedLoader-loader\">\n" +
            "                    <span class=\"sr-only\">\n" +
                                    t('Loading...') +
            "                    </span>\n" +
            "                </div>\n" +
            "            </div>\n" +
            "        </div>";
        return node;
    }

    static value() {
        return;
    }
}

EmbedLoadingBlot.blotName = 'loading-embed';
EmbedLoadingBlot.tagName = 'div';
