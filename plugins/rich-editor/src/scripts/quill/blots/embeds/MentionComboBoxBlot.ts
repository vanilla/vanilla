/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Container from "quill/blots/container";
import Inline from "quill/blots/inline";
import Embed from "quill/blots/embed";
import TextBlot from "quill/blots/text";
import Parchment from "parchment";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";

/**
 * A Blot to wrap the MentionAutoCompleteBlot.
 *
 * @see {MentionAutoCompleteBlot}
 *
 * The MentionAutoCompleteBlot is responsible for wrapping itself.
 * Do not instantiate this Blot on its own.
 */
export default class MentionComboBoxBlot extends Container {
    public static blotName = "mention-combobox";
    public static className = "atMentionComboBox";
    public static tagName = "span";
    public static scope = Parchment.Scope.BLOCK;
    public static allowedChildren = [...Container.allowedChildren, MentionAutoCompleteBlot, Inline, Embed, TextBlot];

    constructor(domNode) {
        super(domNode);
        domNode.setAttribute("role", "combobox");
        domNode.setAttribute("aria-haspopup", "listbox");
    }

    /**
     * Delete this blot it has no children. Wrap it if it doesn't have it's proper parent name.
     *
     * @param context - A shared context that is passed through all updated Blots.
     */
    public optimize(context) {
        super.optimize(context);
        if (this.children.length === 0) {
            this.remove();
        }
    }

    public replaceWith(name, value) {
        const replacement = typeof name === "string" ? Parchment.create(name, value) : name;
        return super.replaceWith(replacement);
    }
}
