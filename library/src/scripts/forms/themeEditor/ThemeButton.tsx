/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { themeButtonClasses } from "@library/forms/themeEditor/ThemeButton.styles";
import React from "react";

interface IProps {
    disabled?: boolean;
    label: string;
    onClick(): void;
}

export const ThemeButton = React.forwardRef(function ThemeButton(_props: IProps, ref: React.Ref<HTMLButtonElement>) {
    const { disabled, label, onClick } = _props;

    return (
        <>
            <div className={themeButtonClasses().root}>
                <button disabled={disabled} onClick={onClick} ref={ref}>
                    {label}
                </button>
            </div>
        </>
    );
});
