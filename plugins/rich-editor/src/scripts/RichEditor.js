/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "quill/quill";
import EmojiBlot from "./blots/EmojiBlot.js";
import ImageBlot from "./blots/ImageBlot.js";
import SpoilerBlot from "./blots/SpoilerBlot.js";
import CodeInlineBlot from "./formats/CodeInlineBlot.js";
import CodeBlockBlot from "./blots/CodeBlockBlot.js";
import VideoBlot from "./blots/VideoBlot.js";
import VanillaTheme from "./quill/VanillaTheme";
import * as utility from "@core/utility";
import { delegateEvent } from "@core/dom-utility";

// Blots
Quill.register(EmojiBlot);
Quill.register(ImageBlot);
Quill.register(SpoilerBlot);
Quill.register(CodeInlineBlot);
Quill.register(CodeBlockBlot);
Quill.register(VideoBlot);


// Quill.register({
//     'formats/code-inline': CodeInlineBlot,
// });

// Theme
Quill.register("themes/vanilla", VanillaTheme);

const options = {
    theme: "vanilla",
};

export default class RichEditor {

    initialFormat = "rich";
    initialValue = "";

    /** @type {HTMLFormElement} */
    form;

    /**
     * Create a new RichEditor.
     *
     * @param {string|Element} containerSelector - The CSS selector or the container to render into.
     */
    constructor(containerSelector) {
        if (typeof containerSelector === "string") {
            this.container = document.querySelector(containerSelector);
            if (!this.container) {
                if (!this.container) {
                    throw new Error(`Editor container ${containerSelector} could not be found. Rich Editor could not be started.`);
                }
            }
        } else if (containerSelector instanceof HTMLElement) {
            this.container = containerSelector;
        }

        // Hijack the form submit
        const form = this.container.closest("form");
        this.bodybox = form.querySelector(".BodyBox");

        if (!this.bodybox) {
            throw new Error("Could not find the BodyBox inside of the form.");
        }

        this.initialFormat = this.bodybox.getAttribute("format") || "Rich";
        this.initialValue = this.bodybox.value;

        if (this.initialFormat === "Rich") {
            this.initializeWithRichFormat();
        } else {
            this.initializeOtherFormat();
        }
    }

    /**
     * Handle a click on a video.
     *
     * @param {Event} event - The event.
     */
    handlePlayVideo() {
        const playButton = this;
        if (!(playButton instanceof HTMLElement)) {
            return;
        }
        const container = playButton.closest(".embedVideo-ratio");
        container.innerHTML = '<iframe frameborder="0" allow="autoplay; encrypted-media" class="embedVideo-iframe" src="' + playButton.dataset.url + '" allowfullscreen></iframe>';
    }

    initializeWithRichFormat() {
        utility.log("Initializing Rich Editor");
        this.quill = new Quill(this.container, options);
        this.bodybox.style.display = "none";

        if (this.initialValue) {
            utility.log("Setting existing content as contents of editor");
            this.quill.setContents(JSON.parse(this.initialValue));
        }

        this.quill.on("text-change", this.synchronizeDelta.bind(this));

        // const insertEmoji = () => {
        //     const editorSelection = this.quill.getSelection();
        //     const emoji = '😊';
        //     let range = this.quill.getSelection(true);
        //     this.quill.insertEmbed(range.index, 'emoji', {
        //         'emojiChar': emoji
        //     }, Quill.sources.USER);
        //     this.quill.setSelection(range.index + 1, Quill.sources.SILENT);
        //
        // };
        // document.querySelector(".emojiButton").addEventListener("click", insertEmoji);
        //
        // const insertImage = () => {
        //     let range = this.quill.getSelection(true);
        //     this.quill.insertEmbed(range.index, 'embeddedImage', {
        //         alt: 'Quill Cloud',
        //         url: 'http://stephane.local/uploads/userpics/966/pNOH8FCLAMG82.jpg'
        //     }, Quill.sources.USER);
        //     this.quill.setSelection(range.index + 1, Quill.sources.SILENT);
        // };

        const insertSpoiler = () => {
            const range = this.quill.getSelection(true);
            const text = this.quill.getText(range.index, range.length);
            this.quill.insertEmbed(range.index, 'list', {
                content: text,
            }, Quill.sources.USER);
            this.quill.setSelection(range.index + text.length, Quill.sources.SILENT);
        };
        document.querySelector(".test-spoiler").addEventListener("click", insertSpoiler);


        // Dummy data
        const insertText = () => {
            const range = this.quill.getSelection(true);
            const blurb = "Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind's eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there's little good evidence Orion's sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxyrise the sky calls to us! Descended from astronomers?\n" +
                "Vanquish the impossible, another world. Are creatures of the cosmos, white dwarf Cambrian explosion ship of the imagination colonies, how far away. Venture, extraplanetary stirred by starlight, cosmic ocean across the centuries. With pretty stories for which there's little good evidence extraplanetary concept of the number one culture quasar permanence of the stars, Orion's sword, white dwarf. Something incredible is waiting to be known birth Hypatia tingling of the spine network of wormholes bits of moving fluff ship of the imagination as a patch of light.\n" +
                "With pretty stories for which there's little good evidence Euclid dream of the mind's eye, rings of Uranus decipherment the sky calls to us descended from astronomers trillion, Tunguska event radio telescope, hydrogen atoms! Concept of the number one, at the edge of forever ship of the imagination, Sea of Tranquility, hydrogen atoms encyclopaedia galactica astonishment something incredible is waiting to be known tendrils of gossamer clouds. The only home we've ever known extraordinary claims require extraordinary evidence. Stirred by starlight made in the interiors of collapsing stars galaxies emerged into consciousness! Dispassionate extraterrestrial observer and billions upon billions upon billions upon billions upon billions upon billions upon billions?";
            this.quill.insertText(range.index, blurb, Quill.sources.USER);
            this.quill.setSelection(range.index + blurb.length, Quill.sources.SILENT);
        };
        document.querySelector(".test-sagan").addEventListener("click", insertText);


        // Code Block - Inline
        const insertInlineCodeBlock = () => {
            const range = this.quill.getSelection(true);
            const text = this.quill.getText(range.index, range.length);

            this.quill.deleteText(range.index, range.length, Quill.sources.SILENT);

            this.quill.insertEmbed(range.index, 'code-inline', {
                content: text,
            }, Quill.sources.USER);
            this.quill.setSelection(range.index + text.length, Quill.sources.SILENT);

            // this.quill.formatText(range.index, text.length, 'code-inline', {
            //     content: text,
            // }, Quill.sources.USER);
            // this.quill.setSelection(range.index, text.length, Quill.sources.SILENT);

        };
        document.querySelector(".test-blockinline").addEventListener("click", insertInlineCodeBlock);

        // Code Block - Block
        const insertCodeBlockBlock = () => {
            const range = this.quill.getSelection(true);
            const text = this.quill.getText(range.index, range.length);
            console.log("code block text: ", text);
            // this.quill.deleteText(range.index, range.length, Quill.sources.SILENT);
            this.quill.insertEmbed(range.index, 'code-block', {
                content: text,
            }, Quill.sources.USER);
            this.quill.setSelection(range.index + text.length, Quill.sources.SILENT);
        };
        document.querySelector(".test-blockparagraph").addEventListener("click", insertCodeBlockBlock);

        // Code Block - Block
        const insertVideo = () => {
            const range = this.quill.getSelection(true);
            this.quill.insertText(range.index, '\n', Quill.sources.SILENT);
            this.quill.insertEmbed(range.index + 1, 'video-placeholder', {
                photoUrl: 'https://i.ytimg.com/vi/wupToqz1e2g/hqdefault.jpg',
                url: 'https://www.youtube.com/embed/wupToqz1e2g',
                name: "Video Title",
                width: 1858,
                height: 1276,

            }, Quill.sources.USER);
            this.quill.setSelection(range.index + 2, Quill.sources.SILENT);
        };
        document.querySelector(".test-video").addEventListener("click", insertVideo);
        delegateEvent('click', '.js-playVideo', this.handlePlayVideo);
    }

    /**
     * For compatibility with the legacy base theme's javascript the Quill Delta needs to always be in the main form
     * as a hidden input (Because we aren't overriding the submit)
     */
    synchronizeDelta() {
        this.bodybox.value = JSON.stringify(this.quill.getContents()["ops"]);
    }

    initializeOtherFormat() {

        // TODO: check if we can convert from a format

        return;
    }
}
