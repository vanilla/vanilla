/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ChangeStatusIndicator } from "@dashboard/appearance/fragmentEditor/ChangeStatusIndicator";
import { ErrorIndicator } from "@dashboard/appearance/fragmentEditor/ErrorIndicator";
import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { FragmentEditorCommits } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Commits";
import {
    FragmentEditorEditorTabID,
    FragmentEditorInfoTabID,
    useFragmentEditor,
    useFragmentFileUploadMutation,
    useFragmentTabFormField,
} from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { FragmentEditorCss } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Css";
import { FragmentEditorCustomOptions } from "@dashboard/appearance/fragmentEditor/FragmentEditor.CustomOptions";
import { FragmentEditorDocumentation } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Documentation";
import { FragmentEditorFileViewer } from "@dashboard/appearance/fragmentEditor/FragmentEditor.FileViewer";
import { FragmentEditorJavascript } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Javascript";
import { FragmentEditorPreview } from "@dashboard/appearance/fragmentEditor/FragmentEditor.Preview";
import { FragmentEditorPreviewData } from "@dashboard/appearance/fragmentEditor/FragmentEditor.PreviewData";
import { FragmentEditorTitleBar } from "@dashboard/appearance/fragmentEditor/FragmentEditor.TitleBar";
import { cx } from "@emotion/css";
import type { IUploadedFile } from "@library/apiv2";
import Translate from "@library/content/Translate";
import { extractErrorMessage } from "@library/errorPages/CoreErrorMessages";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { getClassForButtonType } from "@library/forms/Button.getClassForButtonType";
import { ButtonType } from "@library/forms/buttonTypes";
import { UploadButton } from "@library/forms/UploadButton";
import { Row } from "@library/layout/Row";
import { useDragHandle } from "@library/layout/useDragHandle";
import ButtonLoader from "@library/loaders/ButtonLoader";
import ModalConfirm from "@library/modal/ModalConfirm";
import { EditorTabs } from "@library/textEditor/EditorTabs";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useDeferredFocuser, useGlobalClass } from "@vanilla/react-utils";
import { uuidv4 } from "@vanilla/utils";
import { useRef, useState } from "react";

export function FragmentEditor() {
    const classes = fragmentEditorClasses();
    const editor = useFragmentEditor();
    const { settings, form, updateForm } = editor;
    const [showConfirmDeletePreview, setShowConfirmDeletePreview] = useState<IFragmentPreviewData | null>(null);
    const [showConfirmDeleteFile, setShowConfirmDeleteFile] = useState<IUploadedFile | null>(null);

    const tabListRef = useRef<HTMLDivElement>(null);

    const { dragHandle, mainPanelRef } = useDragHandle({
        onWidthPercentageChange: (newWidth) => {
            editor.updateSettings({
                filesWidthPercentage: newWidth.toString(),
            });
        },
        initialWidthPercentage: parseInt(settings.filesWidthPercentage),
    });

    const deferredFocuser = useDeferredFocuser();

    const uploadFileMutation = useFragmentFileUploadMutation({
        onSuccess(uploadedFile, newFiles) {
            // Now go to that tab index
            const tabID = FragmentEditorEditorTabID.File(uploadedFile);
            editor.setEditorTabID(tabID);

            // Also make sure we scroll to it.
            if (tabListRef.current) {
                tabListRef.current.scrollTo({
                    left: tabListRef.current.offsetWidth,
                });
            }

            // And focus the tab
            deferredFocuser.focusElementBySelector(`#${tabID}`);
        },
    });

    // Inject some css variables for dark/light theming.
    useGlobalClass(settings.theme === "light" ? classes.lightVars : classes.darkVars);

    return (
        <div className={cx(classes.root)}>
            <FragmentEditorTitleBar />
            <div className={classes.row}>
                <div ref={mainPanelRef} className={classes.files}>
                    <EditorTabs.Root
                        eager={true}
                        value={editor.editorTabID}
                        onValueChange={editor.setEditorTabID}
                        orientation={"horizontal"}
                    >
                        <EditorTabs.List className={classes.tabButtons} ref={tabListRef}>
                            <EditorTabTrigger value={FragmentEditorEditorTabID.IndexTsx}>index.tsx</EditorTabTrigger>
                            <EditorTabTrigger value={FragmentEditorEditorTabID.IndexCss}>styles.css</EditorTabTrigger>
                            {form.fragmentType === "CustomFragment" && (
                                <EditorTabTrigger value={FragmentEditorEditorTabID.CustomOptions}>
                                    {t("custom-options.json")}
                                </EditorTabTrigger>
                            )}
                            {form.previewData.map((previewData) => (
                                <EditorTabTrigger
                                    key={previewData.previewDataUUID}
                                    value={FragmentEditorEditorTabID.Preview(previewData)}
                                    onDelete={() => {
                                        setShowConfirmDeletePreview(previewData);
                                    }}
                                >
                                    {`preview/${previewData.name}.json`}
                                </EditorTabTrigger>
                            ))}
                            {showConfirmDeletePreview && (
                                <ModalConfirm
                                    isVisible={true}
                                    onCancel={() => {
                                        setShowConfirmDeletePreview(null);
                                    }}
                                    title={t("Delete Preview Data")}
                                    onConfirm={() => {
                                        const previewDataIndex = editor.form.previewData.findIndex(
                                            (d) => d.previewDataUUID === showConfirmDeletePreview.previewDataUUID,
                                        );
                                        const previousPreviewData = editor.form.previewData[previewDataIndex - 1];
                                        const newTabToFocus =
                                            previousPreviewData != null
                                                ? FragmentEditorEditorTabID.Preview(previousPreviewData)
                                                : FragmentEditorEditorTabID.IndexTsx;

                                        const newForm = {
                                            ...editor.form,
                                            previewData:
                                                editor.form.previewData?.filter(
                                                    (d) =>
                                                        d.previewDataUUID !== showConfirmDeletePreview.previewDataUUID,
                                                ) ?? [],
                                        };
                                        if (previewDataIndex === editor.selectedPreviewDataIndex) {
                                            editor.setSelectedPreviewDataIndex(0); // Reset the selected preview data index if it was the currently selected one
                                        }
                                        updateForm(newForm);
                                        setShowConfirmDeletePreview(null);
                                        editor.setEditorTabID(newTabToFocus);
                                    }}
                                >
                                    <Translate
                                        source="Are you sure you want to delete the preview data '<0/>'?"
                                        c0={showConfirmDeletePreview.name}
                                    />
                                </ModalConfirm>
                            )}
                            {form.files.map((file) => (
                                <EditorTabTrigger
                                    key={file.mediaID}
                                    value={FragmentEditorEditorTabID.File(file)}
                                    onDelete={() => {
                                        setShowConfirmDeleteFile(file);
                                    }}
                                >
                                    {`files/${file.name}`}
                                </EditorTabTrigger>
                            ))}
                            {showConfirmDeleteFile && (
                                <ModalConfirm
                                    isVisible={true}
                                    onCancel={() => {
                                        setShowConfirmDeletePreview(null);
                                    }}
                                    title={t("Delete File")}
                                    onConfirm={() => {
                                        const fileIndex = editor.form.files.findIndex(
                                            (d) => d.mediaID === showConfirmDeleteFile.mediaID,
                                        );
                                        const previousFile = editor.form.files[fileIndex - 1];
                                        const newTabToFocus =
                                            previousFile != null
                                                ? FragmentEditorEditorTabID.File(previousFile)
                                                : FragmentEditorEditorTabID.IndexTsx;

                                        const newForm = {
                                            ...editor.form,
                                            files:
                                                editor.form.files?.filter(
                                                    (d) => d.mediaID !== showConfirmDeleteFile.mediaID,
                                                ) ?? [],
                                        };
                                        updateForm(newForm);
                                        setShowConfirmDeleteFile(null);
                                        editor.setEditorTabID(newTabToFocus);
                                    }}
                                >
                                    <Translate
                                        source="Are you sure you want to delete the file '<0/>'?"
                                        c0={showConfirmDeleteFile.name}
                                    />
                                </ModalConfirm>
                            )}
                            <DropDown
                                asReachPopover={true}
                                flyoutType={FlyoutType.LIST}
                                buttonType={ButtonType.INPUT}
                                buttonContents={
                                    <Row align={"center"} gap={8}>
                                        <Icon icon="add" size="compact" />
                                        {t("Add")}
                                    </Row>
                                }
                            >
                                <DropDownItemButton
                                    onClick={() => {
                                        const currentPreviewData = editor.form.previewData ?? [];

                                        const newPreviewData: IFragmentPreviewData = {
                                            previewDataUUID: uuidv4(),
                                            name: `Preview Data #${currentPreviewData.length + 1}`,
                                            data: {},
                                        };
                                        editor.updateForm({
                                            ...editor.form,
                                            previewData: [...currentPreviewData, newPreviewData],
                                        });
                                        const tabID = FragmentEditorEditorTabID.Preview(newPreviewData);
                                        editor.setEditorTabID(tabID);

                                        // Also make sure we scroll to it.
                                        if (tabListRef.current) {
                                            tabListRef.current.scrollTo({
                                                left: tabListRef.current.offsetWidth,
                                            });
                                        }

                                        // And focus the tab
                                        deferredFocuser.focusElementBySelector(`#${tabID}`);
                                    }}
                                >
                                    {t("Add Preview Data")}
                                </DropDownItemButton>
                                <DropDownItem>
                                    <UploadButton
                                        accessibleTitle={t("Upload a file")}
                                        buttonType={"custom"}
                                        className={dropDownClasses().action}
                                        onUpload={(file) => {
                                            uploadFileMutation.mutate(file);
                                        }}
                                    >
                                        <span className={dropDownClasses().text}>{t("Upload File")}</span>
                                        {uploadFileMutation?.error ? (
                                            <ToolTip label={extractErrorMessage(uploadFileMutation?.error)}>
                                                <ToolTipIcon>
                                                    <Icon icon="status-alert" />
                                                </ToolTipIcon>
                                            </ToolTip>
                                        ) : undefined}
                                        {uploadFileMutation.isLoading && (
                                            <ButtonLoader className={dropDownClasses().loader} />
                                        )}
                                    </UploadButton>
                                </DropDownItem>
                            </DropDown>
                        </EditorTabs.List>

                        <EditorTabs.Content value={FragmentEditorEditorTabID.IndexTsx}>
                            <FragmentEditorJavascript />
                        </EditorTabs.Content>
                        <EditorTabs.Content value={FragmentEditorEditorTabID.IndexCss}>
                            <FragmentEditorCss />
                        </EditorTabs.Content>
                        {form.fragmentType === "CustomFragment" && (
                            <EditorTabs.Content value={FragmentEditorEditorTabID.CustomOptions}>
                                <FragmentEditorCustomOptions />
                            </EditorTabs.Content>
                        )}
                        {form.previewData.map((previewData) => (
                            <EditorTabs.Content
                                key={previewData.previewDataUUID}
                                value={FragmentEditorEditorTabID.Preview(previewData)}
                            >
                                <FragmentEditorPreviewData
                                    previewData={previewData}
                                    onChange={(newVal) => {
                                        updateForm({
                                            previewData: form.previewData.map((d) =>
                                                d.name === previewData.name ? { ...d, ...newVal } : d,
                                            ),
                                        });
                                    }}
                                />
                            </EditorTabs.Content>
                        ))}
                        {form.files.map((file) => (
                            <EditorTabs.Content key={file.mediaID} value={FragmentEditorEditorTabID.File(file)}>
                                <FragmentEditorFileViewer uploadedFile={file} />
                            </EditorTabs.Content>
                        ))}
                    </EditorTabs.Root>
                </div>
                {dragHandle}
                <div className={classes.docs}>
                    <EditorTabs.Root eager={true} value={editor.infoTabID} onValueChange={editor.setInfoTabID}>
                        <EditorTabs.List className={classes.tabButtons}>
                            <EditorTabs.Trigger value={FragmentEditorInfoTabID.Preview}>
                                {t("Preview")}
                            </EditorTabs.Trigger>
                            <EditorTabs.Trigger value={FragmentEditorInfoTabID.Commits}>
                                {t("Commits")}
                            </EditorTabs.Trigger>
                            <EditorTabs.Trigger value={FragmentEditorInfoTabID.Documentation}>
                                {t("Documentation")}
                            </EditorTabs.Trigger>
                        </EditorTabs.List>
                        <EditorTabs.Content value={FragmentEditorInfoTabID.Preview}>
                            <FragmentEditorPreview />
                        </EditorTabs.Content>
                        <EditorTabs.Content value={FragmentEditorInfoTabID.Commits}>
                            <FragmentEditorCommits />
                        </EditorTabs.Content>
                        <EditorTabs.Content value={FragmentEditorInfoTabID.Documentation}>
                            <FragmentEditorDocumentation />
                        </EditorTabs.Content>
                    </EditorTabs.Root>
                </div>
            </div>
        </div>
    );
}

function EditorTabTrigger(props: {
    onDelete?: () => void;
    value: FragmentEditorEditorTabID;
    children: React.ReactNode;
}) {
    const tabID = props.value;
    const editor = useFragmentEditor();
    const field = useFragmentTabFormField(props.value);
    let indicator: React.ReactNode = null;

    if (field.error) {
        indicator = <ErrorIndicator error={field.error} />;
    } else if (field.isAdded) {
        indicator = <ChangeStatusIndicator changeType={"added"} />;
    } else if (field.isDirty) {
        indicator = <ChangeStatusIndicator changeType={"modified"} />;
    }

    return (
        <EditorTabs.Trigger value={props.value} id={props.value}>
            {indicator}
            {props.children}
            {props.onDelete && (
                <span
                    tabIndex={editor.editorTabID === tabID ? 0 : -1}
                    style={{ marginRight: -6 }}
                    className={getClassForButtonType("iconCompact")}
                    role="button"
                    onKeyDown={(e) => {
                        if (e.key === "Enter" || e.key === " ") {
                            e.preventDefault();
                            (e.target as HTMLElement).click();
                        }
                    }}
                    onClick={() => {
                        props.onDelete?.();
                    }}
                >
                    <Icon icon="delete" size={"compact"} />
                </span>
            )}
        </EditorTabs.Trigger>
    );
}
