/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LAYOUT_EDITOR_CONFIG_KEY } from "@dashboard/appearance/nav/AppearanceNav.hooks";
import { LayoutOverviewRoute, LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { LayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { useLayoutDraft, useTextEditorJsonBuffer } from "@dashboard/layout/editor/LayoutEditor.hooks";
import { LayoutEditorTitleBar } from "@dashboard/layout/editor/LayoutEditorTitleBar";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { useConfigsByKeys } from "@library/config/configHooks";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import TextEditor from "@library/textEditor/TextEditor";
import { getRelativeUrl, siteUrl, t } from "@library/utility/appUtils";
import React, { useState } from "react";
import { RouteComponentProps } from "react-router-dom";

export default function LayoutTextEditorPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
        layoutID?: string;
    }>,
) {
    const { history } = props;
    const { layoutViewType, layoutID } = props.match.params;
    const { layoutDraft, persistDraft, updateDraft } = useLayoutDraft(layoutID, layoutViewType);
    const toast = useToast();

    const [isSaving, setIsSaving] = useState(false);

    const { textContent, setTextContent, loadTextDraft, validateTextDraft, dismissJsonError, jsonErrorMessage } =
        useTextEditorJsonBuffer();
    const catalog = useLayoutCatalog(layoutViewType);

    const config = useConfigsByKeys([LAYOUT_EDITOR_CONFIG_KEY]);
    const isCustomLayoutsEnabled = !!config?.data?.[LAYOUT_EDITOR_CONFIG_KEY];

    async function handleSave() {
        if (!layoutDraft) {
            return;
        }

        try {
            setIsSaving(true);
            const savedLayout = await persistDraft(layoutDraft);
            if (savedLayout) {
                history.replace(getRelativeUrl(LayoutOverviewRoute.url(savedLayout)));
                toast.addToast({
                    autoDismiss: true,
                    body: <>{t("Layout saved.")}</>,
                });
            }
        } catch (e) {
            toast.addToast({
                autoDismiss: true,
                body: <>{e.description}</>,
            });
        }
    }

    function openTextEditor() {
        if (!layoutDraft) {
            return;
        }
        loadTextDraft(layoutDraft);
    }

    function closeTextEditor() {
        const validatedDraft = validateTextDraft(textContent, layoutViewType);
        if (!validatedDraft) {
            return;
        }
        updateDraft(validatedDraft);
        setTextContent("");
    }

    return (
        <Modal size={ModalSizes.FULL_SCREEN} isVisible scrollable>
            {isCustomLayoutsEnabled && (
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
                    cancelPath={
                        layoutID == null
                            ? LegacyLayoutsRoute.url(layoutViewType)
                            : LayoutOverviewRoute.url({
                                  name: layoutDraft?.name ?? t("My Layout"),
                                  layoutID,
                                  layoutViewType,
                              })
                    }
                    autoFocusTitleInput={layoutID == null}
                    title={layoutDraft?.name ?? t("My Layout")}
                    onTitleChange={(newTitle) => {
                        updateDraft({ name: newTitle });
                    }}
                    disableSave={!!jsonErrorMessage}
                    isSaving={isSaving}
                />
            )}
            {!layoutDraft || !catalog ? (
                <LayoutOverviewSkeleton />
            ) : isCustomLayoutsEnabled ? (
                <LayoutEditor draft={layoutDraft} onDraftChange={updateDraft} catalog={catalog} />
            ) : (
                <h1 style={{ paddingLeft: 24 }}>{t("Page Not Found")}</h1>
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
