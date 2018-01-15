import events from "@core/events";
import * as apiUtility from "@core/api-utility";
import * as domUtility from "@core/dom-utility";
import Quill from "quill";
import axios from "axios";

let options = {
    // debug: "info",
    modules: {
        toolbar: ['bold', 'italic', 'underline', 'strike'],
    },
    placeholder: "Compose an epic...",
    theme: "bubble",
};

class RichEditor {

    submitEndpoint = "discussions";

    /**
     * Create a new RichEditor.
     *
     * @param {string} containerSelector - The CSS selector or the container to render into.
     */
    constructor(containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) {
            if (!container) {
                throw new Error(`Editor container ${containerSelector} could not be found. Rich Editor could not be started.`);
            }
        }

        this.editor = new Quill(container, options);

        // Hijack the form submit
        const form = container.closest("form");
        if (form) {
            this.form = form;
            form.addEventListener("submit", this.handleFormSubmit);
        }
    }

    /**
     * Handle the editor form submit.
     *
     * @param {Event} event - A javascript event.
     *
     * @returns {boolean}
     */
    handleFormSubmit = (event) => {
        event.preventDefault();

        const formData = domUtility.getFormData(this.form);
        const transformedData = apiUtility.transformLegacyFormData(formData);
        const editorResult = this.editor.getContents();
        transformedData["body"] = JSON.stringify(editorResult);

        console.log(formData);
        console.log(transformedData);

        // axios.post("/api/v2/discussions", transformedData)
        //     .then((response) => {
        //         console.log(response);
        //     }).catch((error) => {
        //         console.log(error);
        //     });

        return false;
    }
}

events.onVanillaReady(() => {
    console.log("Hello from the editor");
    new RichEditor(".bodybox-wrap .TextBoxWrapper");
});
