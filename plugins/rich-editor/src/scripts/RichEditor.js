/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import * as apiUtility from "@core/api-utility";
import * as domUtility from "@core/dom-utility";
import * as utility from "@core/utility";
import DiscussionsApi from "@vanilla/api/discussions";
import CommentsApi from "@vanilla/api/comments";
import Quill from "quill";

const toolbarOptions = [
    ["bold", "italic", "underline", "strike"], // toggled buttons
    ["blockquote", "code-block"], // Blocks
    [{ header: 1 }, { header: 2 }], // custom button values
    [{ list: "ordered"}, { list: "bullet" }],
    [{ indent: "-1"}, { indent: "+1" }], // outdent/indent
    [{ header: [1, 2, false] }],
    ["clean"], // remove formatting button
];

const options = {
    modules: {
        toolbar: toolbarOptions,
    },
    placeholder: "Create a new post...",
    theme: "bubble",
};

export default class RichEditor {
    initialFormat = "rich";
    initialValue = "";

    /**
     * Create a new RichEditor.
     *
     * @param {string|Element} containerSelector - The CSS selector or the container to render into.
     */
    constructor(containerSelector) {
        this.gatherInitialData = this.gatherInitialData.bind(this);
        this.submitComment = this.submitComment.bind(this);
        this.submitDiscussion = this.submitDiscussion.bind(this);
        this.handleFormSubmit = this.handleFormSubmit.bind(this);
        this.submit = this.submit.bind(this);

        let container;

        if (typeof containerSelector === "string") {
            container = document.querySelector(containerSelector);
            if (!container) {
                if (!container) {
                    throw new Error(`Editor container ${containerSelector} could not be found. Rich Editor could not be started.`);
                }
            }
        } else if (containerSelector instanceof HTMLElement) {
            container = containerSelector;
        }

        // Hijack the form submit
        const form = container.closest("form");
        if (form) {
            this.form = form;
            this.gatherInitialData();
            form.addEventListener("submit", this.handleFormSubmit);
        }

        this.editor = new Quill(container, options);

        if (this.initialFormat === "rich") {
            const contents = JSON.parse(this.initialValue)["ops"];
            this.editor.setContents(contents);
        } else {
            this.editor.setText(this.initialValue);
        }
    }

    gatherInitialData() {
        const formatInput = this.form.querySelector("input[name='Format']");

        if (formatInput && formatInput.value) {
            this.initialFormat = formatInput.value;
        }

        const textarea = this.form.querySelector("textarea[name='Body']");

        if (textarea && textarea.value) {
            this.initialValue = textarea.value;
        }

        this.endpoint = this.form.getAttribute("data-submit-type");

        if (!this.endpoint) {
            throw new Error("Unable to find submission endpoint. Be sure to set the `data-submit-type` on the form.");
        }
    }

    /**
     * Handle the editor form submit.
     *
     * @param {Event} event - A javascript event.
     *
     * @returns {boolean} - False to prevent default.
     */
    handleFormSubmit(event) {
        event.preventDefault();

        const formData = domUtility.getFormData(this.form);
        const transformedData = apiUtility.transformLegacyFormData(formData);
        const editorResult = this.editor.getContents();
        transformedData["body"] = JSON.stringify(editorResult);
        transformedData["format"] = "rich";

        this.submit(transformedData);

        return false;
    }

    /**
     * Submit the form data to the desired endpoint.
     *
     * @param {Object} data - The data to submit.
     */
    submit(data) {
        let submitFunction;

        switch (this.endpoint) {
        case "discussions":
            submitFunction = this.submitDiscussion;
            break;
        case "comments":
            submitFunction = this.submitComment;
            break;
        default:
            throw new Error(`Unknown submit endpoint ${this.endpoint}`);
        }
        submitFunction(data);
    }

    /**
     * Submit form data to the discussion endpoint.
     *
     * @param {Object} data - The data to submit.
     */
    submitDiscussion(data) {
        const id = data["discussionID"];
        const promise = this.initialValue ? DiscussionsApi.patch(id, data) : DiscussionsApi.post(data);

        promise
            .then((response) => {
                if (response.data.url) {
                    window.location.href = response.data.url;
                }
            }).catch((error) => {
                utility.logError(error);
            });
    }

    submitComment(data) {
        const id = data["discussionID"];
        const promise = this.initialValue ? CommentsApi.patch(id, data) : CommentsApi.post(data);

        promise
            .then((response) => {
                console.log(response);
            }).catch((error) => {
                utility.logError(error);
            });
    }
}
