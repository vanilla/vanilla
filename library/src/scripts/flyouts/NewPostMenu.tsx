import React, { ReactNode, useState } from "react";
import classNames from "classnames";

import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";
import { Trail } from "react-spring/renderprops";
import { useSpring, animated, interpolate } from "react-spring";

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

    const classes = newPostMenuClasses();
    const { items } = props;

    const { x, d, s } = useSpring({
        config: { duration: 150 },
        x: open ? 1 : 0,
        d: open ? -135 : 0,
        s: open ? 0.9 : 1,
        from: { x: 0, d: 0, s: 1 },
    });
    const AnimatedButton = animated(Button);
    return (
        <div className={classNames(classes.root)}>
            {open && (
                <Trail
                    config={{ mass: 2, tension: 3000, friction: 150 }}
                    items={items}
                    keys={item => item.id}
                    from={{ opacity: 0, transform: "translate3d(0, 100%, 0)" }}
                    to={{ opacity: 1, transform: "translate3d(0, 0, 0)" }}
                >
                    {item => props => <ActionItem key={item.id} item={item} style={props} />}
                </Trail>
            )}

            <AnimatedButton
                style={{
                    opacity: x
                        .interpolate({
                            range: [0, 0.25, 0.45, 0.75, 1],
                            output: [1, 0.97, 0.7, 0.9, 1],
                        })
                        .interpolate(x => `${x}`),
                    transform: interpolate([d, s], (d, s) => `rotate(${d}deg) scale(${s})`),
                }}
                baseClass={ButtonTypes.CUSTOM}
                onClick={toggle}
                // className={classNames(classes.toggle, {
                //     [classes.isOpen]: open,
                // })}
                className={classNames(classes.toggle)}
            >
                <NewPostMenuIcon />
            </AnimatedButton>
        </div>
    );
}
