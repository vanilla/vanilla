/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ChangeStatusIndicator } from "@dashboard/appearance/fragmentEditor/ChangeStatusIndicator";
import { ErrorIndicator } from "@dashboard/appearance/fragmentEditor/ErrorIndicator";
import { FragmentCommitMeta } from "@dashboard/appearance/fragmentEditor/FragmentCommitMeta";
import {
    useFragmentEditor,
    type IFragmentEditorForm,
} from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { FragmentEditorFileViewer } from "@dashboard/appearance/fragmentEditor/FragmentEditor.FileViewer";
import { FragmentEditorCommitForm } from "@dashboard/appearance/fragmentEditor/FragmentEditorCommitForm";
import { diffFragmentRevisions } from "@dashboard/appearance/fragmentEditor/FragmentEditorDiffBuilder";
import { fragmentEditorDiffViewerClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditorDiffViewer.classes";
import { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import {
    useActiveRevisionQuery,
    useCommitFragmentMutation,
    useDeleteFragmentDraftMutation,
    useDeleteFragmentMutation,
    usePreviewDataSchema,
} from "@dashboard/appearance/fragmentEditor/FragmentsApi.hooks";
import { css } from "@emotion/css";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { useToastErrorHandler } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import FlexSpacer from "@library/layout/FlexSpacer";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { useDragHandle } from "@library/layout/useDragHandle";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { MonacoDiffEditor } from "@library/textEditor/MonacoDiffEditor";
import { PlainTextDiff } from "@library/textEditor/PlainTextDiff";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import * as RadixTabs from "@radix-ui/react-tabs";
import { useMutation, useQuery } from "@tanstack/react-query";
import { formatList, t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useLocalStorage } from "@vanilla/react-utils";
import { useEffect, useMemo, useState } from "react";

interface IProps {
    modifiedRevisionUUID: string | "latest";
    onClose: () => void;
}

export function FragmentEditorDiffViewer(props: IProps) {
    const { modifiedRevisionUUID } = props;

    const { fragmentUUID, form } = useFragmentEditor();

    const activeRevisionQuery = useActiveRevisionQuery(fragmentUUID);

    const modifiedRevisionQuery = useQuery({
        queryKey: ["fragmentRevision", fragmentUUID, modifiedRevisionUUID, form],
        queryFn: async () => {
            if (modifiedRevisionUUID === "latest" || !fragmentUUID) {
                return form;
            }

            return FragmentsApi.get(fragmentUUID, { fragmentRevisionUUID: modifiedRevisionUUID });
        },
        keepPreviousData: true,
    });

    useEffect(() => {
        return () => {
            modifiedRevisionQuery.remove();
        };
    }, []);

    const classes = fragmentEditorDiffViewerClasses();

    return (
        <Modal size={ModalSizes.XXL} isVisible={true} exitHandler={props.onClose}>
            <Frame
                canGrow={true}
                header={
                    <FrameHeader
                        title={
                            activeRevisionQuery.isSuccess && activeRevisionQuery.data == null ? (
                                <>{t("Initial Commit")}</>
                            ) : (
                                <>
                                    <span className={classes.title}>{t("Comparing")}</span>
                                    <span className={classes.revisionGroup}>
                                        {modifiedRevisionQuery.isSuccess && (
                                            <RevisionMeta fragment={modifiedRevisionQuery.data} />
                                        )}
                                        <Icon icon={"move-right"} />
                                        {activeRevisionQuery.isSuccess && activeRevisionQuery.data && (
                                            <RevisionMeta fragment={activeRevisionQuery.data} />
                                        )}
                                    </span>
                                </>
                            )
                        }
                        closeFrame={props.onClose}
                    />
                }
                body={
                    <FrameBody selfPadded={true} className={classes.frameBody}>
                        <QueryLoader
                            query={activeRevisionQuery}
                            query2={modifiedRevisionQuery}
                            success={(active, modified) => {
                                return (
                                    <DiffViewImpl
                                        originalRevision={active}
                                        modifiedRevision={modified}
                                        onClose={props.onClose}
                                    />
                                );
                            }}
                        />
                    </FrameBody>
                }
            />
        </Modal>
    );
}

function RevisionMeta(props: { fragment: FragmentsApi.Detail | IFragmentEditorForm }) {
    const { fragment } = props;

    const classes = fragmentEditorDiffViewerClasses();

    const title = "commitMessage" in fragment ? fragment.commitMessage : "Unsaved Changes";

    return (
        <span className={classes.revisionItem}>
            <strong className={classes.revisionName} title={title}>
                {title}
            </strong>
            {/* It's only an actual revision if there is a status in it. */}
            {"status" in fragment && <FragmentCommitMeta fragment={fragment} />}
        </span>
    );
}

function DiffViewImpl(props: {
    originalRevision: FragmentsApi.Detail | IFragmentEditorForm | null;
    modifiedRevision: FragmentsApi.Detail | IFragmentEditorForm;
    onClose: () => void;
}) {
    const { originalRevision, modifiedRevision } = props;

    const [filesWidthPercentage, setFilesWidthPercentage] = useLocalStorage("fragmentEditorDiffWidth", 30);

    const { dragHandle, mainPanelRef } = useDragHandle({
        onWidthPercentageChange: setFilesWidthPercentage,
        initialWidthPercentage: filesWidthPercentage,
    });

    const diff = useMemo(() => {
        return diffFragmentRevisions(originalRevision, modifiedRevision);
    }, [originalRevision, modifiedRevision]);

    const [selectedFileIndex, setSelectedFileIndex] = useState("0");

    // Note we may not actually be in the editor so only use the parts that have a defualt impl.
    const editor = useFragmentEditor();
    const schema = usePreviewDataSchema(modifiedRevision.fragmentType);

    const onError = useToastErrorHandler();
    const commitExistingCommitMutation = useCommitFragmentMutation({
        fragmentUUID: editor.form.fragmentUUID,
        fragmentRevisionUUID: editor.form.fragmentRevisionUUID,
        onSuccess() {
            props.onClose();
        },
    });

    const commitCurrentMutation = useMutation({
        async mutationFn(data: FragmentsApi.CommitData) {
            await editor.saveFormMutation.mutateAsync(data);
        },
        onSuccess() {
            props.onClose();
        },
        onError,
    });

    const commitMutation = "isForm" in modifiedRevision ? commitCurrentMutation : commitExistingCommitMutation;

    const deleteDraftMutation = useDeleteFragmentDraftMutation({
        fragmentUUID: modifiedRevision.fragmentUUID,
        fragmentRevisionUUID: modifiedRevision.fragmentRevisionUUID,
    });
    const classes = fragmentEditorDiffViewerClasses();

    if (diff.length === 0) {
        // There were no changes.
        return (
            <div className={classes.emptyMessage}>
                <CoreErrorMessages
                    noIcon={true}
                    error={{
                        message: t("No changes detected"),
                        description: t("There are no changes between the two revisions."),
                        actionItem: modifiedRevision.fragmentRevisionUUID ? (
                            <Button
                                disabled={deleteDraftMutation.isLoading}
                                onClick={() => {
                                    deleteDraftMutation.mutate();
                                    props.onClose();
                                }}
                                buttonType={"input"}
                            >
                                {deleteDraftMutation.isLoading ? <ButtonLoader /> : t("Delete Draft")}
                            </Button>
                        ) : undefined,
                    }}
                />
            </div>
        );
    }

    const isCommitable =
        !("status" in modifiedRevision) || modifiedRevision.status === "draft" || originalRevision === null;

    const erroredFileNames: string[] = [];
    const tabTriggers: React.ReactNode[] = [];
    diff.forEach((changedItem, i) => {
        const isSelected = `${i}` === selectedFileIndex;
        const error = isCommitable ? editor.editorTabErrors[changedItem.tabID] : null;
        if (error) {
            erroredFileNames.push(changedItem.fileName);
        }
        tabTriggers.push(
            <RadixTabs.Trigger value={`${i}`} key={i} className={classes.changedFileButton}>
                <span className={classes.changedFileButtonContents}>
                    <span className={classes.changeFileName}>{changedItem.fileName}</span>
                    {error ? (
                        <ErrorIndicator error={error} className={classes.fileError} />
                    ) : (
                        <ChangeStatusIndicator changeType={changedItem.changeType} isSelected={isSelected} />
                    )}
                </span>
            </RadixTabs.Trigger>,
        );
    });

    return (
        <RadixTabs.Tabs
            orientation={"vertical"}
            className={classes.root}
            value={selectedFileIndex}
            onValueChange={(i) => {
                setSelectedFileIndex(i);
            }}
        >
            <div ref={mainPanelRef} className={classes.changedFiles}>
                <h3 className={classes.changeListHeading}>{diff.length} Changed Files</h3>
                <RadixTabs.List className={classes.changedFilesList}>{tabTriggers}</RadixTabs.List>
                <FlexSpacer actualSpacer={true} />
                {isCommitable ? (
                    <>
                        {erroredFileNames.length > 0 && (
                            <div className={classes.commitWarningContainer}>
                                <Message
                                    className={classes.commitWarning}
                                    error={{
                                        message: "Some files contain errors.",
                                        description: formatList(erroredFileNames),
                                    }}
                                />
                            </div>
                        )}
                        <FragmentEditorCommitForm className={classes.commitForm} saveMutation={commitMutation} />
                    </>
                ) : (
                    <div className={classes.revertForm}>
                        {t("Revert will apply the contents of this commit as unsaved changes.")}
                        <Button
                            buttonType={"primary"}
                            onClick={() => {
                                editor.updateForm({
                                    css: modifiedRevision.css,
                                    jsRaw: modifiedRevision.jsRaw,
                                    files: modifiedRevision.files,
                                    previewData: modifiedRevision.previewData,
                                    name: modifiedRevision.name,
                                });
                                props.onClose();
                            }}
                        >
                            {t("Revert")}
                        </Button>
                    </div>
                )}
            </div>
            {dragHandle}
            <div className={classes.diffViewer}>
                {diff.map((changedItem, i) => {
                    return (
                        <RadixTabs.Content value={`${i}`} key={i} className={classes.diffContent} forceMount={true}>
                            {changedItem.type === "code" && (
                                <MonacoDiffEditor
                                    hideUntilReady={true}
                                    className={classes.editor}
                                    language={changedItem.language}
                                    theme={editor.editorTheme}
                                    editorOptions={editor.editorOptions}
                                    original={changedItem.oldCode}
                                    modified={changedItem.newCode}
                                />
                            )}
                            {changedItem.type === "previewData" && (
                                <>
                                    {changedItem.newPreviewData?.description !==
                                        changedItem.oldPreviewData?.description && (
                                        <>
                                            <h3 className={classes.diffHeader}>{t("Description")}</h3>
                                            <PlainTextDiff
                                                className={classes.plainTextDiff}
                                                oldText={changedItem.oldPreviewData?.description ?? ""}
                                                newText={changedItem.newPreviewData?.description ?? ""}
                                            />
                                        </>
                                    )}
                                    <h3 className={classes.diffHeader}>{t("Preview Data")}</h3>
                                    <MonacoDiffEditor
                                        hideUntilReady={true}
                                        className={classes.editor}
                                        language={"json"}
                                        jsonSchema={schema}
                                        theme={editor.editorTheme}
                                        editorOptions={editor.editorOptions}
                                        original={
                                            changedItem.oldPreviewData?.data
                                                ? JSON.stringify(changedItem.oldPreviewData.data, null, 4)
                                                : ""
                                        }
                                        modified={
                                            changedItem.newPreviewData?.data
                                                ? JSON.stringify(changedItem.newPreviewData.data, null, 4)
                                                : ""
                                        }
                                    />
                                </>
                            )}
                            {changedItem.type === "uploadedFile" && (
                                <FragmentEditorFileViewer
                                    className={classes.fileViewer}
                                    uploadedFile={changedItem.newUpload ?? changedItem.oldUpload!}
                                />
                            )}
                        </RadixTabs.Content>
                    );
                })}
            </div>
        </RadixTabs.Tabs>
    );
}
