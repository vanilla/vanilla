import { BlockEmbed } from "quill/blots/block";
import { t } from "@core/utility";

export default class EmbedLoadingBlot extends BlockEmbed {

    static blotName = 'embed-loading';
    static className = 'embed-loading';
    static tagName = 'div';

    static create() {
        const node = super.create();
        node.classList.add('embed');
        node.classList.add('embed-loading');
        node.setAttribute('role', 'alert');

        node.innerHTML = `<div class='embedLoader'>
                            <div class='embedLoader-box'>
                                <div class='embedLoader-loader'>
                                    <span class='sr-only'>
                                        ${t('Loading...')}
                                    </span>
                                </div>
                            </div>
                        </div>`;
        return node;
    }

    static value() {
        return;
    }

    /**
     * Register a callback for when the blot is detached.
     *
     * @param {function} callback - The callback to call.
     */
    registerDeleteCallback(callback) {
        this.deleteCallback = callback;
    }

    /**
     * Call the delete callback if set when detaching.
     */
    detach() {
        if (this.deleteCallback) {
            this.deleteCallback();
        }

        super.detach();
    }
}
