/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import { t } from "@core/application";
import uniqueId from "lodash/uniqueId";

export default class ErrorBlot extends FocusableEmbedBlot {
    public static blotName = "embed-error";
    public static className = "embed-error";
    public static tagName = "div";

    public static create(data) {
        const node = super.create(data) as HTMLElement;
        const descriptionId = uniqueId("embedLoader-description");
        node.classList.remove(FocusableEmbedBlot.FOCUS_CLASS);

        const error = document.createElement("div");
        error.classList.add("embedLoader-error");
        error.classList.add(FocusableEmbedBlot.FOCUS_CLASS);
        error.setAttribute("aria-describedby", descriptionId);
        error.setAttribute("aria-label", t("Error"));
        error.setAttribute("role", "alert");

        error.setAttribute("aria-live", "assertive");
        error.innerHTML = `<svg class="embedLoader-icon embedLoader-warningIcon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M11.651,3.669,2.068,21.75H21.234Zm.884-1,10,18.865A1,1,0,0,1,21.649,23h-20a1,1,0,0,1-.884-1.468l10-18.865a1,1,0,0,1,1.768,0Zm.231,13.695H10.547L10.2,10h2.9Zm-2.535,2.354a1.24,1.24,0,0,1,.363-.952,1.493,1.493,0,0,1,1.056-.34,1.445,1.445,0,0,1,1.039.34,1.26,1.26,0,0,1,.353.952,1.223,1.223,0,0,1-.366.944A1.452,1.452,0,0,1,11.65,20a1.5,1.5,0,0,1-1.042-.34A1.206,1.206,0,0,1,10.231,18.716Z" style="fill: currentColor;"/>
                        </svg>
                        <span class="embedLoader-errorMessage" id="${descriptionId}">${data.message}</span>
                        <button type="button" class="closeButton js-closeEmbedError" aria-hidden="true" tabindex="-1">
                            <svg class="embedLoader-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
                            </svg>
                        </button>
                    </div>`;

        node.appendChild(error);
        return node;
    }

    public static value(domNode: HTMLElement) {
        const messageNode = domNode.querySelector(".embedLoader-errorMessage");
        return {
            message: messageNode ? messageNode.textContent : t("Error could not be found"),
        };
    }

    constructor(domNode: HTMLElement) {
        super(domNode);

        const closeButton = domNode.querySelector(".js-closeEmbedError");
        if (closeButton instanceof HTMLElement) {
            closeButton.addEventListener("click", () => {
                this.remove();
            });
        }
    }
}
