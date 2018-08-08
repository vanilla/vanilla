/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { onReady, onContent } from "@dashboard/application";

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
