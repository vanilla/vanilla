/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";
import { categoryPickerClasses } from "./CategoryPicker.classes";

interface IProps {
    label?: string;
    description?: string;
}

/**
 * Renders category name and description
 */
export function CategoryInfo(props: IProps) {
    const { label, description } = props;
    const classes = categoryPickerClasses();

    return (
        <>
            {label && description && (
                <div className={classes.categoryInfo}>
                    <div className={classes.categoryLabel}>{label}</div>
                    <div className={classes.categoryDescription}>{description}</div>
                </div>
            )}
        </>
    );
}

export default CategoryInfo;
