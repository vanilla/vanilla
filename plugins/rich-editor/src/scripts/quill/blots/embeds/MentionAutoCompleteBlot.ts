/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "quill/core";
import Inline from "quill/blots/inline";
import MentionBlot from "./MentionBlot";
import { IMentionData } from "../../../editor/MentionSuggestion";
import { t } from "@dashboard/application";

/**
 * A Blot to represent text that is being matched for an autocomplete.
 *
 * This and the MentionComboBoxBlot are used for accessibility primarily and
 * don't current represent and visual changes.
 *
 * It's final state is as a MentionBlot.
 *
 * @see {MentionBlot}
 * @see {MentionComboBoxBlot}
 */
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
        domNode.setAttribute("aria-autocomplete", "list");
    }

    /**
     * Wrap this Blot in a MentionComboBoxBlot for accessibility purposes.
     */
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
        this.replaceWith("mention", result);
        this.quill && this.quill.update(Quill.sources.USER);
    }

    /**
     * Remove the combobox and turn the blot into plaintext.
     */
    public cancel() {
        this.replaceWith("inline", this.domNode.innerHTML);
        this.quill && this.quill.update(Quill.sources.USER);
    }

    /**
     * Inject accessibility attributes into the autocomplete and it's parent combobox.
     */
    public injectAccessibilityAttributes(data: IComboBoxAccessibilityOptions) {
        const domNode = this.domNode;
        const parentNode = this.parent.domNode;

        parentNode.setAttribute("id", data.ID);
        parentNode.setAttribute("aria-expanded", !!data.activeItemID);
        parentNode.setAttribute("aria-owns", data.suggestionListID);
        domNode.setAttribute("aria-controls", data.suggestionListID);

        if (data.activeItemID) {
            domNode.setAttribute("aria-activedescendant", data.activeItemID);
            domNode.removeAttribute("aria-describeby");
        } else {
            domNode.setAttribute("aria-describedby", data.noResultsID);
            domNode.removeAttribute("aria-activedescendant");
        }
    }

    /**
     * Get the attached quill instance.
     *
     * This will _NOT_ work before attach() is called.
     */
    private get quill(): Quill | null {
        if (!this.scroll || !this.scroll.domNode.parentNode) {
            return null;
        }

        return Quill.find(this.scroll.domNode.parentNode!);
    }
}

interface IComboBoxAccessibilityOptions {
    ID: string;
    suggestionListID: string;
    noResultsID: string;
    activeItemID: string | null;
}
