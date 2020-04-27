import React, { ReactNode, useState } from "react";
import classNames from "classnames";

import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";
import { Trail } from "react-spring/renderprops";
import NewPostItem from "@library/flyouts/NewPostMenuItem";

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

export interface ITransition {
    opacity: number;
    transform: string;
}

export default function NewPostItems(props: { items: IAddPost[] }) {
    const classes = newPostMenuClasses();
    const { items } = props;

    return (
        <Trail
            config={{ mass: 2, tension: 3000, friction: 150 }}
            items={items}
            keys={item => item.id}
            from={{ opacity: 0, transform: "translate3d(0, 100%, 0)" }}
            to={{ opacity: 1, transform: "translate3d(0, 0, 0)" }}
        >
            {item => props => <NewPostItem key={item.id} item={item} style={props} />}
        </Trail>
    );
}
