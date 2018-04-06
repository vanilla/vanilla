/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@core/application";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";

export default class LoadingBlot extends FocusableEmbedBlot {

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
