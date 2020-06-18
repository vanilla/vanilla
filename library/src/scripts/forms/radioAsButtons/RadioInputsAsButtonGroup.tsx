/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
// import { radioInputAsButtonClasses } from "@library/forms/radioAsButtons/radioInputAsButtonStyles";
import { IRadioGroupProps, RadioGroupProvider } from "@library/forms/radioAsButtons/RadioGroupContext";
import classNames from "classnames";
import { radioTabClasses } from "@library/forms/radioTabs/radioTabStyles";

interface IProps extends IRadioGroupProps {
    className?: string;
    accessibleTitle: string;
    children: JSX.Element;
    setData: (data: any) => void;
}

/**
 * Implement what looks like buttons, but what is semantically radio buttons.
 */
export function RadioInputsAsButtonGroup(props: IProps) {
    const { className, accessibleTitle, children, setData, activeItem } = props;
    const classes = radioTabClasses();
    const classesInputBlock = inputBlockClasses();

    return (
        <RadioGroupProvider setData={setData} activeItem={activeItem}>
            <fieldset className={classNames(classesInputBlock.root, classes.root, className)}>
                <ScreenReaderContent tag="legend">{accessibleTitle}</ScreenReaderContent>
                <div className={classes.tabs}>{children}</div>
            </fieldset>
        </RadioGroupProvider>
    );
}
