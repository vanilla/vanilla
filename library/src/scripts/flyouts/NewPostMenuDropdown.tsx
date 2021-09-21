import React, { useState } from "react";
import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";
import { t } from "@vanilla/i18n";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import SmartLink from "@library/routing/links/SmartLink";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { WidgetLayoutWidget } from "@library/layout/WidgetLayoutWidget";
import { IAddPost } from "@library/flyouts/NewPostMenu";

interface NewPostMenuDropDownProps {
    items: IAddPost[];
}

export default function NewPostMenuDropDown(props: NewPostMenuDropDownProps) {
    const classes = newPostMenuClasses();
    const [dropDownOpen, setDropDownStatus] = useState<boolean>(false);
    const { items } = props;

    const onVisibilityChange = (dropDownStatus) => {
        setDropDownStatus(dropDownStatus);
    };

    const buttonLabel = items.length === 1 ? t(items[0].label) : t("New Post");

    const buttonContents = (
        <div className={classes.buttonContents}>
            <div
                className={classes.buttonIcon}
                style={{
                    transform: dropDownOpen ? "rotate(-135deg)" : "rotate(0deg)",
                    transition: "transform .25s",
                }}
            >
                <NewPostMenuIcon />
            </div>
            <div className={classes.buttonLabel}>{buttonLabel}</div>
        </div>
    );

    const content =
        items.length === 1 ? (
            <LinkAsButton buttonType={ButtonTypes.PRIMARY} className={classes.button} to={items[0].action as string}>
                {buttonContents}
            </LinkAsButton>
        ) : (
            <DropDown
                flyoutType={FlyoutType.FRAME}
                buttonType={ButtonTypes.PRIMARY}
                buttonClassName={classes.button}
                contentsClassName={classes.buttonDropdownContents}
                onVisibilityChange={onVisibilityChange}
                isSmall={true}
                buttonContents={buttonContents}
            >
                {items.map((item, i) => {
                    return (
                        <DropDownItem key={i}>
                            <SmartLink to={item.action as string} className={dropDownClasses().action}>
                                {item.label}
                            </SmartLink>
                        </DropDownItem>
                    );
                })}
            </DropDown>
        );

    return <>{content}</>;
}
