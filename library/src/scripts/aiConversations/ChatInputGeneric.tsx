/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import InputTextBlock from "@library/forms/InputTextBlock";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { Icon } from "@vanilla/icons";
import React from "react";
import { t } from "@vanilla/i18n";
import { useThemeCache } from "@library/styles/styleUtils";

const chatInputClasses = useThemeCache(() => {
    const layout = css({
        display: "grid",
        gap: 4,
        alignItems: "center",
        margin: "8px 0px",
        position: "relative",
        gridTemplateColumns: "auto 40px",
        marginRight: "var(--horizontal-padding, 16px)",
        // This acts as the input box with the submit button within
        borderRadius: 6,
        border: singleBorder(),
        background: "white",

        "&:not(:disabled)": {
            "&:active, &:hover, &:focus, &.focus-visible, &:focus-within": {
                borderColor: ColorsUtils.colorOut(globalVariables().mainColors.primary),
            },
        },
    });
    const inputWrapper = css({
        "& > span:first-of-type": {
            margin: 0,
        },
    });
    const input = css({
        // Override the input box border and colors in favour of the enclosing parent box
        borderColor: "transparent",
        "&:not(:disabled)": {
            "&:active, &:hover, &:focus, &.focus-visible, &:focus-within": {
                borderColor: "transparent",
            },
        },
    });
    const submit = css({
        aspectRatio: "1/1",
    });
    return {
        layout,
        inputWrapper,
        input,
        submit,
    };
});

interface IProps {
    onSubmit: (value: string) => void;

    isLoading: boolean;

    placeholder?: string;
    inputRef?: React.RefObject<HTMLInputElement | HTMLTextAreaElement>;
}

export function ChatInput(props: IProps) {
    const { onSubmit, isLoading } = props;

    const classes = chatInputClasses();

    const [value, setValue] = React.useState("");

    function handleSubmit(value) {
        onSubmit(value);
        setValue("");
    }

    return (
        <div className={classes.layout}>
            <InputTextBlock
                className={classes.inputWrapper}
                inputProps={{
                    multiline: true,
                    disabled: isLoading,
                    value: value,
                    inputRef: props.inputRef,
                    placeholder: props.placeholder ?? t("Ask the AI"),
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => setValue(event.target.value),
                    onKeyPress: (event: React.KeyboardEvent<HTMLInputElement>) => {
                        if (event.key === "Enter" && !event.shiftKey) {
                            event.preventDefault();
                            event.stopPropagation();
                            event.nativeEvent.stopImmediatePropagation();
                            if (!isLoading) {
                                handleSubmit(value);
                            }
                        }
                    },
                }}
                multiLineProps={{
                    overflow: "scroll",
                    rows: 1,
                    maxRows: 100,
                    className: classes.input,
                }}
            />
            <Button
                buttonType={ButtonTypes.ICON}
                className={classes.submit}
                onClick={() => handleSubmit(value)}
                disabled={isLoading}
                name={t("Send")}
            >
                {isLoading ? <ButtonLoader /> : <Icon icon={"send"} />}
            </Button>
        </div>
    );
}
