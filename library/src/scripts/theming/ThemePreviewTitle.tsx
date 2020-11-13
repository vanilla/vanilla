/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ITheme } from "@library/theming/themeReducer";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { InformationIcon } from "@library/icons/common";
import themeCardClasses from "@library/theming/themePreviewCardStyles";

interface IProps {
    theme: ITheme;
}

export function ThemePreviewTitle(props: IProps) {
    const { name, supportedSections } = props.theme;
    const classes = themeCardClasses();
    return supportedSections && supportedSections.length > 0 ? (
        <ToolTip
            label={
                <>
                    {t("Edits to this theme apply to:")}{" "}
                    <ul>
                        {supportedSections.map((section) => {
                            return (
                                <li key={section}>
                                    <span style={{ fontWeight: 600 }}>{t(section)}</span>
                                </li>
                            );
                        })}
                    </ul>
                </>
            }
        >
            <h3 className={classes.title} tabIndex={0}>
                {name}
                <InformationIcon className={classes.titleIcon} />
            </h3>
        </ToolTip>
    ) : (
        <h3 className={classes.title}>{name}</h3>
    );
}
