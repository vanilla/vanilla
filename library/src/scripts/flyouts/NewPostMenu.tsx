import React, { ReactNode, useState } from "react";
import classNames from "classnames";

import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";

export enum PostTypes {
    LINK = "link",
    BUTTON = "button",
}

export interface IAddPost {
    id: string;
    action: (() => void) | string;
    type: PostTypes;
    className?: string;
    label: string;
    icon: JSX.Element;
}

function ActionItem(props: IAddPost) {
    const { action, className, type, label, icon } = props;
    const classes = newPostMenuClasses();

    const contents = (
        <>
            {icon}
            <span className={classes.label}>{label}</span>
        </>
    );

    return (
        <div className={classNames(classes.item)}>
            {type === PostTypes.BUTTON ? (
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(className, classes.action)}
                    onClick={action as () => void}
                >
                    {contents}
                </Button>
            ) : (
                <LinkAsButton
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(className, classes.action)}
                    to={action as string}
                >
                    {contents}
                </LinkAsButton>
            )}
        </div>
    );
}

export default function NewPostMenu(props: { items: IAddPost[] }) {
    const [open, setOpen] = useState(false);
    const toggle = () => setOpen(!open);

    const classes = newPostMenuClasses();
    const { items } = props;

    return (
        <div className={classNames(classes.root)}>
            {open && items.map(item => <ActionItem key={item.id} {...item} />)}

            <Button
                baseClass={ButtonTypes.CUSTOM}
                onClick={toggle}
                className={classNames(classes.toggle, {
                    [classes.isOpen]: open,
                })}
            >
                <NewPostMenuIcon />
            </Button>
        </div>
    );
}
