/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ClearIcon } from "@library/icons/common";
import { sprintf } from "sprintf-js";

interface IProps {
    onClick: (event: React.SyntheticEvent) => void;
    className?: string;
}

/**
 * Overwrite for the ClearIndicator component in React Select
 */
export function ClearButton({ onClick, className }: IProps) {
    const clearText = sprintf(t("Clear %s"), t("Search"));

    return (
        <Button
            buttonType={ButtonTypes.ICON}
            onClick={onClick}
            className={className}
            title={clearText}
            aria-label={clearText}
        >
            <ClearIcon />
        </Button>
    );
}
