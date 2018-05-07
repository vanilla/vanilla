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
        node.classList.add("embed-loading");
        node.classList.add("embed");
        node.classList.remove(FocusableEmbedBlot.FOCUS_CLASS);
        node.innerHTML = `<div class='embedLoader'>
                            <div class='embedLoader-box ${FocusableEmbedBlot.FOCUS_CLASS}' aria-label='${t(
            "Loading...",
        )}'><div class='embedLoader-loader'></div>
                            </div>
                        </div>`;
        return node;
    }

    public static value() {
        return {};
    }
}
