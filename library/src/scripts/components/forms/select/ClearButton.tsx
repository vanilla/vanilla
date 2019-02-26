/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";
import { clear } from "@library/components/icons/common";

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
            baseClass={ButtonBaseClass.ICON}
            className={classNames("suggestedTextInput-clear", "searchBar-clear", props.className)}
            type="button"
            onClick={props.onClick}
            title={t("Clear")}
            aria-label={t("Clear")}
        >
            {clear()}
        </Button>
    );
}
