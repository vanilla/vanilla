import React, { ReactNode, useState } from "react";
import classNames from "classnames";
import { actionFlyoutClasses } from "@library/flyouts/ActionFlyoutStyles";
import { PostFlyoutIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconClasses";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { assetUrl } from "@library/utility/appUtils";
import { action } from "@storybook/addon-actions";

type Icon = ReactNode;

export enum ActionType {
    LINK = "link",
    BUTTON = "button",
}

export interface IAssets {
    icon: Icon;
    label: string;
}

export interface IActionButton {
    id: string;
    type: ActionType;
    assets: IAssets;
    action: () => void;
}

export interface IActionLink {
    id: string;
    type: ActionType;
    assets: IAssets;
    link: string;
}

interface IProps {
    actions: Array<IActionLink | IActionButton>;
}

function ActionItem({
    action,
    className,
    buttonClass,
    iconClass,
}: {
    action: IActionButton | IActionLink;
    className: string;
    buttonClass: string;
    iconClass: string;
}) {
    const { icon, label } = action.assets;

    return (
        <div className={classNames(className)}>
            {action.type === ActionType.BUTTON ? (
                <Button onClick={(action as IActionButton).action} className={classNames(buttonClass)}>
                    <span className={classNames(iconClass)}> {icon} </span>
                    <span> {label} </span>
                </Button>
            ) : (
                <LinkAsButton to={(action as IActionLink).link} className={classNames(buttonClass)}>
                    <span className={classNames(iconClass)}> {icon} </span>
                    <span> {label} </span>
                </LinkAsButton>
            )}
        </div>
    );
}

export default function ActionFlyout({ actions }: IProps) {
    const [open, setOpen] = useState(false);

    const openToggle = () => setOpen(!open);

    const classes = actionFlyoutClasses();
    return (
        <div className={classNames(classes.root)}>
            {open &&
                actions.map(action => (
                    <ActionItem
                        key={action.id}
                        className={classes.item}
                        buttonClass={classes.button}
                        iconClass={classes.icon}
                        action={action}
                    />
                ))}
            <div onClick={openToggle} className={classNames({ [classes.click]: true, [classes.clickOpen]: open })}>
                <PostFlyoutIcon />
            </div>
        </div>
    );
}
