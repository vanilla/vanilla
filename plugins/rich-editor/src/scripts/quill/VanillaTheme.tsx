/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// Quill
import Quill, { QuillOptionsStatic, Blot } from "quill/core";
import ThemeBase from "quill/core/theme";
import KeyboardBindings from "./KeyboardBindings";

// React
import React from "react";
import ReactDOM from "react-dom";
import InlineToolbar from "../editor/InlineToolbar";
import ParagraphToolbar from "../editor/ParagraphToolbar";
import EmojiPopover from "../editor/EmojiPopover";
import EmbedPopover from "../editor/EmbedPopover";
import EditorProvider from "../editor/ContextProvider";
import MentionModule from "../editor/MentionModule";
import Waypoint from "react-waypoint";

export default class VanillaTheme extends ThemeBase {
    private jsBodyBoxContainer: Element;
    private jsFormElement: Element;
    private jsEmbedMenu: Element;

    /**
     * Constructor.
     *
     * @param quill - The quill instance the theme is applying to.
     * @param options - The current options for the instance.
     */
    constructor(quill: Quill, options: QuillOptionsStatic) {
        const themeOptions = {
            ...options,
            placeholder: "Create a new post...",
        };

        super(quill, themeOptions);
        this.quill.root.setAttribute("tabIndex", "0");
        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");
        this.quill.root.setAttribute("tabindex", 0);

        // Add keyboard bindings to options.
        this.addModule("embed/insertion");
        this.addModule("embed/focus");
        const keyboardBindings = new KeyboardBindings(this.quill);
        this.options.modules.keyboard.bindings = {
            ...this.options.modules.keyboard.bindings,
            ...keyboardBindings.bindings,
        };

        // Find the editor root.
        this.jsBodyBoxContainer = this.quill.container.closest(".richEditor") as Element;
        this.jsFormElement = this.jsBodyBoxContainer.closest(".FormWrapper") as Element;
        this.jsEmbedMenu = this.jsBodyBoxContainer.querySelector(".richEditor-embedBar") as Element;
        if (!this.jsBodyBoxContainer) {
            throw new Error("Could not find .richEditor to mount editor components into.");
        }
        this.mountMentionModule();
        this.mountEmojiMenu();
    }

    public init() {
        // Mount react components
        this.mountToolbar();
        this.mountParagraphMenu();
        this.mountEmbedPopover();
        this.mountStickyEmbedMenu();
    }

    /**
     * Mount an inline toolbar (react component).
     */
    private mountToolbar() {
        const container = this.jsBodyBoxContainer.querySelector(".js-InlineEditorToolbar");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <InlineToolbar />
            </EditorProvider>,
            container,
        );
    }

    /**
     * Mount the paragraph formatting toolbar (react component).
     */
    private mountParagraphMenu() {
        const container = this.jsBodyBoxContainer.querySelector(".js-ParagraphEditorToolbar");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <ParagraphToolbar />
            </EditorProvider>,
            container,
        );
    }

    /**
     * Mount Emoji Menu (react component).
     */
    private mountEmojiMenu() {
        const container = this.jsBodyBoxContainer.querySelector(".js-emojiHandle");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <EmojiPopover />
            </EditorProvider>,
            container,
        );
    }

    private mountEmbedPopover() {
        const container = this.jsBodyBoxContainer.querySelector(".js-EmbedDialogue");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <EmbedPopover />
            </EditorProvider>,
            container,
        );
    }

    private handleStickyMenuTop(data) {
        const pastWaypoint = data.waypointTop < 0;

        this.jsFormElement.classList.toggle("isPastEditorTop", pastWaypoint);
        this.jsEmbedMenu.classList.toggle("InputBox-borderWidth", pastWaypoint);
        this.jsEmbedMenu.classList.toggle("InputBox-borderColor", pastWaypoint);
    }

    private handleStickyMenuBottom(data) {
        const pastWaypoint = data.waypointTop < 0;
        this.jsFormElement.classList.toggle("isPastEditorBottom", pastWaypoint);
    }

    private mountStickyEmbedMenu() {
        const stickyEmbed = this.jsBodyBoxContainer.querySelector(".js-RichEditorStickyEmbed");
        const unStickyEmbed = this.jsBodyBoxContainer.querySelector(".js-RichEditorUnstickyEmbed");

        ReactDOM.render(
            <Waypoint
                onPositionChange={data => {
                    this.handleStickyMenuTop(data);
                }}
            />,
            stickyEmbed,
        );

        ReactDOM.render(
            <Waypoint
                onPositionChange={data => {
                    this.handleStickyMenuBottom(data);
                }}

                alert(unStickyEmbed.offsetHeight);
                bottomOffset={-(unStickyEmbed.offsetHeight || 39)}
            />,
            unStickyEmbed,
        );
    }

    private mountMentionModule() {
        const container = this.jsBodyBoxContainer.querySelector(".js-MentionModule");
        ReactDOM.render(
            <EditorProvider quill={this.quill}>
                <MentionModule />
            </EditorProvider>,
            container,
        );
    }
}
