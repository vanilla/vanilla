/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, onContent } from "@library/utility/appUtils";

onReady(handleRichEditorInputFormatterOptions);
onContent(handleRichEditorInputFormatterOptions);

function handleRichEditorInputFormatterOptions() {
    const inputFormatterSelect = document.getElementById("Form_Garden-dot-InputFormatter") as HTMLSelectElement;
    if (inputFormatterSelect) {
        updateRichFormValues(inputFormatterSelect.value);
        inputFormatterSelect.addEventListener("change", () => {
            updateRichFormValues(inputFormatterSelect.value);
        });
    }
}

function updateRichFormValues(inputFormatter: string) {
    const richFormGroups = document.querySelectorAll(".js-richFormGroup");
    if (inputFormatter === "Rich") {
        richFormGroups.forEach(group => {
            group.classList.remove("Hidden");
        });
    } else {
        richFormGroups.forEach(group => {
            group.classList.add("Hidden");
        });
    }
}
