/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import { close } from "@library/components/Icons";
import { ButtonBaseClass } from "../../Button";
import classNames from "classnames";
import { t } from "@library/application";

/**
 * Overwrite for the multiValueRemove component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props - props of component
 */
export default function multiValueRemove(props) {
    const { innerProps, isDisabled, selectProps } = props;

    // We need to bind the function to the props for that component
    const handleKeyDown = event => {
        switch (event.key) {
            case "Enter":
            case "Spacebar":
            case " ":
                innerProps.onClick(event);
                break;
        }
    };

    return (
        <components.MultiValueRemove {...props} className="suggestedTextInput-tokenRemove">
            <button
                {...innerProps}
                className={classNames(
                    ButtonBaseClass.CUSTOM,
                    `${selectProps.classNamePrefix}-clear`,
                    "suggestedTextInput-clear",
                )}
                type="button"
                style={{}}
                aria-hidden={null} // Unset the prop in restInnerProps
                onKeyDown={handleKeyDown}
                onClick={innerProps.onClick}
                onTouchEnd={innerProps.onTouchEnd}
                onMouseDown={innerProps.onMouseDown}
                disabled={isDisabled}
                title={t("Clear")}
                aria-label={t("Clear")}
            >
                {close("suggestedTextInput-tokenRemoveIcon", true)}
            </button>
        </components.MultiValueRemove>
    );
}
