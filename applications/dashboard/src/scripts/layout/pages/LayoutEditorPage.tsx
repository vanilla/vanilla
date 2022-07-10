/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { useLayoutDraft, useTextEditorJsonBuffer } from "@dashboard/layout/editor/LayoutEditor.hooks";
import { LayoutEditorTitleBar } from "@dashboard/layout/editor/LayoutEditorTitleBar";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutDetails, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import TextEditor from "@library/textEditor/TextEditor";
import { siteUrl, t } from "@library/utility/appUtils";
import React from "react";
import { RouteComponentProps } from "react-router-dom";

export default function LayoutTextEditorPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
        layoutID?: string;
    }> & {
        draftID?: ILayoutDetails["layoutID"];
    },
) {
    const { layoutViewType, layoutID } = props.match.params;
    const { layoutDraft, persistDraft, updateDraft } = useLayoutDraft(layoutID, layoutViewType);
    const {
        textContent,
        setTextContent,
        loadTextDraft,
        validateTextDraft,
        dismissJsonError,
        jsonErrorMessage,
    } = useTextEditorJsonBuffer();
    const catalog = useLayoutCatalog(layoutViewType);

    async function handleSave() {
        if (!layoutDraft) {
            return;
        }
        await persistDraft(layoutDraft);
    }

    function openTextEditor() {
        if (!layoutDraft) {
            return;
        }
        loadTextDraft(layoutDraft);
    }

    function closeTextEditor() {
        const validatedDraft = validateTextDraft(textContent);
        if (!validatedDraft) {
            return;
        }
        updateDraft(validatedDraft);
        setTextContent("");
    }

    return (
        <Modal size={ModalSizes.FULL_SCREEN} isVisible scrollable>
            <LayoutEditorTitleBar
                actions={
                    <Button
                        onClick={(e) => {
                            e.preventDefault();
                            openTextEditor();
                        }}
                        buttonType={ButtonTypes.TEXT}
                    >
                        {t("Advanced")}
                    </Button>
                }
                onSave={handleSave}
                layoutViewType={layoutViewType}
                layoutID={layoutID}
                isSaveDisabled={!!jsonErrorMessage}
            />
            {!layoutDraft || !catalog ? (
                <LayoutOverviewSkeleton />
            ) : (
                <LayoutEditor draft={layoutDraft} onDraftChange={updateDraft} catalog={catalog} />
            )}
            <Modal
                exitHandler={() => {
                    closeTextEditor();
                }}
                isVisible={!!textContent}
                size={ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT_LARGE}
            >
                {jsonErrorMessage && (
                    <Message
                        confirmText={t("Dismiss")}
                        onConfirm={() => {
                            dismissJsonError();
                        }}
                        cancelText={t("Discard Changes")}
                        onCancel={() => {
                            dismissJsonError();
                            setTextContent("");
                        }}
                        title={"Editor contains invalid JSON."}
                        stringContents={jsonErrorMessage}
                    />
                )}
                <TextEditor
                    value={textContent}
                    onChange={(_e, value) => {
                        if (value) {
                            setTextContent(value);
                        }
                    }}
                    language="json"
                    jsonSchemaUri={siteUrl(`/api/v2/layouts/schema?layoutViewType=${layoutViewType}`)}
                />
            </Modal>
        </Modal>
    );
}
