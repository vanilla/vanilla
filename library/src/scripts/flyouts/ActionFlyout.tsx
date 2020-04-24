import React, { ReactNode, useState } from "react";
import classNames from "classnames";
import { actionFlyoutClasses } from "@library/flyouts/actionFlyoutStyles";
import { PostFlyoutIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

export enum ActionTypes {
    LINK = "link",
    BUTTON = "button",
}

interface IAction {
    action: (() => void) | string;
    type: ActionTypes;
    className?: string;
    label: string;
    icon: JSX.Element;
}

function ActionItem(props: IAction) {
    const { action, className, type, label, icon } = props;
    const classes = actionFlyoutClasses();

    const contents = (
        <>
            {icon}
            <span className={classes.label}>{label}</span>
        </>
    );

    return type === ActionTypes.BUTTON ? (
        <Button onClick={action as () => void} className={classNames(className, classes.action)}>
            {contents}
        </Button>
    ) : (
        <LinkAsButton to={action as string} className={classNames(className, classes.action)}>
            {contents}
        </LinkAsButton>
    );
}

export default function ActionFlyout(actions: IAction[]) {
    const [open, setOpen] = useState(false);

    const toggle = () => setOpen(!open);

    const classes = actionFlyoutClasses();
    return (
        <div className={classNames(classes.root)}>
            {open &&
                actions.map((action, i) => {
                    return <ActionItem key={i} {...action} />;
                })}
            <Button
                baseClass={ButtonTypes.CUSTOM}
                onClick={toggle}
                className={classNames(classes.toggle, {
                    [classes.isOpen]: open,
                })}
            >
                <PostFlyoutIcon />
            </Button>
        </div>
    );
}
