/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { fireEvent } from "@testing-library/react";
import { handleLegacyAttachments } from "@rich-editor/mountEditor";

describe("mountEditor - handleLegacyAttachments()", () => {
    const parser = new DOMParser();
    const mockContent = `<div class="test-handleLegacyAttachments">
    <div>
        <form method="post" action="/post/editcomment/1" autocomplete="off">
            <div>
                <input type="hidden" id="Form_CommentID" name="CommentID" value="1"></input>
                <div class="bodybox-wrap"><div class="richEditor"></div></div>
            </div>
        </form>
    </div>
    <div
        class="editor-upload-saved editor-upload-readonly"
        id="editor-uploads-commentid1"
        style="display: block;"
    >
        <div class="editor-file-preview file-owner" id="media-id-2" title="betelgeuse.png">
            <input type="hidden" name="MediaIDs[]" value="2" disabled="disabled"></input>
            <span class="editor-file-remove" title="Remove"></span>
            <span class="editor-file-reattach" title="Click to re-attach"></span>
        </div>
    </div>
</div>`;
    const mockHTML = parser.parseFromString(mockContent, "text/html");
    const richContainer = mockHTML.querySelector(".richEditor") as HTMLElement;

    it("Had attached file from another editor (e.g. html), we need to preserve it and its functionality in Rich2", async () => {
        vitest.useFakeTimers();

        const updatedForm = handleLegacyAttachments(richContainer, mockHTML);
        const originalParent = updatedForm?.closest(".test-handleLegacyAttachments");

        // readonly div should be hidden when editing
        const readonlyDiv = originalParent?.querySelector(".editor-upload-readonly") as HTMLDivElement;
        expect(readonlyDiv.style.display).toBe("none");

        // form should contain readonly content with input, remove button, reattach button etc
        const attachmentContainer = updatedForm?.querySelector(".editor-upload-previews") as HTMLDivElement;
        const input = attachmentContainer.querySelector(`input[name="MediaIDs[]"]`) as HTMLInputElement;
        const removeButton = attachmentContainer.querySelector(".editor-file-remove") as HTMLSpanElement;
        const reattachButton = attachmentContainer.querySelector(".editor-file-reattach") as HTMLSpanElement;
        [input, removeButton, reattachButton].forEach((element) => {
            expect(element).toBeDefined();
        });

        vitest.advanceTimersByTime(500);

        // click remove button
        fireEvent(
            removeButton,
            new MouseEvent("click", {
                bubbles: true,
                cancelable: true,
            }),
        );
        expect(
            attachmentContainer.querySelector(".editor-file-preview")?.classList.contains("editor-file-removed"),
        ).toBe(true);
        expect(attachmentContainer.querySelector(`input[name="RemoveMediaIDs[]"]`)).toBeDefined();
        expect(
            attachmentContainer.querySelector(`input[name="RemoveMediaIDs[]"]`)?.getAttribute("disabled"),
        ).toBeNull();

        // click reattach button
        fireEvent(
            reattachButton,
            new MouseEvent("click", {
                bubbles: true,
                cancelable: true,
            }),
        );
        expect(
            attachmentContainer.querySelector(".editor-file-preview")?.classList.contains("editor-file-removed"),
        ).toBe(false);
        expect(attachmentContainer.querySelector(`input[name="RemoveMediaIDs[]"]`)).toBeNull();
        expect(attachmentContainer.querySelector(`input[name="MediaIDs[]"]`)).toBeDefined();
        expect(attachmentContainer.querySelector(`input[name="MediaIDs[]"]`)?.getAttribute("disabled")).toBe("true");
    });
});
