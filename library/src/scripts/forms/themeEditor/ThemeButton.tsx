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

export function ThemeButton(_props: IProps) {
    const { disabled, label, onClick } = _props;

    return (
        <>
            <div className={themeButtonClasses().root}>
                <button disabled={disabled} onClick={onClick}>
                    {label}
                </button>
            </div>
        </>
    );
}
