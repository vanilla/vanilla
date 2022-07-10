/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { PropsWithChildren } from "react";
import { currentThemeClasses } from "./currentThemeStyles";
import { ThemePreviewTitle } from "@library/theming/ThemePreviewTitle";
import { ITheme } from "@library/theming/themeReducer";
import { t } from "@vanilla/i18n";
import DateTime from "@library/content/DateTime";
import { cx } from "@emotion/css";

export function Flag(props: PropsWithChildren<{ className?: string }>) {
    const classes = currentThemeClasses();
    return <div className={cx(classes.flag, props.className)}>{props.children ?? t("Current")}</div>;
}
export interface IThemeInfo {
    [key: string]: {
        type: string;
        info: string;
    };
}

interface IProps {
    theme: ITheme;
    editButton?: React.ReactNode;
    copyButton?: React.ReactNode;
    revisionHistoryButton?: React.ReactNode;
}

export default function CurrentThemeInfo(props: IProps) {
    const classes = currentThemeClasses();
    const { theme, copyButton, editButton, revisionHistoryButton } = props;
    const { info } = theme.preview;
    return (
        <React.Fragment>
            <section className={classes.themeContainer}>
                <div className={classes.themeInfo}>
                    <Flag>{t("Current Theme")}</Flag>
                    <div className={classes.name}>
                        <ThemePreviewTitle theme={props.theme} />
                    </div>
                    {info &&
                        Object.entries(info).map(([key, value], i) => (
                            <div key={i} className={classes.description}>
                                <p>
                                    <strong>{key === "Description" ? "" : `${key}:`}</strong>{" "}
                                    {value.type === "date" ? <DateTime timestamp={value.value} /> : value.value}
                                </p>
                            </div>
                        ))}
                </div>
                <div className={classes.themeActionButtons}>
                    {editButton}
                    {copyButton}
                    {revisionHistoryButton}
                </div>
            </section>
        </React.Fragment>
    );
}
