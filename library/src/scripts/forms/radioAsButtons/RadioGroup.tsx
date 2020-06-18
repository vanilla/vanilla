/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { IRadioGroupProps, RadioGroupProvider } from "@library/forms/radioAsButtons/RadioGroupContext";
import classNames from "classnames";
import { radioInputAsButtonClasses } from "@library/forms/radioAsButtons/radioInputAsButtons.styles";

interface IProps extends IRadioGroupProps {
    className?: string;
    accessibleTitle: string;
    children: JSX.Element | JSX.Element[];
    setData: (data: any) => void;
    classes?: any; // Overwritable base classes for component
    buttonActiveClass?: string; // class that goes directly on the actual "button" element
    buttonClass?: string; // class that goes directly on the actual "button" element
}

/**
 * Implement what looks like buttons, but what is semantically radio buttons.
 */
export function RadioGroup(props: IProps) {
    const { className, accessibleTitle, children, setData, activeItem, classes = radioInputAsButtonClasses() } = props;
    const classesInputBlock = inputBlockClasses();

    return (
        <RadioGroupProvider setData={setData} activeItem={activeItem}>
            <fieldset className={classNames(classesInputBlock.root, classes.root, className)}>
                <ScreenReaderContent tag="legend">{accessibleTitle}</ScreenReaderContent>
                <div className={classes.items}>{children}</div>
            </fieldset>
        </RadioGroupProvider>
    );
}
