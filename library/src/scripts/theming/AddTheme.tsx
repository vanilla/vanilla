/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import classNames from "classnames";
import React from "react";
import addThemeClasses from "./addThemeStyles";
import { t } from "@vanilla/i18n";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps {
    onAdd?: React.ReactNode;
    className?: string;
}

export function AddTheme(props: IProps) {
    const classes = addThemeClasses();
    return (
        <Button
            buttonType={ButtonTypes.ICON}
            className={classNames(classes.button, props.className)}
            title={t("Add Theme")}
        >
            <div className={classes.addTheme}>{props.onAdd}</div>
        </Button>
    );
}
