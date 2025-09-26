/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { ChangeType } from "@dashboard/appearance/fragmentEditor/ChangeStatusIndicator";
import {
    FragmentEditorEditorTabID,
    type IFragmentEditorForm,
} from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import type { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import type { IUploadedFile } from "@library/apiv2";
import type { JsonSchema } from "@library/json-schema-forms";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import isEqual from "lodash-es/isEqual";

type IChangedUploadedFile = {
    type: "uploadedFile";
    oldUpload: IUploadedFile | null;
    newUpload: IUploadedFile | null;
};
type IChangedCode = {
    type: "code";
    oldCode: string;
    newCode: string;
    language: string;
    fileName: string;
};
type IChangedPreviewData = {
    type: "previewData";
    oldPreviewData: IFragmentPreviewData | null;
    newPreviewData: IFragmentPreviewData | null;
};

type IChangedCustomOptions = {
    type: "customOptions";
    oldCustomOptions: JsonSchema;
    newCustomOptions: JsonSchema;
};

export type IChangedItem = (IChangedUploadedFile | IChangedCode | IChangedPreviewData | IChangedCustomOptions) & {
    fileName: string;
    changeType: ChangeType;
    tabID: string;
};
export function diffFragmentRevisions(
    originalRevision: FragmentsApi.Detail | IFragmentEditorForm | null,
    modifiedRevision: FragmentsApi.Detail | IFragmentEditorForm,
): IChangedItem[] {
    const changes: IChangedItem[] = [];

    if (originalRevision?.jsRaw !== modifiedRevision.jsRaw) {
        changes.push({
            type: "code",
            oldCode: originalRevision?.jsRaw ?? "",
            newCode: modifiedRevision.jsRaw,
            language: "react",
            fileName: "index.tsx",
            changeType: "modified",
            tabID: FragmentEditorEditorTabID.IndexTsx,
        });
    }

    if (originalRevision?.css !== modifiedRevision.css) {
        changes.push({
            type: "code",
            oldCode: originalRevision?.css ?? "",
            newCode: modifiedRevision.css,
            fileName: "index.css",
            language: "css",
            changeType: "modified",
            tabID: FragmentEditorEditorTabID.IndexCss,
        });
    }

    if (JSON.stringify(originalRevision?.customSchema) !== JSON.stringify(modifiedRevision.customSchema)) {
        changes.push({
            type: "code",
            oldCode: JSON.stringify(originalRevision?.customSchema ?? { properties: {} }, null, 4),
            newCode: JSON.stringify(modifiedRevision.customSchema ?? { properties: {} }, null, 4),
            language: "json",
            fileName: "custom-options.json",
            changeType: "modified",
            tabID: FragmentEditorEditorTabID.CustomOptions,
        });
    }

    for (const originalUploadedFile of originalRevision?.files ?? []) {
        const modifiedFile = modifiedRevision.files.find((file) => file.mediaID === originalUploadedFile.mediaID);
        if (!modifiedFile) {
            // Deleted
            changes.push({
                type: "uploadedFile",
                oldUpload: originalUploadedFile,
                newUpload: null,
                fileName: originalUploadedFile.name,
                changeType: "removed",
                tabID: FragmentEditorEditorTabID.File(originalUploadedFile),
            });
        }
    }

    for (const modifiedUploadedFile of modifiedRevision.files) {
        const originalFile = originalRevision?.files.find((file) => file.mediaID === modifiedUploadedFile.mediaID);
        if (!originalFile) {
            // Added
            changes.push({
                type: "uploadedFile",
                oldUpload: null,
                newUpload: modifiedUploadedFile,
                fileName: modifiedUploadedFile.name,
                changeType: "added",
                tabID: FragmentEditorEditorTabID.File(modifiedUploadedFile),
            });
        }
    }

    // Now the preview datas.
    for (const originalPreviewData of originalRevision?.previewData ?? []) {
        const modifiedPreviewData = modifiedRevision.previewData.find(
            (previewData) => previewData.previewDataUUID === originalPreviewData.previewDataUUID,
        );
        if (!modifiedPreviewData) {
            // Deleted
            changes.push({
                type: "previewData",
                oldPreviewData: originalPreviewData,
                newPreviewData: null,
                fileName: `preview/${originalPreviewData.name}.json`,
                changeType: "removed",
                tabID: FragmentEditorEditorTabID.Preview(originalPreviewData),
            });
        } else {
            // We have preview data in both, but we need to compare them
            if (!isEqual(originalPreviewData, modifiedPreviewData)) {
                let fileName = `preview/${originalPreviewData.name}.json`;
                if (originalPreviewData.name !== modifiedPreviewData.name) {
                    fileName = `preview/${originalPreviewData.name}.json → preview/${modifiedPreviewData.name}.json`;
                }

                changes.push({
                    type: "previewData",
                    oldPreviewData: originalPreviewData,
                    newPreviewData: modifiedPreviewData,
                    changeType: "modified",
                    fileName,
                    tabID: FragmentEditorEditorTabID.Preview(modifiedPreviewData),
                });
            }
        }
    }

    for (const modifiedPreviewData of modifiedRevision.previewData) {
        const originalPreviewData = originalRevision?.previewData.find(
            (previewData) => previewData.previewDataUUID === modifiedPreviewData.previewDataUUID,
        );
        if (!originalPreviewData) {
            // Added
            changes.push({
                type: "previewData",
                oldPreviewData: null,
                newPreviewData: modifiedPreviewData,
                fileName: `preview/${modifiedPreviewData.name}.json`,
                changeType: "added",
                tabID: FragmentEditorEditorTabID.Preview(modifiedPreviewData),
            });
        }
    }

    return changes;
}
