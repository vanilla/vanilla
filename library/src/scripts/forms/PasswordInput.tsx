/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode, useState } from "react";
import { inputClasses, inputVariables } from "@library/forms/inputStyles";
import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { css, cx } from "@emotion/css";
import { getRequiredID } from "@library/utility/idUtils";
import { ToolTip } from "@library/toolTip/ToolTip";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { globalVariables } from "@library/styles/globalStyleVars";

interface IProps extends React.InputHTMLAttributes<HTMLInputElement> {
    showUnmask?: boolean;
    type?: never; // The type will be controlled by the component
    autoComplete?: never; // Turn off autofill suggestions for the field
    className?: string;
    inputRef?: React.RefObject<HTMLInputElement>;
    hasError?: boolean;
    errorTooltip?: ReactNode;
}

const heightFix = css({
    height: inputVariables().sizing.height,
});

export function PasswordInput(props: IProps) {
    const { showUnmask, inputRef, className, hasError, errorTooltip, ...rest } = props;
    const classes = inputClasses();
    const [showText, setShowText] = useState<boolean>(false);

    const buttonLabel = showText ? "Hide Password" : "Show Password";
    const inputID = getRequiredID(props, "passwordField");

    const toggleShowText = () => {
        setShowText(!showText);
        document.getElementById(inputID)?.focus();
    };

    return (
        <div className={classes.inputWrapper}>
            <div className={cx(classes.inputContainer, heightFix, className)}>
                <input {...rest} id={inputID} type={showText ? "text" : "password"} autoComplete="off" ref={inputRef} />
                {hasError && (
                    <ConditionalWrap
                        condition={!!errorTooltip}
                        component={ToolTip}
                        componentProps={{ label: errorTooltip }}
                    >
                        {/* This span is required for the conditional tooltip */}
                        <span style={{ display: "flex" }}>
                            <Icon icon="status-alert" className={classes.errorIcon} />
                        </span>
                    </ConditionalWrap>
                )}
            </div>
            {showUnmask && (
                <Button
                    buttonType={ButtonTypes.ICON}
                    tabIndex={-1}
                    onClick={toggleShowText}
                    ariaLabel={t(buttonLabel)}
                    title={t(buttonLabel)}
                    className={classes.hugRight}
                >
                    <Icon icon={showText ? "hide-content" : "show-content"} />
                </Button>
            )}
        </div>
    );
}

export default PasswordInput;
