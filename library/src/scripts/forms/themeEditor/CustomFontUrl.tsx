/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { isAllowedUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n/src";
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";

interface IProps {
    forceError?: boolean; // Force error for storybook.
}

function urlValidation(url: any) {
    return url ? isAllowedUrl(url.toString()) : false;
}

export function CustomFontUrl(props: IProps) {
    return (
        <ThemeInputText
            varKey={"global.fonts.customFont.url"}
            debounceTime={10}
            forceError={props.forceError}
            validation={(newValue) => {
                if (props.forceError) {
                    return false;
                } else {
                    return !newValue || urlValidation(newValue);
                }
            }}
            errorMessage={t("Invalid URL")}
        />
    );
}
