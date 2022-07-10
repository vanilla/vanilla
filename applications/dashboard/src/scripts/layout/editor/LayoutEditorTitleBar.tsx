/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutOverviewRoute, LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import AdminEditTitleBar from "@dashboard/components/AdminEditTitleBar";
import { useLayoutDraft } from "@dashboard/layout/editor/LayoutEditor.hooks";
import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LoadStatus } from "@library/@types/api/core";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@vanilla/i18n";
import { useLastValue } from "@vanilla/react-utils";
import { RecordID } from "@vanilla/utils";
import React, { useEffect, useState } from "react";

interface IProps {
    layoutID?: RecordID;
    layoutViewType: LayoutViewType;
    onSave?: () => void;
    isSaveDisabled?: boolean;
    actions?: React.ReactNode;
}

export function LayoutEditorTitleBar(props: IProps) {
    const { layoutID, layoutViewType, onSave } = props;
    const { layoutDraft, persistLoadable, updateDraft } = useLayoutDraft(layoutID, layoutViewType);
    const [wasJustSaved, setWasJustSaved] = useState(false);
    const isSaving = persistLoadable.status === LoadStatus.LOADING;
    const isSaved = persistLoadable.status === LoadStatus.SUCCESS;
    const wasSaved = useLastValue(isSaved);

    useEffect(() => {
        // Clear the just saved status on edit.
        if (wasJustSaved) {
            setWasJustSaved(false);
        }
    }, [wasJustSaved, layoutDraft]);

    useEffect(() => {
        if (!wasSaved && isSaved) {
            // Show that we were just saved.
            setWasJustSaved(true);
        }
    }, [isSaved, wasSaved]);

    return (
        <AdminEditTitleBar
            title={layoutDraft?.name ?? t("My Layout")}
            cancelPath={
                layoutID == null
                    ? LegacyLayoutsRoute.url(layoutViewType)
                    : LayoutOverviewRoute.url({
                          name: layoutDraft?.name ?? t("My Layout"),
                          layoutID,
                          layoutViewType,
                      })
            }
            onTitleChange={(newTitle) => {
                updateDraft({ name: newTitle });
            }}
            autoFocusTitleInput={layoutID == null}
            // We're making our own save button.
            noSaveButton
            actions={
                <>
                    {props.actions}
                    <Button
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                        onClick={onSave}
                        disabled={isSaving || props.isSaveDisabled}
                    >
                        {isSaving ? <ButtonLoader /> : wasJustSaved ? t("Saved") : t("Save")}
                    </Button>
                </>
            }
        />
    );
}
