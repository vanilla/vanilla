/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    CustomPagesRoute,
    LayoutOverviewRoute,
    LegacyLayoutsRoute,
} from "@dashboard/appearance/routes/appearanceRoutes";
import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import { EditorRolePreviewDropDownItem, EditorRolePreviewProvider } from "@dashboard/roles/EditorRolePreviewContext";
import {
    EditorThemePreviewDropDownItem,
    EditorThemePreviewOverrides,
    EditorThemePreviewProvider,
} from "@library/theming/EditorThemePreviewContext";
import { RouteComponentProps, useLocation } from "react-router-dom";
import { getRelativeUrl, siteUrl, t } from "@library/utility/appUtils";
import { useLayoutDraft, useTextEditorJsonBuffer } from "@dashboard/layout/editor/LayoutEditor.hooks";

import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { LayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { LayoutEditorTitleBar } from "@dashboard/layout/editor/LayoutEditorTitleBar";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import MonacoEditor from "@library/textEditor/MonacoEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { useState } from "react";
import { useToast } from "@library/features/toaster/ToastContext";

export default function LayoutTextEditorPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
        layoutID?: string;
    }>,
) {
    const isCopy = new URLSearchParams(useLocation().search).get("copy") === "true";
    const { history } = props;
    const { layoutViewType, layoutID } = props.match.params;
    const isCustomPage = layoutViewType === "customPage";
    const { layoutDraft, persistDraft, updateDraft } = useLayoutDraft(layoutID, layoutViewType, isCopy);
    const toast = useToast();
    const [isSaving, setIsSaving] = useState(false);

    const { textContent, setTextContent, loadTextDraft, validateTextDraft, dismissJsonError, jsonErrorMessage } =
        useTextEditorJsonBuffer();
    const catalog = useLayoutCatalog(layoutViewType);

    async function handleSave() {
        if (!layoutDraft) {
            return;
        }

        try {
            setIsSaving(true);
            const savedLayout = await persistDraft(layoutDraft);
            if (savedLayout) {
                const redirectUrl = isCustomPage ? CustomPagesRoute.url(null) : LayoutOverviewRoute.url(savedLayout);
                history.replace(getRelativeUrl(redirectUrl));
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

    const classes = layoutEditorClasses();

    return (
        <Modal size={ModalSizes.FULL_SCREEN} isVisible className={classes.modal}>
            <EditorThemePreviewProvider>
                <EditorRolePreviewProvider>
                    <LayoutEditorTitleBar
                        actions={
                            <>
                                <DropDown flyoutType={FlyoutType.LIST}>
                                    <EditorThemePreviewDropDownItem />
                                    <EditorRolePreviewDropDownItem />
                                    <DropDownItemButton
                                        onClick={() => {
                                            openTextEditor();
                                        }}
                                    >
                                        {t("Advanced")}
                                    </DropDownItemButton>
                                </DropDown>
                            </>
                        }
                        onSave={handleSave}
                        cancelPath={
                            isCustomPage
                                ? CustomPagesRoute.url(null)
                                : layoutID == null
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

                    <EditorThemePreviewOverrides fallback={<LayoutOverviewSkeleton />}>
                        {!layoutDraft || !catalog ? (
                            <LayoutOverviewSkeleton />
                        ) : (
                            <LayoutEditor draft={layoutDraft} onDraftChange={updateDraft} catalog={catalog} />
                        )}
                    </EditorThemePreviewOverrides>
                </EditorRolePreviewProvider>
            </EditorThemePreviewProvider>

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
                <MonacoEditor
                    value={textContent}
                    onChange={(value) => {
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
