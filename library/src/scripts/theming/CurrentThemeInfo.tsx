/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { currentThemeClasses } from "./currentThemeStyles";
import DateTime from "@library/content/DateTime";

export interface IThemeInfo {
    [key: string]: {
        type: string;
        info: string;
    };
}

interface IProps {
    name: string;
    info: IThemeInfo;
    support?: string;
    editButton?: React.ReactNode;
    copyButton?: React.ReactNode;
}

export default function CurrentThemeInfo(props: IProps) {
    const classes = currentThemeClasses();
    const { name, info, copyButton, editButton } = props;
    return (
        <React.Fragment>
            <section className={classes.themeContainer}>
                <div className={classes.themeInfo}>
                    <div className={classes.flag}>Current Theme</div>
                    <div className={classes.name}>
                        <h3>{name}</h3>
                    </div>
                    {Object.entries(info).map(([key, value], i) => (
                        <div key={i} className={classes.description}>
                            <p>
                                <strong>{key === "Description" ? "" : `${key}:`}</strong>{" "}
                                {value.type === "date" ? <DateTime timestamp={value.info} /> : value.info}
                            </p>
                        </div>
                    ))}
                </div>
                <div className={classes.themeActionButtons}>
                    {editButton}
                    {copyButton}
                </div>
            </section>
        </React.Fragment>
    );
}
