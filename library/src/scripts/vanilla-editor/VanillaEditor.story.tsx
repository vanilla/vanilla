/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { loadTranslations } from "@vanilla/i18n";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { ensureBuiltinEmbeds } from "@library/embeddedContent/embedService";
import { setMeta } from "@library/utility/appUtils";

export default {
    title: "VanillaEditor",
};

setMeta("upload.maxSize", 10 * 1024 * 1024);
setMeta("upload.allowedExtensions", [
    "txt",
    "jpg",
    "jpeg",
    "gif",
    "png",
    "bmp",
    "tiff",
    "ico",
    "zip",
    "gz",
    "tar.gz",
    "tgz",
    "psd",
    "ai",
    "pdf",
    "doc",
    "xls",
    "ppt",
    "docx",
    "xlsx",
    "pptx",
    "log",
    "rar",
    "7z",
]);
ensureBuiltinEmbeds();
loadTranslations({});
export function EditorPlayground() {
    return (
        <div>
            <StoryHeading>Rich Editor 2.0</StoryHeading>
            <StoryParagraph>
                This is a demo of the new version of Rich Editor. A lot of functionality is still a work in progress.
            </StoryParagraph>
            <VanillaEditor />
        </div>
    );
}
