import React from "react";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { IUserFragment } from "@library/@types/api/users";
import { visibility } from "@library/styles/styleHelpersVisibility";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { dropdownSwitchButtonClasses } from "@library/flyouts/dropDownSwitchButtonStyles";
import { CheckCompactIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n/src";
import classNames from "classnames";
import ButtonLoader from "@library/loaders/ButtonLoader";
import DateTime from "@library/content/DateTime";

interface IProps {
    name?: string;
    imageUrl?: string;
    date?: string;
    isSelected?: boolean;
    userInfo: IUserFragment;
    revisionID: number;
    onClick?: (event: any) => void;
    isLoading?: boolean;
}

export function ThemeRevisionItem(props: IProps) {
    const visibilityClasses = visibility();
    const classes = dropdownSwitchButtonClasses();

    const checkStatus = props.isSelected ? (
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
            <div style={{ display: "flex" }}>
                <UserPhoto userInfo={props.userInfo} size={UserPhotoSize.MEDIUM} />
                <div style={{ margin: "10px", width: "150px" }}>
                    <span style={{ display: "block" }} className={classes.itemLabel}>
                        {props.name}
                    </span>
                    <span style={{ display: "block" }} className={classes.itemLabel}>
                        <DateTime timestamp={props.date} />
                    </span>
                </div>
                <span className={classNames(classes.checkContainer, "sc-only")}>
                    {props.isLoading ? <ButtonLoader /> : checkStatus}
                </span>
            </div>
        </>
    );

    return (
        <DropDownItemButton
            onClick={props.onClick}
            role={"switch"}
            aria-checked={props.isSelected}
            disabled={props.isLoading}
        >
            {content}
        </DropDownItemButton>
    );
}
