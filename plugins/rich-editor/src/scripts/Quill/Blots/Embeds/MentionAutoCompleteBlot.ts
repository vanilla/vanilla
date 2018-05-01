/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Inline from "quill/blots/inline";
import MentionBlot from "./MentionBlot";
import { IMentionData } from "../../../Editor/MentionSuggestion";
import { t } from "@core/application";

const count = 0;

export default class MentionAutoCompleteBlot extends Inline {
    public static blotName = "mention-autocomplete";
    public static className = "atMentionAutoComplete";
    public static tagName = "span";
    public static requiredContainer = "mention-combobox";

    public static formats() {
        return true;
    }

    constructor(domNode) {
        super(domNode);
        domNode.setAttribute("aria-label", t("@mention a user"));
    }

    public attach() {
        super.attach();
        if (
            this.statics.requiredContainer &&
            (this.parent as any).statics.blotName !== this.statics.requiredContainer
        ) {
            this.wrap(this.statics.requiredContainer);
        }
    }

    /**
     * Get the username out of the Blot. This is used for API requests.
     *
     * The @ sign is remove, and double quotes are stripped, because they can be used to allow
     * spaces or punctuation.
     */
    get username() {
        const textContent = this.domNode.textContent || "";
        return textContent.replace("@", "").replace(`"`, "");
    }

    /**
     * Be sure to unwrap this Blot into its parent before replacing itself
     * or it will recurse ifinitelely trying to recreate its requiredContainer
     */
    public replaceWith(name, value?) {
        this.moveChildren(this.parent, this.next);
        this.remove();
        return this.parent.replaceWith(name, value);
    }

    /**
     * Finalize this Blot by replacing it with a full MentionBlot.
     *
     * @param result The new MentionBlot
     */
    public finalize(result: IMentionData) {
        return this.replaceWith("mention", result) as MentionBlot;
    }

    /**
     * Remove the combobox and turn the blot into plaintext.
     */
    public cancel() {
        return this.replaceWith("inline", this.domNode.innerHTML);
    }

    /**
     * Inject accessibility attributes into the autocomplete and it's parent combobox.
     */
    public injectAccessibilityAttributes(data: IComboBoxAccessibilityOptions) {
        this.domNode.setAttribute("aria-controls", data.mentionListID);
        this.domNode.setAttribute("aria-activedescendant", data.activeItemID);
        this.parent.domNode.setAttribute("id", data.ID);
        this.parent.domNode.setAttribute("aria-owns", data.mentionListID);
        this.parent.domNode.setAttribute("aria-activedescendant", data.activeItemID);
    }
}

interface IComboBoxAccessibilityOptions {
    ID: string;
    mentionListID: string;
    activeItemID: string;
}
