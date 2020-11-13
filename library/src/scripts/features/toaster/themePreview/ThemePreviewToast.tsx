/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Toast from "@library/features/toaster/Toast";
import { getMeta } from "@library/utility/appUtils";
import { PreviewStatusType, useThemeActions } from "@library/theming/ThemeActions";
import { useThemePreviewToasterState } from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { t } from "@vanilla/i18n/src";
import { RecordID } from "@vanilla/utils";

interface IThemePreview {
    name: string;
    redirect: string;
    themeID: RecordID;
    revisionID?: number;
}

export function ThemePreviewToast() {
    const { applyStatus, cancelStatus } = useThemePreviewToasterState();
    const [themePreview, setThemePreview] = useState<IThemePreview | null>(getMeta("themePreview", null));
    const { putCurrentTheme, putPreviewTheme, patchThemeWithRevisionID } = useThemeActions();

    const [restoringRevision, setRestoringRevision] = useState(false);
    const [revisionRestored, setRevisionRestored] = useState(false);

    const isRevisionPreview = themePreview?.revisionID ?? false;
    const handleApply = async () => {
        if (!themePreview) {
            return;
        }
        if (isRevisionPreview) {
            setRestoringRevision(true);
            const updatedTheme = await patchThemeWithRevisionID({
                themeID: themePreview.themeID,
                revisionID: themePreview.revisionID,
            });
            if (updatedTheme) {
                setRestoringRevision(false);
                setRevisionRestored(true);
            }
            putPreviewTheme({ themeID: "", revisionID: undefined, type: PreviewStatusType.APPLY });
        } else {
            putCurrentTheme(themePreview.themeID);
            putPreviewTheme({ themeID: "", type: PreviewStatusType.APPLY });
        }
    };

    const handleCancel = async () => {
        putPreviewTheme({ themeID: "", type: PreviewStatusType.CANCEL });
    };

    useEffect(() => {
        if (!themePreview) {
            return;
        }
        if (
            (themePreview.name && applyStatus.status === LoadStatus.SUCCESS) ||
            cancelStatus.status === LoadStatus.SUCCESS ||
            revisionRestored
        ) {
            window.location.href = isRevisionPreview
                ? `/theme/theme-settings/${themePreview.themeID}/revisions`
                : themePreview.redirect;
        }
    });

    if (!themePreview) {
        return null;
    }

    return (
        <Toast
            links={[
                {
                    name: isRevisionPreview ? t("Restore") : t("Apply"),
                    type: ButtonTypes.TEXT,
                    onClick: handleApply,
                    isLoading:
                        applyStatus.status === LoadStatus.LOADING ||
                        applyStatus.status === LoadStatus.SUCCESS ||
                        restoringRevision,
                },
                {
                    name: t("Cancel"),
                    type: ButtonTypes.TEXT_PRIMARY,
                    onClick: handleCancel,
                    isLoading: cancelStatus.status === LoadStatus.LOADING || cancelStatus.status === LoadStatus.SUCCESS,
                },
            ]}
            message={
                <>
                    You are previewing the <b>{themePreview.name}</b> theme.
                    {applyStatus.error && <ErrorMessages errors={[applyStatus.error]} />}
                </>
            }
        />
    );
}
