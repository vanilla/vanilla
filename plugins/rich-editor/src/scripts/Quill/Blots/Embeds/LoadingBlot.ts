/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@core/application";
import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import uniqueId from "lodash/uniqueId";

export default class LoadingBlot extends FocusableEmbedBlot {
    public static blotName = "embed-loading";
    public static className = "embed-loading";
    public static tagName = "div";

    public static create(value: any) {
        const node = super.create(value) as HTMLElement;
        const descriptionId = uniqueId("embedLoader-description");

        node.classList.add("embed");
        node.classList.add("embed-loading");
        node.classList.remove(FocusableEmbedBlot.FOCUS_CLASS);

        node.innerHTML = `<div class='embedLoader'>
                            <div class='embedLoader-box ${
                                FocusableEmbedBlot.FOCUS_CLASS
                            }' aria-describedby='${descriptionId}' aria-label='${t("Loading...")}'>
                                <span id="${descriptionId}" class='sr-only'>${t("richEditor.embed.description")}</span>
                                <div class='embedLoader-loader'></div>
                            </div>
                        </div>`;
        return node;
    }

    public static value() {
        return {};
    }

    private deleteCallback?: () => void;

    /**
     * Register a callback for when the blot is detached.
     *
     * @param {function} callback - The callback to call.
     */
    public registerDeleteCallback(callback) {
        this.deleteCallback = callback;
    }

    /**
     * Call the delete callback if set when detaching.
     */
    public detach() {
        if (this.deleteCallback) {
            this.deleteCallback();
        }

        super.detach();
    }
}
