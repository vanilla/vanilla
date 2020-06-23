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
import { buttonClasses } from "@library/forms/buttonStyles";
import { radioInputAsButtonsClasses } from "@library/forms/radioAsButtons/radioInputAsButtons.styles";

interface IProps extends IRadioGroupProps {
    className?: string;
    accessibleTitle: string;
    children: React.ReactNode;
    setData: (data: any) => void;
    buttonActiveClass?: string; // class that goes directly on the actual "button" element
    buttonClass?: string; // class that goes directly on the actual "button" element
}

/**
 * Implement what looks like buttons, but what is semantically radio buttons.
 */
export function RadioGroup(props: IProps) {
    const classesButtons = buttonClasses();
    const {
        className,
        accessibleTitle,
        children,
        setData,
        activeItem,
        classes = radioInputAsButtonsClasses(),
        buttonActiveClass = classesButtons.primary,
        buttonClass = classesButtons.standard,
    } = props;
    const classesInputBlock = inputBlockClasses();
    const rootClass = classes["root"] ?? "";

    return (
        <RadioGroupProvider
            setData={setData}
            activeItem={activeItem}
            buttonActiveClass={buttonActiveClass}
            buttonClass={buttonClass}
            classes={classes}
        >
            <fieldset className={classNames(classesInputBlock.root, rootClass, className)}>
                <ScreenReaderContent tag="legend">{accessibleTitle}</ScreenReaderContent>
                <div className={classes.items}>{children}</div>
            </fieldset>
        </RadioGroupProvider>
    );
}
