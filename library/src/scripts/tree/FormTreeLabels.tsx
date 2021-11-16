/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { formTreeClasses } from "@library/tree/FormTree.classes";
import { makeFormTreeLabelID, useFormTreeContext, useFormTreeLabels } from "@library/tree/FormTreeContext";
import React from "react";

interface IProps {}

export function FormTreeLabels(props: IProps) {
    const classes = formTreeClasses();
    const treeContext = useFormTreeContext();
    const labels = useFormTreeLabels();

    if (Object.keys(labels).length === 0) {
        // No labels to display on this one.
        return <></>;
    }

    return (
        <header className={classes.columnHeader}>
            <div className={classes.rowIconWrapper}></div>
            {Object.entries(labels).map(([propertyName, label]) => (
                <div className={classes.columnLabelWrapper} key={propertyName}>
                    {label ? (
                        <label className={classes.columnLabel} id={makeFormTreeLabelID(treeContext, propertyName)}>
                            {label}
                        </label>
                    ) : (
                        // If we don't have a label we still need a spacer
                        <span
                            className={classes.columnLabel}
                            id={makeFormTreeLabelID(treeContext, propertyName)}
                        ></span>
                    )}
                </div>
            ))}
            <div className={treeContext.isCompact ? classes.actionWrapperCompact : classes.actionWrapper}></div>
        </header>
    );
}
