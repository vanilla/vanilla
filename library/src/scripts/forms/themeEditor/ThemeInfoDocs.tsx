/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { DocumentationIcon } from "@library/icons/common";
import SmartLink from "@library/routing/links/SmartLink";
import React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@vanilla/i18n";

interface IProps {
    href: string;
}

export function ThemeInfoDocs(props: IProps) {
    const classes = themeBuilderClasses();

    return (
        <span className={classes.blockInfo}>
            <SmartLink title={t("Documentation")} className={classes.documentationIconLink} to={props.href}>
                <ScreenReaderContent>{t("Documentation")}</ScreenReaderContent>
                <DocumentationIcon />
            </SmartLink>
        </span>
    );
}
