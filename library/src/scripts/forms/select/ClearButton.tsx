/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import classNames from "classnames";
import { ClearIcon } from "@library/icons/common";

interface IProps {
    onClick: (event: React.SyntheticEvent) => void;
    className?: string;
}

/**
 * Overwrite for the ClearIndicator component in React Select
 */
export function ClearButton(props: IProps) {
    return (
        <Button
            baseClass={ButtonTypes.ICON}
            className={classNames("suggestedTextInput-clear", "searchBar-clear", props.className)}
            onClick={props.onClick}
            title={t("Clear")}
            aria-label={t("Clear")}
        >
            <ClearIcon />
        </Button>
    );
}
