import {
    useFragmentEditor,
    type IFragmentEditorForm,
} from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { FragmentEditorParser } from "@dashboard/appearance/fragmentEditor/FragmentEditorParser";
import type { IFragmentListFilters } from "@dashboard/appearance/fragmentEditor/FragmentListFilters";
import { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import type { JsonSchema } from "@library/json-schema-forms";
import type { IWithPaging } from "@library/navigation/SimplePagerModel";
import { getRegisteredFragments, loadFragmentDefinition } from "@library/utility/fragmentsRegistry";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { labelize } from "@vanilla/utils";

export function useInitialFragmentForm(params: {
    fragmentType: string;
    fragmentUUID: string | null;
    isCopy?: boolean;
}) {
    const { fragmentType, fragmentUUID, isCopy } = params;
    const formQuery = useQuery({
        queryKey: ["fragments", "initial", isCopy, fragmentType, fragmentUUID],
        queryFn: async (): Promise<IFragmentEditorForm> => {
            if (!fragmentUUID) {
                // Try to make one from the widget fragment meta.
                const fragmentMeta = getRegisteredFragments()[fragmentType] ?? null;
                if (!fragmentMeta) {
                    throw new Error(`No widget fragment found for type ${fragmentType}`);
                }

                const loadedMeta = await loadFragmentDefinition(fragmentMeta);

                return {
                    fragmentType: fragmentType,
                    name: labelize(fragmentType) + " Template (copy)",
                    css: loadedMeta.templateCss ?? "",
                    jsRaw: loadedMeta.templateTsx,
                    previewData: loadedMeta.previewData,
                    files: [],
                    fragmentRevisionUUID: null,
                    fragmentUUID: null,
                    isForm: true,
                };
            }

            // Let's go lookup from the api.
            const response = await FragmentsApi.get(fragmentUUID, { status: "latest" });
            return {
                // Notably only copying certain properties here.
                // Some code paths in the editor compare the form vs a revision and the runtime code can't differenciate between the two if we just copy a full actual revision into the form.
                fragmentType: response.fragmentType,
                name: isCopy ? response.name + " " + t("(copy)") : response.name,
                css: response.css,
                jsRaw: response.jsRaw,
                previewData: response.previewData,
                files: response.files,
                fragmentRevisionUUID: isCopy ? null : response.fragmentRevisionUUID,
                fragmentUUID: isCopy ? null : response.fragmentUUID,
                customSchema: response.customSchema,
                isForm: true,
            };
        },
        keepPreviousData: true,
    });
    return formQuery;
}

export function useFragmentCommits(fragmentUUID: string | null) {
    return useQuery({
        queryKey: ["fragmentCommits", fragmentUUID],
        queryFn: async () => {
            if (!fragmentUUID) {
                return {
                    data: [],
                    paging: {
                        page: 1,
                    },
                } as IWithPaging<FragmentsApi.Fragment[]>;
            }

            const revisions = FragmentsApi.getRevisions(fragmentUUID, {
                page: 1,
                limit: 50,
            });

            return revisions;
        },
    });
}

export function usePreviewDataSchema(fragmentType: string): JsonSchema {
    const catalog = useLayoutCatalog("all");
    const schema = catalog?.fragments?.[fragmentType]?.schema ?? {
        type: "object",
        properties: {},
    };
    return schema;
}

export function useActiveFragments(params: Pick<FragmentsApi.IndexParams, "appliedStatus" | "fragmentType">) {
    return useQuery({
        queryKey: ["fragments", params],
        queryFn: async (): Promise<FragmentsApi.Fragment[]> => {
            return await FragmentsApi.index({ sort: "-dateRevisionInserted", status: "active", ...params });
        },
        keepPreviousData: true,
    });
}

export function useSaveFragmentFormMutation(params: {
    fragmentUUID: string | null;
    onSuccess?: (fragment: FragmentsApi.Detail) => void;
}) {
    const { fragmentUUID, onSuccess } = params;
    const toast = useToast();
    const queryClient = useQueryClient();
    const errorHandler = useToastErrorHandler();

    const saveMutation = useMutation({
        mutationFn: async (form: IFragmentEditorForm & Partial<FragmentsApi.CommitData>) => {
            const { fragmentType, ...restForm } = form;
            const js = await FragmentEditorParser.transformJs(form.jsRaw);

            let detail: FragmentsApi.Detail;
            if (fragmentUUID) {
                detail = await FragmentsApi.patch(fragmentUUID, { ...restForm, js });
            } else {
                detail = await FragmentsApi.post({
                    ...form,
                    js,
                });
            }

            toast.addToast({
                body: form.commitMessage
                    ? t("Fragment Changes Commited")
                    : fragmentUUID
                    ? t("Fragment Draft Saved")
                    : t("Fragment Created"),
                autoDismiss: true,
            });

            onSuccess?.(detail);
            await queryClient.invalidateQueries(["fragmentCommits", detail.fragmentUUID]);
            void queryClient.invalidateQueries(["fragments"]);
            return detail;
        },
        async onSuccess(detail) {},
        onError: errorHandler,
        mutationKey: ["saveWidgetFragment", fragmentUUID],
    });

    return saveMutation;
}

export function useActiveRevisionQuery(fragmentUUID: string | null) {
    const activeRevisionQuery = useQuery({
        queryKey: ["fragments", fragmentUUID, "active"],
        queryFn: async () => {
            if (!fragmentUUID) {
                return null;
            }
            return FragmentsApi.get(fragmentUUID!, { status: "active" });
        },
        keepPreviousData: true,
    });
    return activeRevisionQuery;
}

export function useCommitFragmentMutation(params: {
    fragmentUUID: string | null;
    fragmentRevisionUUID: string | null;
    onSuccess?: () => void;
}) {
    const { fragmentUUID, fragmentRevisionUUID, onSuccess } = params;
    const toast = useToast();
    const queryClient = useQueryClient();
    const toastError = useToastErrorHandler();
    const commitMutation = useMutation({
        mutationFn: async (commit: FragmentsApi.CommitData) => {
            if (!fragmentUUID || !fragmentRevisionUUID) {
                return;
            }
            await FragmentsApi.commitRevision(fragmentUUID, { ...commit, fragmentRevisionUUID });
        },
        async onSuccess() {
            toast.addToast({
                body: t("Changes Committed"),
                autoDismiss: true,
            });

            await queryClient.invalidateQueries(["fragmentCommits", fragmentUUID]);
            void queryClient.invalidateQueries(["fragments"]);
            onSuccess?.();
        },
        onError: toastError,
        mutationKey: ["commitWidgetFragment", fragmentUUID],
    });

    return commitMutation;
}

export function useDeleteFragmentMutation(params: { fragmentUUID: string; onSuccess?: () => void }) {
    const { fragmentUUID, onSuccess } = params;
    const toast = useToast();
    const queryClient = useQueryClient();
    const toastError = useToastErrorHandler();
    const deleteMutation = useMutation({
        mutationFn: async (params: { fragmentRevisionUUID?: string }) => {
            await FragmentsApi.delete(fragmentUUID, params.fragmentRevisionUUID);
        },
        async onSuccess() {
            toast.addToast({
                body: t("Fragment Deleted"),
                autoDismiss: true,
            });

            void queryClient.invalidateQueries(["fragmentCommits", fragmentUUID]);
            void queryClient.invalidateQueries(["fragments"]);
            onSuccess?.();
        },
        onError: toastError,
        mutationKey: ["deleteWidgetFragment", fragmentUUID],
    });

    return deleteMutation;
}

export function useDeleteFragmentDraftMutation(params: {
    fragmentUUID: string | null;
    fragmentRevisionUUID: string | null;
    onSuccess?: () => void;
}) {
    const { fragmentUUID, fragmentRevisionUUID, onSuccess } = params;
    const editor = useFragmentEditor();
    const toast = useToast();
    const queryClient = useQueryClient();
    const toastError = useToastErrorHandler();
    const deleteMutation = useMutation({
        mutationFn: async () => {
            if (!fragmentUUID || !fragmentRevisionUUID) {
                return;
            }
            await FragmentsApi.delete(fragmentUUID, fragmentRevisionUUID);
        },
        async onSuccess() {
            toast.addToast({
                body: t("Draft Deleted"),
                autoDismiss: true,
            });

            void queryClient.invalidateQueries(["fragmentCommits", fragmentUUID]);
            void queryClient.invalidateQueries(["fragments"]);
            onSuccess?.();

            // Get the new latest revision

            if (!fragmentUUID) {
                return;
            }
            const latestRevision = await FragmentsApi.get(fragmentUUID, { status: "latest" });
            editor.reloadForm({ ...latestRevision, isForm: true });
        },
        onError: toastError,
        mutationKey: ["deleteWidgetFragment", fragmentUUID],
    });

    return deleteMutation;
}
