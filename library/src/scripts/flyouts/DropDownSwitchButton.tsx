import * as React from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { CheckCompactIcon } from "@library/icons/common";
import { locationPickerClasses } from "@knowledge/modules/locationPicker/locationPickerStyles";
import { dropdownSwitchButtonClasses } from "@library/flyouts/dropDownSwitchButtonStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

export interface IButtonSwitch {
    status: boolean;
    isLoading?: boolean;
    onClick: (event: any) => void;
    label: string;
}
/**
 *
 */
export default function DropDownSwitchButton(props: IButtonSwitch) {
    const visibilityClasses = visibility();
    const classes = dropdownSwitchButtonClasses();

    const { isLoading, status, onClick, label } = props;

    const content = (
        <>
            <span className={classes.itemLabel}>{label}</span>
            <span className={classes.checkContainer}>
                {isLoading ? (
                    <ButtonLoader />
                ) : (
                    <CheckCompactIcon
                        aria-hidden={true}
                        className={classNames(
                            { [visibilityClasses.visuallyHidden]: !status },
                            "selectBox-isSelectedIcon",
                        )}
                    />
                )}
            </span>
        </>
    );

    return (
        <DropDownItemButton onClick={onClick} role={"switch"} aria-checked={status} disabled={props.isLoading}>
            {content}
        </DropDownItemButton>
    );
}
