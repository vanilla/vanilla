/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ManageIconsApi } from "@dashboard/appearance/manageIcons/ManageIconsApi";
import { useManageIconsForm } from "@dashboard/appearance/manageIcons/ManageIconsFormContext";
import type { IUserFragment } from "@library/@types/api/users";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { downloadAsFile } from "@vanilla/dom-utils";
import { t } from "@vanilla/i18n";
import { logError, promiseTimeout, uuidv4 } from "@vanilla/utils";
import dompurify from "dompurify";
import JSZip from "jszip";
// import { BlobWriter, TextReader, ZipWriter } from "@zip.js/zip.js";
import jszip from "jszip";

export function useActiveIconsQuery() {
    const query = useQuery({
        queryKey: ["icons", "active"],
        queryFn: async () => {
            return await ManageIconsApi.getActive();
        },
    });

    return query;
}

export function useSystemIconsQuery() {
    const query = useQuery({
        queryKey: ["icons", "system"],
        queryFn: async () => {
            return await ManageIconsApi.getSystem();
        },
    });

    return query;
}

export type IParsedBulkIcons = {
    icons: ManageIconsApi.IManagedIcon[];
    errors: Array<{ fileName: string; error: string }>;
};

export function useParseBulkIconZip() {
    const handleError = useToastErrorHandler();
    const user = useCurrentUser()!;

    return useMutation({
        mutationFn: async (file: File): Promise<IParsedBulkIcons> => {
            const validTypes = ["application/zip", "application/x-zip-compressed", "application/x-compressed"];
            if (!validTypes.includes(file.type)) {
                logError("Invalid icon pack file", file.type);
                throw new Error(
                    "File must be a application/zip file. Instead received a file with mime-type: " + file.type,
                );
            }
            const zip = await JSZip.loadAsync(file);

            const icons: ManageIconsApi.IManagedIcon[] = [];

            const svgFiles = Object.values(zip.files).filter((file) => file.name.endsWith(".svg"));
            const countSvgs = svgFiles.length;
            if (countSvgs === 0) {
                throw new Error("No SVGs were found in your zip file.");
            }
            const errors: Array<{ fileName: string; error: string }> = [];

            for (const file of svgFiles) {
                if (!file.name.endsWith(".svg")) {
                    continue;
                }

                // Try and process the file.

                try {
                    const name = file.name;
                    const svgRaw = await file.async("text");
                    const sanitizedSvg = sanitizeAndValidateSvg(svgRaw);
                    const dateInserted = file.date.toISOString();

                    icons.push({
                        iconName: extractIconNameFromFileName(name),
                        ...sanitizedSvg,
                        dateInserted,
                        insertUser: user,
                        insertUserID: user.userID,
                        iconUUID: uuidv4(),
                        isActive: false,
                        isCustom: true,
                    });
                } catch (e) {
                    errors.push({ fileName: file.name, error: e.message });
                }
            }
            return { icons, errors };
        },
        onError: handleError,
    });
}

function extractIconNameFromFileName(fileName: string): string {
    return fileName.replace(".svg", "").split("/").pop() ?? "";
}

export function useDownloadIconsBulkMutation(name: string, icons: ManageIconsApi.IManagedIcon[]) {
    const handleError = useToastErrorHandler();

    return useMutation({
        mutationFn: async () => {
            const zip = new JSZip();

            for (const icon of icons) {
                zip.file(`${icon.iconName}.svg`, icon.svgRaw, { date: new Date(icon.dateInserted) });
            }

            const zipBlob = await zip.generateAsync({ type: "blob" });

            // Honestly this feels "too fast" since everything is in memory already. Let's add a short delay.
            await promiseTimeout(1000);

            downloadAsFile(zipBlob, name, { fileExtension: "zip" });
        },
        onError: handleError,
    });
}

export function useBulkUploadIconMutation() {
    const queryClient = useQueryClient();
    const handleError = useToastErrorHandler();
    const toast = useToast();

    const mutation = useMutation({
        mutationKey: ["bulkUploadIcons"],
        mutationFn: async (icons: Array<{ iconName: string; svgRaw: string }>) => {
            return await ManageIconsApi.uploadIcons(icons);
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["icons"]);
            void toast.addToast({
                body: t("Icons uploaded successfully."),
                dismissible: true,
                autoDismiss: true,
            });
        },
        onError: handleError,
    });

    return mutation;
}

export function useUploadIconMutation(iconName: string) {
    const queryClient = useQueryClient();
    const handleError = useToastErrorHandler();

    const mutation = useMutation({
        mutationKey: ["uploadIcon", iconName],
        mutationFn: async (file: File) => {
            const contents = await readFileAsText(file);
            const sanitizedSvg = sanitizeAndValidateSvg(contents).svgRaw;

            return await ManageIconsApi.uploadIcon(iconName, sanitizedSvg);
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["icons"]);
        },
        onError: handleError,
    });

    return mutation;
}

export function useRestoreIconMutation(iconName: string) {
    const queryClient = useQueryClient();
    const handleError = useToastErrorHandler();

    const mutation = useMutation({
        mutationKey: ["restoreIcon", iconName],
        mutationFn: async (iconUUID: string) => {
            return await ManageIconsApi.restoreIcon(iconName, iconUUID);
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["icons"]);
        },
        onError: handleError,
    });

    return mutation;
}

export function useDeleteIconMutation(iconUUID: string) {
    const queryClient = useQueryClient();
    const handleError = useToastErrorHandler();

    const mutation = useMutation({
        mutationKey: ["deleteIcon", iconUUID],
        mutationFn: async () => {
            return await ManageIconsApi.deleteIcon(iconUUID);
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["icons"]);
        },
        onError: handleError,
    });

    return mutation;
}

export function useIconRevisions(iconName: string) {
    const query = useQuery({
        queryKey: ["icons", iconName],
        queryFn: async () => {
            return await ManageIconsApi.getRevisions(iconName);
        },
    });

    return query;
}

async function readFileAsText(file: File): Promise<string> {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            resolve((e.target?.result as string) ?? "");
        };
        reader.readAsText(file);
    });
}

/**
 * Now technically a site admin could still XSS themselves or the site if they use the API directly. They can already do that with all the scripting allowed.
 *
 * This sanitization is to prevent a case of an admin accidently downloading a malicious SVG and uploading it without realizing it, which is why it can live in the frontend.
 *
 * @param svgText
 * @returns
 */
function sanitizeAndValidateSvg(
    svgText: string,
): Pick<ManageIconsApi.IManagedIcon, "svgRaw" | "svgAttributes" | "svgContents" | "unsafeSvgRaw"> {
    const unsafeSvgRaw = svgText;
    // Replace colors before sanitization
    svgText = svgText.replace("#000000", "currentColor").replace("#555A62", "currentColor");

    // First we to sanitize the contents.
    const cleanedFragment = dompurify.sanitize(svgText, {
        USE_PROFILES: { svg: true, svgFilters: true },
        RETURN_DOM_FRAGMENT: true,
    });

    const svg = cleanedFragment.firstElementChild;
    if (!(svg instanceof SVGElement)) {
        throw new Error("Uploaded file is not a valid SVG");
    }

    if (!svg.getAttribute("viewBox")) {
        throw new Error("Uploaded SVG does not have a viewBox attribute.");
    }

    const attributes = getSvgAttributes(svg);

    return {
        unsafeSvgRaw,
        svgRaw: svg.outerHTML,
        svgAttributes: attributes,
        svgContents: svg.innerHTML,
    };
}

const getSvgAttributes = (element: Element) => {
    const attributes = element.attributes;
    const result: Record<string, string> = {};
    for (let i = 0; i < attributes.length; i++) {
        const attribute = attributes[i];
        result[attribute.name] = attribute.value;
    }

    if (result.style) {
        const style = result.style;
        const styleAttributes = style.split(";");
        let styleResult: Record<string, string> = {};
        for (const styleAttribute of styleAttributes) {
            const [key, value] = styleAttribute.split(":");
            styleResult[key] = value;
        }
        result.style = styleResult as any;
    }
    return result;
};
