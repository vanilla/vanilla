/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILayout, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { initCodeHighlighting } from "@library/content/code";
import { getRelativeUrl, t, siteUrl } from "@library/utility/appUtils";
import React, { useEffect, useState } from "react";
import { RouteComponentProps } from "react-router-dom";
import AdminEditTitleBar from "@dashboard/components/AdminEditTitleBar";
import { LayoutOverviewRoute, LegacyLayoutsRoute } from "@dashboard/appearance/routes/pageRoutes";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useLayoutJsonDraft, useLayoutJsonDraftActions } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { PageLoadStatus } from "@library/loaders/PageLoadStatus";
import { LayoutEditorOverview } from "@dashboard/appearance/components/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/appearance/components/LayoutEditor.classes";
import classNames from "classnames";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { css } from "@emotion/css";
import TextEditor from "@library/textEditor/TextEditor";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Frame from "@library/layout/frame/Frame";
import { LayoutEditorContextProvider } from "@dashboard/appearance/components/LayoutEditorContextProvider";

export default function EditLayoutPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
        layoutID?: string;
    }> & {
        draftID?: ILayout["layoutID"];
    },
) {
    const classes = layoutEditorClasses();
    const { layoutViewType = "home", layoutID } = props.match.params;
    const { history } = props;
    const isNewDraft = !!props.draftID;
    const draftID = (props.draftID ?? layoutID)!;
    const draftLoadable = useLayoutJsonDraft(props.draftID, props.match.params.layoutID, layoutViewType);
    const draft = draftLoadable.data;

    const { save: saveDraft, update: updateDraft } = useLayoutJsonDraftActions(draftID);

    const device = useTitleBarDevice();
    const isCompact = device === TitleBarDevices.COMPACT;

    useEffect(() => {
        initCodeHighlighting();
    }, [draft]);

    const [isSaving, setIsSaving] = useState(false);
    async function handleSave() {
        setIsSaving(true);
        const layout = await saveDraft();
        setIsSaving(false);
        history.push(getRelativeUrl(LayoutOverviewRoute.url({ ...layout, layoutViewType })));
    }

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editorContent, setEditorContent] = useState("");

    function openTextEditor() {
        setEditorContent(JSON.stringify(draft ?? {}, null, 4));
        setIsModalOpen(true);
    }

    function saveTextEditor(val: string) {
        updateDraft({
            ...JSON.parse(val),
            layoutID: draftLoadable.data!.layoutID,
            name: draftLoadable.data!.name,
            layoutViewType,
        });
    }

    function handleClose() {
        // TODO error handling here.
        setIsModalOpen(false);
        saveTextEditor(editorContent);
    }

    return draft ? (
        <PageLoadStatus loadable={draftLoadable}>
            <Modal size={ModalSizes.FULL_SCREEN} isVisible>
                <AdminEditTitleBar
                    title={draft?.name || t("Untitled")}
                    cancelPath={
                        isNewDraft
                            ? LegacyLayoutsRoute.url(layoutViewType)
                            : LayoutOverviewRoute.url({
                                  name: draft.name,
                                  layoutID: draft.layoutID,
                                  layoutViewType,
                              } as ILayout)
                    }
                    disableSave={isSaving}
                    onSave={handleSave}
                    onTitleChange={(newTitle) => {
                        updateDraft({ ...draft, name: newTitle });
                    }}
                    autoFocusTitleInput={!!isNewDraft}
                    isCompact={isCompact}
                />
                <div className={classNames(classes.root)}>
                    <div className={classNames(classes.screen)}>
                        <LayoutEditorContextProvider
                            layoutID={draftID}
                            isEditMode={true}
                            addWidgetHandler={() => {
                                openTextEditor();
                            }}
                        >
                            <LayoutEditorOverview layoutLoadable={draftLoadable} />
                            <Modal
                                size={ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT_LARGE}
                                exitHandler={handleClose}
                                isVisible={isModalOpen}
                            >
                                <Frame
                                    canGrow
                                    className={css({
                                        maxHeight: "initial",
                                    })}
                                    header={<FrameHeader title={t("JSON Editor")} closeFrame={handleClose} />}
                                    body={
                                        <TextEditor
                                            value={editorContent}
                                            onChange={(_e, value) => {
                                                if (value) {
                                                    setEditorContent(value);
                                                }
                                            }}
                                            language="json"
                                            jsonSchemaUri={siteUrl(
                                                `/api/v2/layouts/schema?layoutViewType=${layoutViewType}`,
                                            )}
                                        />
                                    }
                                />
                            </Modal>
                        </LayoutEditorContextProvider>
                    </div>
                </div>
            </Modal>
        </PageLoadStatus>
    ) : (
        <></>
    );
}
