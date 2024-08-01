/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureHtmlElement } from "@vanilla/dom-utils";
import { ForumEditor } from "@rich-editor/editor/ForumEditor";
import React from "react";
import { mountReact } from "@vanilla/react-utils";
import { getMeta } from "@library/utility/appUtils";
import { LegacyVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";

/**
 * Mount the editor into a DOM Node.
 *
 * @param containerSelector - The CSS selector or the HTML Element to render into.
 */
export default function mountEditor(containerSelector: string | Element, descriptionID?: string) {
    const container = ensureHtmlElement(containerSelector);
    const bodybox = container.closest("form")!.querySelector(".BodyBox");
    const placeholder = bodybox?.getAttribute("placeholder") ?? undefined;
    const isRich2 = getMeta("inputFormat.desktop")?.match(/rich2/i);

    const uploadEnabled = !!container.dataset.uploadenabled;
    const needsHtmlConversion = !!container.dataset.needshtmlconversion;
    const showConversionNotice = !!(container.dataset.showconversionnotice ?? needsHtmlConversion);

    if (!bodybox) {
        throw new Error("Could not find the BodyBox to mount editor to.");
    }

    const initialFormat = bodybox.getAttribute("format"); // Could be Rich or rich or any other format

    // This one is a special case when initial format is Rich, current format is Rich2 and we have the toggle to not reinterpret initial format to current
    // So we need to still render Rich. This may not be necessary when we deprecate Rich completely in favour of Rich2
    const forceForumEditor =
        isRich2 && !getMeta("inputFormat.reinterpretPostsAsRich") && initialFormat?.toLowerCase() === "rich";

    if (initialFormat?.match(/rich|2/i)) {
        handleLegacyAttachments(container);
        mountReact(
            isRich2 && !forceForumEditor ? (
                <LegacyVanillaEditor
                    initialFormat={initialFormat}
                    needsHtmlConversion={needsHtmlConversion}
                    legacyTextArea={bodybox as HTMLInputElement}
                    uploadEnabled={uploadEnabled}
                    showConversionNotice={showConversionNotice}
                />
            ) : (
                <ForumEditor
                    placeholder={placeholder}
                    legacyTextArea={bodybox as HTMLInputElement}
                    descriptionID={descriptionID ?? undefined}
                    uploadEnabled={uploadEnabled}
                    needsHtmlConversion={needsHtmlConversion}
                />
            ),
            container,
            () => {
                container.classList.remove("isDisabled");
            },
            { clearContents: true },
        );
    } else {
        throw new Error(`Unsupported initial editor format ${initialFormat}`);
    }
}

/**
 * We need to replicate legacy logic here, so if we have uploaded attachments
 * from other legacy editors (e.g. HTML), we move it to the form and ensure we can remove it.
 *
 * @param container rich editor container
 * @param documentFromCaller for testing purposes only
 */
export function handleLegacyAttachments(container: HTMLElement, documentFromCaller?: Document) {
    const globalDocument = documentFromCaller ?? document;
    const form = container.closest("form");
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    // find the attachment container and move its content into the form
    const formCommentId = form.querySelector<HTMLInputElement>("#Form_CommentID");
    const formDiscussionId = form.querySelector<HTMLInputElement>("#Form_DiscussionID");
    const formConversationId = form.querySelector<HTMLInputElement>("#Form_ConversationID");

    const isComment = formCommentId && parseInt(formCommentId.value) > 0;
    const isDiscussion = formDiscussionId && parseInt(formDiscussionId.value) > 0;
    const isConversation = formConversationId && parseInt(formConversationId.value) > 0;
    const isNewCommentForm = formCommentId && formCommentId.value === "" && (isDiscussion || isConversation);

    // determine form type
    let savedAttachmentSelector = "";
    let formType = "";
    let value = "";
    if (isComment) {
        formType = "CommentID";
        savedAttachmentSelector = `#editor-uploads-${formType.toLowerCase()}${formCommentId.value}`;
        value = formCommentId.value;
    } else if (isDiscussion) {
        formType = "DiscussionID";
        savedAttachmentSelector = `#editor-uploads-${formType.toLowerCase()}${formDiscussionId.value}`;
        value = formDiscussionId.value;
    } else if (isConversation) {
        formType = "ConversationID";
        savedAttachmentSelector = `#editor-uploads-${formType.toLowerCase()}${formConversationId.value}`;
        value = formConversationId.value;
    }

    // hide read-only attachments and move its content to the form
    if (!isNewCommentForm && savedAttachmentSelector !== "") {
        const savedAttachmentContainer = globalDocument.querySelector<HTMLDivElement>(savedAttachmentSelector);
        if (!savedAttachmentContainer) {
            return;
        }
        const editingForm = globalDocument
            .querySelector<HTMLFormElement>(`input#Form_${formType}[value="${value}"]`)
            ?.closest("form");
        if (savedAttachmentContainer && editingForm) {
            savedAttachmentContainer.style.display = "none";
            const clonedSavedAttachmentContainer = savedAttachmentContainer.cloneNode(true);

            const attachmentInForm = globalDocument.createElement("div");
            attachmentInForm.classList.add("editor-upload-previews");

            if (clonedSavedAttachmentContainer.childNodes.length) {
                clonedSavedAttachmentContainer.childNodes.forEach((node) => {
                    if (node instanceof HTMLDivElement && node.classList.contains("editor-file-preview")) {
                        const clonedNode = node.cloneNode(true) as HTMLDivElement;
                        attachmentInForm.appendChild(clonedNode);

                        // Attach event handlers to remove and reattach buttons
                        const removeButton = clonedNode.querySelector(".editor-file-remove");
                        const reattachButton = clonedNode.querySelector(".editor-file-reattach");
                        removeButton &&
                            removeButton.addEventListener("click", () => {
                                const previewInput = clonedNode.querySelector<HTMLInputElement>("input");
                                if (!previewInput) {
                                    return;
                                }
                                previewInput.setAttribute("name", "RemoveMediaIDs[]");
                                previewInput.id = "file-remove-" + previewInput.value;
                                previewInput.removeAttribute("disabled");
                                clonedNode.classList.add("editor-file-removed");
                            });
                        reattachButton &&
                            reattachButton.addEventListener("click", () => {
                                const previewInput = clonedNode.querySelector<HTMLInputElement>("input");
                                if (!previewInput) {
                                    return;
                                }
                                previewInput.setAttribute("name", "MediaIDs[]");
                                previewInput.removeAttribute("id");
                                previewInput.setAttribute("disabled", true);
                                clonedNode.classList.remove("editor-file-removed");
                            });
                    }
                });
            }

            const attachmentsParent = editingForm.querySelector(".bodybox-wrap");

            if (!attachmentsParent) {
                return;
            }

            attachmentsParent.appendChild(attachmentInForm);

            // show back read-only attachments when form submission is cancelled
            editingForm.addEventListener("X-ClearCommentForm", () => {
                savedAttachmentContainer.style.display = "block";
            });

            return editingForm;
        }
    }
}
