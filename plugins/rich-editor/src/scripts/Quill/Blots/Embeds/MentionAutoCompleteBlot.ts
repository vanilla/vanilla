/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { Blot } from "quill/core";
import Inline from "quill/blots/inline";
import Container from "quill/blots/container";
import Parchment from "parchment";
import withWrapper from "../Abstract/withWrapper";
import MentionBlot from "./MentionBlot";
import { IMentionData } from "../../../Editor/MentionItem";
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
            console.log("wrapping");
            this.wrap(this.statics.requiredContainer);
        } else {
            console.log("no wrapping", this.statics.requiredContainer);
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
     * If this is the only child blot we want to delete the parent with it.
     */
    public remove() {
        // this.parent.remove();
        super.remove();
    }

    public replaceWith(name, value?) {
        this.moveChildren(this.parent, this.next);
        this.remove();
        // this.parent.unwrap();
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

    public cancel() {
        console.log(this.domNode.innerHTML);
        return this.replaceWith("inline", this.domNode.innerHTML);
    }

    // public optimize(context: { [key: string]: any }): void {

    // }

    public injectAccessibilityAttributes(data: IComboBoxAccessibilityOptions) {
        // Inject accessibility attributes into the autocomplete and it's parent combobox.
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
