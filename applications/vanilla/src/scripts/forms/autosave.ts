import { siteUrl } from "@library/utility/appUtils";
import axios from "axios";
import qs from "qs";
import debounce from "lodash/debounce";

/** Attach auto save function after vanilla is loaded */
window.onVanillaReady(function () {
    // Get the parent form
    const formContainer = document.querySelector(
        "body.Post #DiscussionForm, body.Discussion .CommentForm",
    ) as HTMLElement;

    // Attach listeners to all inputs within
    if (formContainer) {
        const userInputs = formContainer?.querySelectorAll("input, textarea");
        [].slice.call(userInputs).forEach((input: HTMLInputElement) => {
            input.addEventListener("input", function () {
                saveDraft(formContainer);
            });
        });
    }
});

/** Used to make the save draft requests */
const apiv1 = axios.create({
    baseURL: siteUrl("/"),
    transformRequest: [(data) => qs.stringify(data)],
    paramsSerializer: (params) => qs.stringify(params),
});

/** Poor mans "state" so we don't post drafts without changes */
let previousValues = {
    Name: "",
    Body: "",
    CategoryID: "",
    Tags: "",
    CommentID: "",
};

/** Will return true if any values have been updated from the last function call */
const hasFormValuesChanged = (formData: FormData): boolean => {
    return Object.keys(previousValues).some((key) => {
        if (formData.has(key) && formData.get(key) !== previousValues[key]) {
            previousValues[key] = formData.get(key);
            return true;
        } else {
            previousValues[key] = formData.get(key);
            return false;
        }
    });
};

/** Takes any form and will return its values as an object */
const generateFormRequestBody = (form: HTMLFormElement): object | null => {
    if (form) {
        const data = new FormData(form);

        // If nothing has changed
        if (!hasFormValuesChanged(data)) {
            return null;
        }

        // If form body is empty
        const formBody = data.get("Body");
        if (`${formBody}`.length === 0 || `${formBody}` === '[{"insert":"\\n"}]') {
            return null;
        }

        // Return the object
        return Object.fromEntries(data.entries());
    }
    return null;
};

/**
 * This debounced function will take a DOM element which contains a form and submit
 * that forms content as a draft to the action specified in the form itself
 */
const saveDraft = debounce(
    async function (formContainer: HTMLElement) {
        // Find the form within the element passed in
        const form = formContainer.querySelector("form");
        // Check if the form has DraftID field (indicating that it can accept drafts)
        const draftID = form && (form.querySelector('input[id*="DraftID"]') as HTMLInputElement);

        if (form && draftID) {
            // Get some info from the form
            const discussionID = form.querySelector('input[id*="DiscussionID"]') as HTMLInputElement;

            // Generate the payload
            const requestBody = generateFormRequestBody(form);

            // Generate the endpoint
            const endpoint = form.getAttribute("action");

            // Comments needs slightly different handling
            const isComment = endpoint?.includes("comment");

            const params = isComment
                ? {
                      discussionid: discussionID.value,
                  }
                : {};

            // This will return the complete request body (with KvPs for discussion or comments)
            const getMergedRequestBody = () => {
                // These are the form inputs and comment keys
                const common = {
                    ...requestBody,
                    DeliveryType: "VIEW",
                    DeliveryMethod: "JSON",
                };
                // Comments require a Type and LastCommentID while Discussion require Save Draft
                return isComment ? { ...common, Type: "Draft" } : { ...common, "Save Draft": "Save Draft" };
            };

            // If we have something to send and somewhere to send it
            if (requestBody && endpoint) {
                const response = await apiv1.post(endpoint, getMergedRequestBody(), { params });

                // Initial forms do not have a draft ID
                if (draftID.value != response.data["DraftID"]) {
                    draftID.value = response.data["DraftID"] ?? 0;
                }
            }
        }
    },
    3000,
    { maxWait: 10000 },
); // How long to wait before executing the above code. Called from input events.
