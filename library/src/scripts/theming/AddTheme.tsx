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
import { ButtonTypes } from "@library/forms/buttonStyles";
import { themeItemClasses } from "@themingapi/theming-ui-settings/themeItemStyles";
import themeCardClasses from "./themePreviewCardStyles";

interface IProps {
    onAdd?: React.ReactNode;
    className?: string;
}

export function AddTheme(props: IProps) {
    const classes = addThemeClasses();
    const classesThemeCard = themeCardClasses();
    const classesthemeItem = themeItemClasses();
    return (
        <div className={classNames(classesthemeItem.item, props.className)}>
            <div className={(classesThemeCard.constraintContainer, classes.noBoxshadow)}>
                <div className={classesThemeCard.ratioContainer}>
                    <div className={classesThemeCard.container}>
                        <Button
                            baseClass={ButtonTypes.ICON}
                            className={classNames(classes.button)}
                            title={t("Add Theme")}
                        >
                            <div className={classes.addTheme}>{props.onAdd}</div>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
