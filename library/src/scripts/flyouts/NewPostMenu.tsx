import React, { ReactNode, useState } from "react";
import classNames from "classnames";

import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";
import { Trail } from "react-spring/renderprops";
import { useSpring, animated, interpolate } from "react-spring";
import NewPostBackground from "./NewPostBackground";

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
    icon: React.ReactNode;
}

export interface ITransition {
    opacity: number;
    transform: string;
}

function ActionItem({ item, style }: { item: IAddPost; style: ITransition }) {
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

export default function NewPostMenu(props: { items: IAddPost[] }) {
    const [open, setOpen] = useState(false);
    const toggle = () => setOpen(!open);
    const onClickBackground = () => {
        if (open) {
            setOpen(false);
        }
    };

    const classes = newPostMenuClasses();
    const { items } = props;

    // Animation parameters: o (opacity), d (degree), s (scale)
    const AnimatedButton = animated(Button);
    const { o, d, s } = useSpring({
        config: { duration: 150 },
        o: open ? 1 : 0,
        d: open ? -135 : 0,
        s: open ? 0.9 : 1,
        from: { o: 0, d: 0, s: 1 },
    });

    return (
        <NewPostBackground open={open} onClick={onClickBackground}>
            <div className={classNames(classes.root)}>
                <Trail
                    reverse={open}
                    config={{ mass: 2, tension: 4000, friction: 100 }}
                    items={items}
                    keys={item => item.id}
                    from={{ opacity: 0, transform: "translate3d(0, 100%, 0)" }}
                    to={{ opacity: open ? 1 : 0, transform: open ? "translate3d(0, 0, 0)" : "translate3d(0, 100%, 0)" }}
                >
                    {item => props => <ActionItem key={item.id} item={item} style={props} />}
                </Trail>

                <AnimatedButton
                    style={{
                        opacity: o
                            .interpolate({
                                range: [0, 0.25, 0.45, 0.75, 1],
                                output: [1, 0.97, 0.7, 0.9, 1],
                            })
                            .interpolate(x => `${o}`),
                        transform: interpolate([d, s], (d, s) => `rotate(${d}deg) scale(${s})`),
                    }}
                    baseClass={ButtonTypes.CUSTOM}
                    onClick={toggle}
                    className={classNames(classes.toggle)}
                >
                    <NewPostMenuIcon />
                </AnimatedButton>
            </div>
        </NewPostBackground>
    );
}
