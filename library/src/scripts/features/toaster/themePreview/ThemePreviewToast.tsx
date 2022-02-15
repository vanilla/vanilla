/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo, useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Toast } from "@library/features/toaster/Toast";
import { getMeta } from "@library/utility/appUtils";
import { PreviewStatusType, useThemeActions } from "@library/theming/ThemeActions";
import { useThemePreviewToasterState } from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { t } from "@vanilla/i18n/src";
import { RecordID } from "@vanilla/utils";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { themePreviewToastClasses } from "@library/features/toaster/themePreview/ThemePreviewToast.style";
import { useToast } from "@library/features/toaster/ToastContext";

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

    const classes = themePreviewToastClasses();

    const { addToast, updateToast } = useToast();

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

    const isCancelLoading = useMemo(() => {
        return cancelStatus.status === LoadStatus.LOADING || cancelStatus.status === LoadStatus.SUCCESS;
    }, [cancelStatus]);

    const isApplyLoading = useMemo(() => {
        return (
            applyStatus.status === LoadStatus.LOADING || applyStatus.status === LoadStatus.SUCCESS || restoringRevision
        );
    }, [applyStatus, restoringRevision]);

    const applyButtonLabel = useMemo(() => {
        return isRevisionPreview ? t("Restore") : t("Apply");
    }, [isRevisionPreview]);

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
                ? `/appearance/style-guides/${themePreview.themeID}/revisions`
                : themePreview.redirect;
        }
    });

    // Keep track of any toasts this component creates
    const [toastID, setToastID] = useState<string>();

    // Create a body of the toast
    const toastBody = useMemo(() => {
        if (themePreview) {
            return (
                <>
                    You are previewing the <b>{themePreview && themePreview.name}</b> theme.
                    {applyStatus.error && <ErrorMessages errors={[applyStatus.error]} />}
                    <div className={classes.toastActions}>
                        <Button onClick={handleApply} buttonType={ButtonTypes.TEXT}>
                            {isApplyLoading ? <ButtonLoader buttonType={ButtonTypes.TEXT} /> : <>{applyButtonLabel}</>}
                        </Button>
                        <Button onClick={handleCancel} buttonType={ButtonTypes.TEXT_PRIMARY}>
                            {isCancelLoading ? <ButtonLoader buttonType={ButtonTypes.TEXT_PRIMARY} /> : t("Cancel")}
                        </Button>
                    </div>
                </>
            );
        } else {
            return null;
        }
    }, [applyButtonLabel, applyStatus.error, isApplyLoading, isCancelLoading, themePreview]);

    useEffect(() => {
        // If there is no created toast, add one
        if (toastBody && !toastID) {
            const newID = addToast({
                persistent: true,
                body: toastBody,
            });
            setToastID(newID);
        }
        // Otherwise update the toast based using the ID
        if (toastBody && toastID) {
            updateToast(toastID, { body: toastBody });
        }
    }, [toastBody]);

    return null;
}
