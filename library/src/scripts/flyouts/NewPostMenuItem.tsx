import React, { ReactNode, useState } from "react";
import classNames from "classnames";

import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";
import { PostTypes, ITransition, IAddPost } from "./NewPostMenu";

export default function NewPostItem({ item, style }: { item: IAddPost; style: ITransition }) {
    const { action, className, type, label, icon } = item;
    const classes = newPostMenuClasses();

    const contents = (
        <>
            {icon}
            <span className={classes.label}>{label}</span>
        </>
    );

    return (
        <div style={style} className={classNames(classes.item)}>
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
