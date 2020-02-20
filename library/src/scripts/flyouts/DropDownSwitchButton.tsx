import * as React from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { CheckCompactIcon } from "@library/icons/common";
import { dropdownSwitchButtonClasses } from "@library/flyouts/dropDownSwitchButtonStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { t } from "@vanilla/i18n/src";

export interface IButtonSwitch {
    status: boolean;
    isLoading?: boolean;
    onClick?: (event: any) => void;
    label: string;
}
/**
 *
 */
export default function DropDownSwitchButton(props: IButtonSwitch) {
    const visibilityClasses = visibility();
    const classes = dropdownSwitchButtonClasses();

    const { isLoading, status, onClick, label } = props;
    const checkStatus = status ? (
        <>
            <CheckCompactIcon aria-hidden={true} />
            <span className={visibilityClasses.visuallyHidden}>{t("on")}</span>
        </>
    ) : (
        <>
            <span className={visibilityClasses.visuallyHidden}>{t("off")}</span>
        </>
    );

    const content = (
        <>
            <span className={classes.itemLabel}>{label}</span>
            <span className={classNames(classes.checkContainer, "sc-only")}>
                {isLoading ? <ButtonLoader /> : checkStatus}
            </span>
        </>
    );

    return (
        <DropDownItemButton onClick={onClick} role={"switch"} aria-checked={status} disabled={props.isLoading}>
            {content}
        </DropDownItemButton>
    );
}
