/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Toast from "@library/features/toaster/Toast";
import { getMeta } from "@library/utility/appUtils";
import { useThemeActions, PreviewStatusType } from "@library/theming/ThemeActions";
import { useThemePreviewToasterState } from "@library/features/toaster/themePreview/ThemePreviewToastReducer";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";

interface IThemePreview {
    name: string;
    redirect: string;
    themeID: string | number;
}

export function ThemePreviewToast() {
    const { applyStatus, cancelStatus } = useThemePreviewToasterState();
    const [themePreview, setThemePreview] = useState<IThemePreview | null>(getMeta("themePreview", null));
    const { putCurrentTheme, putPreviewTheme } = useThemeActions();

    const handleApply = async () => {
        if (!themePreview) {
            return;
        }
        putCurrentTheme(themePreview.themeID);
        putPreviewTheme({ themeID: "", type: PreviewStatusType.APPLY });
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
            cancelStatus.status === LoadStatus.SUCCESS
        ) {
            window.location.href = themePreview.redirect;
        }
    });

    if (!themePreview) {
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
                    You are previewing the <b>{themePreview.name}</b> theme.
                    {applyStatus.error && <ErrorMessages errors={[applyStatus.error]} />}
                </>
            }
        />
    );
}
