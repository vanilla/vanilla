/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Toast from "@library/features/toaster/Toast";
import { getMeta } from "@library/utility/appUtils";
import { useThemesActions, PreviewStatusType } from "@library/theming/ThemesActions";
import { useThemePreviewToasterState } from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import { LoadStatus } from "@library/@types/api/core";

export function ThemePreviewToast() {
    const { applyStatus, cancelStatus } = useThemePreviewToasterState();
    const [showToaster, setShowToast] = useState(getMeta("themePreview", true));
    const { putCurrentTheme, putPreviewTheme } = useThemesActions();

    const handleApply = async () => {
        putCurrentTheme(showToaster.themeID);
        putPreviewTheme({ themeID: "", type: PreviewStatusType.APPLY });
    };

    const handleCancel = async () => {
        putPreviewTheme({ themeID: "", type: PreviewStatusType.CANCEL });
    };

    useEffect(() => {
        if (
            showToaster.name &&
            (applyStatus.status === LoadStatus.SUCCESS || cancelStatus.status === LoadStatus.SUCCESS)
        ) {
            window.location.href = showToaster.redirect;
        }
    });

    if (!showToaster.name) {
        return null;
    }

    return (
        <Toast
            links={[
                {
                    name: "Apply",
                    type: ButtonTypes.TEXT,
                    onClick: handleApply,
                    isLoading: applyStatus.status === LoadStatus.LOADING || applyStatus.status === LoadStatus.SUCCESS,
                },
                {
                    name: "Cancel",
                    type: ButtonTypes.TEXT_PRIMARY,
                    onClick: handleCancel,
                    isLoading: cancelStatus.status === LoadStatus.LOADING || cancelStatus.status === LoadStatus.SUCCESS,
                },
            ]}
            message={
                <>
                    You are previewing the <b>{showToaster.name}</b> theme.
                </>
            }
        />
    );
}
