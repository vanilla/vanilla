/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { mountModal } from "@library/modal/mountModal";
import { delegateEvent } from "@vanilla/dom-utils";
import { DeleteCategoryModal } from "@vanilla/addon-vanilla/categories/DeleteCategoryModal";
import { onReady, onContent } from "@library/utility/appUtils";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { CategoryPicker } from "@library/forms/select/CategoryPicker";
import { addComponent } from "@library/utility/componentRegistry";
import { CommunityMemberInput } from "@vanilla/addon-vanilla/forms/CommunityMemberInput";
cssOut(`.suggestedTextInput-option`, suggestedTextStyleHelper({ forDashboard: true }).option);
addComponent("CategoryPicker", CategoryPicker, { overwrite: true });
addComponent("CommunityMemberInput", CommunityMemberInput, { overwrite: true });
onReady(handleImageUploadInputDisplay);
onContent(handleImageUploadInputDisplay);

function handleImageUploadInputDisplay() {
    const imageUploadEnabled = document.getElementById("Form_ImageUpload-dot-Limits-dot-Enabled") as HTMLInputElement;

    if (imageUploadEnabled) {
        const displayClass = "dimensionsDisabled";
        const imageUploadDimensions = Array.from(document.getElementsByClassName("ImageUploadLimitsDimensions"));

        if (imageUploadEnabled.checked) {
            imageUploadDimensions.forEach((input) => {
                input.classList.remove(displayClass);
            });
        }

        imageUploadEnabled.addEventListener("click", () => {
            if (imageUploadEnabled.checked) {
                imageUploadDimensions.forEach((input) => {
                    input.classList.remove(displayClass);
                });
            } else {
                imageUploadDimensions.forEach((input) => {
                    input.classList.add(displayClass);
                });
            }
        });
    }
}

delegateEvent("click", ".dropdown-menu-link-delete-delete", (event, triggeringElement) => {
    event.preventDefault();
    const categoryID = triggeringElement.getAttribute("data-categoryid");
    const discussionsCount = triggeringElement.getAttribute("data-countDiscussions");
    if (categoryID === null) {
        return;
    }
    mountModal(
        <DeleteCategoryModal
            categoryID={parseInt(categoryID)}
            discussionsCount={discussionsCount !== null ? parseInt(discussionsCount) : undefined}
        />,
    );
});
