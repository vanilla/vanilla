/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { FragmentEditorCommunication } from "@dashboard/appearance/fragmentEditor/FragmentEditorCommunication";
import { FragmentEditorParser } from "@dashboard/appearance/fragmentEditor/FragmentEditorParser";
import type { IFragmentEditorSettings } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Settings";
import type { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { useDebouncedInput } from "@dashboard/hooks";
import { uploadFile, type IUploadedFile } from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { CopyLinkButton } from "@library/forms/CopyLinkButton";
import { useMutation, type UseMutationResult } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useLastValue, useLocalStorage } from "@vanilla/react-utils";
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
    type Dispatch,
    type SetStateAction,
} from "react";
import type {
    MonacoEditorOptions,
    MonacoEditorTheme,
    MonacoError,
    TypeDefinitions,
} from "@library/textEditor/MonacoUtils";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import { useQueryParam } from "@library/routing/routingUtils";
import { useQueryStringSync } from "@library/routing/QueryString";
import { MonacoErrors } from "@dashboard/appearance/fragmentEditor/MonacoErrors";
import debounce from "lodash-es/debounce";

export type IFragmentEditorForm = Pick<
    FragmentsApi.Detail,
    "fragmentType" | "name" | "css" | "jsRaw" | "previewData" | "files" | "customSchema"
> & {
    fragmentUUID: string | null;
    fragmentRevisionUUID: string | null;
    isForm: true;
};

export const FragmentEditorEditorTabID = {
    IndexTsx: "index_tsx" as const,
    IndexCss: "index_css" as const,
    CustomOptions: "customOptions" as const,
    Preview: (data: IFragmentPreviewData) => `preview_${data.previewDataUUID}` as const,
    File: (file: IUploadedFile) => `file_${file.mediaID}` as const,
};
export type FragmentEditorEditorTabID =
    | "index_tsx"
    | "index_css"
    | "customOptions"
    | `preview_${string}`
    | `file_${string}`;

export type FragmentFieldForTab<T extends FragmentEditorEditorTabID> = T extends "index_tsx"
    ? "jsRaw"
    : T extends "index_css"
    ? "css"
    : T extends "customOptions"
    ? "customSchema"
    : T extends `file_${string}`
    ? "files"
    : T extends `preview_${string}`
    ? "previewData"
    : never;

type RawFragmentFieldValueFormTab<T extends FragmentEditorEditorTabID> = IFragmentEditorForm[FragmentFieldForTab<T>];
export type FragmentFieldValueForTab<T extends FragmentEditorEditorTabID> =
    RawFragmentFieldValueFormTab<T> extends Array<infer U> ? U : RawFragmentFieldValueFormTab<T>;

export function extractFragmentFieldValue<T extends FragmentEditorEditorTabID>(
    form: IFragmentEditorForm,
    tab: T,
): FragmentFieldValueForTab<T> | null {
    const formKey = fragmentEditorFieldForTab(tab);
    let value: any = form[formKey] ?? null;
    if (tab.startsWith("file_")) {
        // For files we need to find the right file object
        const fileID = parseInt(tab.split("_")[1]);
        const file = form.files.find((f) => f.mediaID === fileID);
        value = file ?? null;
    } else if (tab.startsWith("preview_")) {
        // For preview we need to find the right previewData object
        const previewDataUUID = tab.split("_")[1];
        const previewData = form.previewData.find((pd) => pd.previewDataUUID === previewDataUUID);
        value = previewData ?? null;
    }
    return value;
}

export function applyFragmentFieldUpdates<T extends FragmentEditorEditorTabID>(
    form: IFragmentEditorForm,
    tab: T,
    updated: FragmentFieldValueForTab<T>,
): IFragmentEditorForm {
    if (tab.startsWith("file_")) {
        // For files we need to replace the file object in the array
        const fileID = parseInt(tab.split("_")[1]);
        return {
            ...form,
            files: form.files?.map((f) => (f.mediaID === fileID ? (updated as any) : f)) ?? [updated as any],
        };
    } else if (tab.startsWith("preview_")) {
        // For preview we need to replace the previewData object in the array
        const previewDataUUID = tab.split("_")[1];
        return {
            ...form,
            previewData: form.previewData?.map((pd) =>
                pd.previewDataUUID === previewDataUUID ? (updated as any) : pd,
            ) ?? [updated as any],
        };
    }

    const changes = {
        [fragmentEditorFieldForTab(tab)]: updated as any,
    };

    const result = {
        ...form,
        ...changes,
    };

    return result;
}

export function fragmentEditorFieldForTab<TabID extends FragmentEditorEditorTabID>(
    tabID: TabID,
): FragmentFieldForTab<TabID> {
    switch (tabID) {
        case FragmentEditorEditorTabID.IndexTsx:
            return "jsRaw" as FragmentFieldForTab<TabID>;
        case FragmentEditorEditorTabID.IndexCss:
            return "css" as FragmentFieldForTab<TabID>;
        case FragmentEditorEditorTabID.CustomOptions:
            return "customSchema" as FragmentFieldForTab<TabID>;
        default:
            if (tabID.startsWith("file_")) {
                return "files" as FragmentFieldForTab<TabID>;
            } else if (tabID.startsWith("preview_")) {
                return "previewData" as FragmentFieldForTab<TabID>;
            }

            throw new Error(`Unknown tabID ${tabID} for fragmentEditorFieldForTab`);
    }
}

export const FragmentEditorInfoTabID = {
    Preview: "preview",
    Commits: "commits",
    Documentation: "documentation",
} as const;
export type FragmentEditorInfoTabID = (typeof FragmentEditorInfoTabID)[keyof typeof FragmentEditorInfoTabID];

type IEditorTabErrors = Partial<Record<FragmentEditorEditorTabID, React.ReactNode | null>>;

interface IFragmentEditorContext {
    fragmentUUID: string | null;

    isPreviewLoaded: boolean;
    form: IFragmentEditorForm;
    initialForm: IFragmentEditorForm;
    formIsDirty: boolean;
    updateForm: Dispatch<SetStateAction<Partial<IFragmentEditorForm>>>;
    reloadForm: (form: IFragmentEditorForm) => void;

    typeDefinitions: TypeDefinitions;
    saveFormMutation: UseMutationResult<FragmentsApi.Detail, any, Partial<FragmentsApi.CommitData> | undefined>;

    // Settings
    settings: IFragmentEditorSettings;
    updateSettings: Dispatch<SetStateAction<Partial<IFragmentEditorSettings>>>;
    editorTheme: MonacoEditorTheme;
    editorOptions: MonacoEditorOptions;

    // Preview state
    selectedPreviewDataIndex: number;
    setSelectedPreviewDataIndex: (index: number) => void;

    // Communication
    Communication: FragmentEditorCommunication;
    onCommunicationEstablished: (communication: FragmentEditorCommunication) => void;

    editorTabID: FragmentEditorEditorTabID;
    setEditorTabID: (tabID: FragmentEditorEditorTabID) => void;
    editorTabErrors: IEditorTabErrors;
    setEditorTabErrors: Dispatch<SetStateAction<IEditorTabErrors>>;
    infoTabID: FragmentEditorInfoTabID;
    setInfoTabID: (tabID: FragmentEditorInfoTabID) => void;
}
const EDITOR_SETTINGS_DEFAULTS: IFragmentEditorSettings = {
    formatOnSave: true,
    previewUpdateFrequency: "onChange",
    theme: "light",
    filesWidthPercentage: "60",
    fontSize: "12",
    inlayHints: true,
    showMinimap: "no",
};

const darkTheme: MonacoEditorTheme = {
    base: "vs-dark",
    inherit: true,
    rules: [],
    colors: {
        "editor.foreground": "#f8f8f2",
        "editor.background": "#110E1B",
        "editorCursor.foreground": "#f8f8f0",
        "editorWhitespace.foreground": "#3B3A32",
        "editorIndentGuide.activeBackground": "#9D550FB0",
        "editor.selectionHighlightBorder": "#222218",
    },
};

const lightTheme: MonacoEditorTheme = {
    base: "vs",
    inherit: true,
    rules: [],
    colors: {},
};

export const FragmentEditorContext = createContext<IFragmentEditorContext>(
    // Let it go boom if you don't hook it up.
    {
        fragmentUUID: null,
        isPreviewLoaded: false,
        initialForm: {} as any,
        form: {} as any,
        formIsDirty: false,
        updateForm: function (value: SetStateAction<Partial<IFragmentEditorForm>>): void {
            throw new Error("Function not implemented.");
        },
        reloadForm: function (): void {},
        typeDefinitions: {},
        saveFormMutation: {} as any,
        settings: { ...EDITOR_SETTINGS_DEFAULTS },
        updateSettings: function (value: SetStateAction<Partial<IFragmentEditorSettings>>): void {
            throw new Error("Function not implemented.");
        },
        editorTheme: lightTheme,
        editorOptions: {},
        selectedPreviewDataIndex: 0,
        setSelectedPreviewDataIndex: function (index: number): void {
            throw new Error("Function not implemented.");
        },
        Communication: new FragmentEditorCommunication(null, null),
        onCommunicationEstablished: function (communication: FragmentEditorCommunication): void {
            throw new Error("Function not implemented.");
        },
        editorTabID: FragmentEditorEditorTabID.IndexTsx,
        setEditorTabID: function (tabID: FragmentEditorEditorTabID): void {
            throw new Error("Function not implemented.");
        },
        editorTabErrors: {},
        setEditorTabErrors() {},
        infoTabID: FragmentEditorInfoTabID.Preview,
        setInfoTabID: function (tabID: FragmentEditorInfoTabID): void {
            throw new Error("Function not implemented.");
        },
    },
);

type IProps = Pick<IFragmentEditorContext, "typeDefinitions"> & {
    initialForm: IFragmentEditorForm;
    children: React.ReactNode;
    saveFormMutation: UseMutationResult<
        FragmentsApi.Detail,
        any,
        IFragmentEditorForm & Partial<FragmentsApi.CommitData>
    >;
};

export function FragmentEditorContextProvider(props: IProps) {
    const initialEditorTabID = useQueryParam("editorTabID", FragmentEditorEditorTabID.IndexTsx);
    const [editorTabID, setEditorTabID] = useState<FragmentEditorEditorTabID>(initialEditorTabID);
    const initialInfoTabID = useQueryParam("infoTabID", FragmentEditorInfoTabID.Preview);
    const [infoTabID, setInfoTabID] = useState<FragmentEditorInfoTabID>(initialInfoTabID);
    useQueryStringSync(
        { editorTabID, infoTabID },
        {
            editorTabID: FragmentEditorEditorTabID.IndexTsx,
            infoTabID: FragmentEditorInfoTabID.Preview,
        },
    );

    // Settings
    const [_settings, setSettings] = useLocalStorage<IFragmentEditorSettings>("fragmentEditorSettings", {
        ...EDITOR_SETTINGS_DEFAULTS,
    });

    const settings = useMemo(() => {
        // In case the value we loaded from local storage is missing some new properties we added.
        return {
            ...EDITOR_SETTINGS_DEFAULTS,
            ..._settings,
        };
    }, [_settings]);

    const updateSettings = useCallback(
        (settings: Partial<IFragmentEditorSettings>) => {
            setSettings((prev) => ({ ...prev, ...settings }));
        },
        [setSettings],
    );

    // Form
    const [initialForm, setInitialForm] = useState<IFragmentEditorForm>(props.initialForm);
    const [form, setForm] = useState<IFragmentEditorForm>(props.initialForm);

    const saveFormMutation = useMutation({
        async mutationFn(commit?: FragmentsApi.CommitData) {
            let formToSave = form;
            if (settings.formatOnSave) {
                formToSave = await FragmentEditorParser.prettifyForm(form);
                setForm(formToSave);
            }
            const result = await props.saveFormMutation.mutateAsync({ ...formToSave, ...commit });
            setInitialForm(formToSave);
            setFormIsDirty(false);

            // Make sure we update the preview
            Communication.sendMessage({
                type: "contentUpdate",
                javascript: currentFormRef.current.jsRaw,
                css: currentFormRef.current.css,
                previewData: currentPreviewDataRef.current,
            });

            return result;
        },
    });

    const reloadForm = useCallback((newForm: IFragmentEditorForm | FragmentsApi.Detail) => {
        setInitialForm({ ...newForm, isForm: true });
        setForm({ ...newForm, isForm: true });
        setFormIsDirty(false);
    }, []);

    useEffect(() => {
        // If the initial form is reloaded we may have a new fragmentUUID
        setForm((existing) => {
            return {
                ...existing,
                fragmentUUID: props.initialForm.fragmentUUID,
                fragmentRevisionUUID: props.initialForm.fragmentRevisionUUID,
            };
        });
    }, [props.initialForm]);

    const { fragmentUUID } = form;

    const [editorTabErrors, setEditorTabErrors] = useState<IEditorTabErrors>({});

    const [formIsDirty, setFormIsDirty] = useState(false);
    const updateForm = useCallback(
        (update: SetStateAction<Partial<IFragmentEditorForm>>) => {
            setFormIsDirty(true);

            setForm((prev) => {
                if (typeof update === "function") {
                    return update(prev) as any;
                } else {
                    return { ...prev, ...update };
                }
            });
        },
        [setForm],
    );

    const [selectedPreviewDataIndex, setSelectedPreviewDataIndex] = useState<number>(form.previewData.length - 1);

    const [isPreviewLoaded, setIsPreviewLoaded] = useState(false);

    // Sync the form with the preview frame/window.
    const [Communication, onCommunicationEstablished] = useState<FragmentEditorCommunication>(
        new FragmentEditorCommunication(window, null),
    );

    // We want our initial payload to use the latest values of the form and preview data at the time the communication is established.
    // By storing the values in a ref we can access the latest values in the event handler without having to constantly add/remove the event handler.
    const currentFormRef = useRef(form);
    const currentPreviewDataRef = useRef(form.previewData[selectedPreviewDataIndex]);
    useLayoutEffect(() => {
        currentFormRef.current = form;
        currentPreviewDataRef.current = form.previewData[selectedPreviewDataIndex];
    }, [form]);

    useEffect(() => {
        // Once the frame/window has loaded and we've established a communication channel, send over the latest content.
        Communication.onMessage((message) => {
            if (message.type === "previewLoadedAck") {
                setIsPreviewLoaded(true);
                // After a load make sure the latest content is sent over.
                Communication.sendMessage({
                    type: "contentUpdate",
                    javascript: currentFormRef.current.jsRaw,
                    css: currentFormRef.current.css,
                    previewData: currentPreviewDataRef.current,
                });
            }
        });
    }, [Communication]);

    const debouncedJs = useDebouncedInput(form.jsRaw, 500);
    useEffect(() => {
        if (settings.previewUpdateFrequency === "onSave") {
            // Don't send updates on every keystroke if we're set to "onSave"
            return;
        }
        Communication.sendMessage({
            type: "contentUpdate",
            javascript: debouncedJs,
        });
    }, [settings, debouncedJs, Communication]);

    useEffect(() => {
        if (settings.previewUpdateFrequency === "onSave") {
            // Don't send updates on every keystroke if we're set to "onSave"
            return;
        }
        Communication.sendMessage({
            type: "contentUpdate",
            previewData: form.previewData[selectedPreviewDataIndex],
        });
    }, [form.previewData, selectedPreviewDataIndex, Communication]);

    const debouncedCss = useDebouncedInput(form.css, 500);
    useEffect(() => {
        if (settings.previewUpdateFrequency === "onSave") {
            // Don't send updates on every keystroke if we're set to "onSave"
            return;
        }
        Communication.sendMessage({
            type: "contentUpdate",
            css: debouncedCss,
        });
    }, [debouncedCss, Communication]);

    return (
        <FragmentEditorContext.Provider
            value={{
                typeDefinitions: props.typeDefinitions,
                fragmentUUID: fragmentUUID,
                Communication,
                onCommunicationEstablished,
                isPreviewLoaded,
                setSelectedPreviewDataIndex,
                selectedPreviewDataIndex,
                form,
                initialForm,
                updateForm,
                reloadForm,
                formIsDirty,
                settings,
                updateSettings,
                editorTheme: settings.theme === "light" ? lightTheme : darkTheme,
                editorOptions: {
                    fontSize: parseInt(settings.fontSize),
                    useInlayHints: settings.inlayHints,
                    minimap: {
                        enabled: settings.showMinimap === "yes",
                    },
                },
                saveFormMutation,
                editorTabID,
                setEditorTabID,
                editorTabErrors,
                setEditorTabErrors,
                infoTabID,
                setInfoTabID,
            }}
        >
            {props.children}
        </FragmentEditorContext.Provider>
    );
}

export function useFragmentTabFormField<TabID extends FragmentEditorEditorTabID>(
    tab: TabID,
): {
    formKey: FragmentFieldForTab<TabID>;
    value: FragmentFieldValueForTab<TabID>;
    setValue: (newValue: FragmentFieldValueForTab<TabID>) => void;
    error: React.ReactNode | null;
    isDirty: boolean;
    isAdded: boolean;
    setError: (newError: string | null) => void;
    onMonacoValidate: (errors: MonacoError[]) => void;
} {
    const { editorTabErrors, setEditorTabErrors, initialForm, form, updateForm, editorTabID } = useFragmentEditor();
    const formKey = fragmentEditorFieldForTab(tab);

    let value = extractFragmentFieldValue(form, tab)!;
    const initialValue = extractFragmentFieldValue(initialForm, tab);
    const isAdded = initialValue == null && value != null;
    const isDirty = JSON.stringify(initialValue) !== JSON.stringify(value);
    const error = editorTabErrors[tab] ?? null;

    const setValue = useCallback(
        (newValue: FragmentFieldValueForTab<TabID>) => {
            updateForm((existing) => {
                return applyFragmentFieldUpdates(existing as IFragmentEditorForm, tab, newValue);
            });
        },
        [updateForm, formKey],
    );

    const setError = useCallback(
        (newError: React.ReactNode | null) => {
            setEditorTabErrors((prev) => ({
                ...prev,
                [tab]: newError,
            }));
        },
        [tab],
    );

    const onMonacoValidate = useCallback(
        debounce((errors: MonacoError[]) => {
            let filteredErrors = errors.filter((e) => {
                // Not importing MarkerSeverity because it brings in all of monaco.
                return (
                    e.severity === 8 || // MarkerSeverity.Error
                    (e.owner === "json" && e.severity >= 4) // MarkerSeverity.Warning
                );
            });

            if (filteredErrors.length > 0) {
                setError(<MonacoErrors errors={filteredErrors} />);
            } else {
                setError(null);
            }
        }, 100),
        [setError],
    );

    return { formKey, isAdded, value, setValue, error, isDirty, setError, onMonacoValidate };
}

export function useFragmentEditor() {
    return useContext(FragmentEditorContext);
}

export function useFragmentFileUploadMutation(params: {
    onSuccess?: (uploadedFile: IUploadedFile, newFiles: IUploadedFile[]) => void;
}) {
    const editor = useFragmentEditor();
    const toast = useToast();
    const uploadFileMutation = useMutation({
        mutationFn: async (file: File) => {
            const upload = await uploadFile(file);
            return upload;
        },
        onSuccess: (upload) => {
            // Notify
            toast.addToast({
                body: (
                    <span>
                        {t("File uploaded successfully")}

                        <CopyLinkButton buttonType={"textPrimary"} url={upload.url}>
                            {t("Copy File URL")}
                        </CopyLinkButton>
                    </span>
                ),
                dismissible: true,
                autoDismiss: true,
            });

            // Now push into our files
            const newFiles = editor.form.files.filter((f) => f.name !== upload.name);
            newFiles.push(upload);
            editor.updateForm({
                files: newFiles,
            });

            params?.onSuccess?.(upload, newFiles);
        },
    });

    return uploadFileMutation;
}
