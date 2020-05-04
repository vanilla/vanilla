import React, { ReactNode, useState, useRef, useMemo } from "react";
import classNames from "classnames";

import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses, newPostMenuVariables } from "@library/flyouts/newPostMenuStyles";
import { useSpring, animated, interpolate, useChain } from "react-spring";
import NewPostBackground from "./NewPostBackground";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { newPostBackgroundVariables } from "./newPostBackgroundStyles";
import { colorOut } from "@library/styles/styleHelpers";
import { useTrail } from "react-spring";

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

function ActionItem({ item, style, aid }: { item: IAddPost; style?: ITransition; aid?: string }) {
    const { action, className, type, label, icon } = item;
    const classes = newPostMenuClasses();

    const contents = (
        <>
            {icon}
            <span className={classes.label}>{label}</span>
        </>
    );

    return (
        <animated.li id={`${aid}-${item.id}`} role="menuitem" style={style} className={classNames(classes.item)}>
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
        </animated.li>
    );
}

export default function NewPostMenu(props: { items: IAddPost[] }) {
    const classes = newPostMenuClasses();
    const vars = newPostMenuVariables();

    let { items } = props;
    const buttonRef = useRef<HTMLButtonElement>(null);

    const [open, setOpen] = useState(false);

    const toggle = () => {
        if (!open && buttonRef.current) {
            buttonRef.current.focus();
        }
        setOpen(!open);
    };

    const onClickBackground = e => {
        e.stopPropagation();
        if (open) {
            setOpen(false);
        }
    };

    const ID = useMemo(() => uniqueIDFromPrefix("newpost"), []);
    const buttonID = ID + "-button";
    const menuID = ID + "-menu";

    const bkgVars = newPostBackgroundVariables();
    const bkgAnimationRef = useRef();
    const trans = useSpring({
        ref: bkgAnimationRef,
        backgroundColor: open ? colorOut(bkgVars.container.color.open) : colorOut(bkgVars.container.color.close),
        from: { backgroundColor: colorOut(bkgVars.container.color.close) },
        config: { duration: bkgVars.container.duration },
    });

    const buttonAnimationRef = useRef();
    const AnimatedButton = animated(Button);
    const { o, d, s } = useSpring({
        ref: buttonAnimationRef,
        config: { duration: 150 },
        o: open ? vars.toggle.opacity.open : vars.toggle.opacity.close,
        d: open ? vars.toggle.degree.open : vars.toggle.degree.close,
        s: open ? vars.toggle.scale.open : vars.toggle.scale.close,
        from: { o: vars.toggle.opacity.close, d: vars.toggle.degree.close, s: vars.toggle.scale.close },
    });

    const menuAnimationRef = useRef();
    const menu = useSpring({
        ref: menuAnimationRef,
        config: { duration: 150 },
        opacity: open ? vars.menu.opacity.open : vars.menu.opacity.close,
        display: open ? vars.menu.display.open : vars.menu.display.close,
        from: { opacity: vars.menu.opacity.close, display: vars.menu.display.close },
    });

    const itemsAnimationRef = useRef();
    const trail = useTrail(items.length, {
        ref: itemsAnimationRef,
        config: { mass: 2, tension: 3500, friction: 100 },
        opacity: toggle ? vars.item.opacity.open : vars.item.opacity.close,
        transform: open
            ? `translate3d(0, ${vars.item.transformY.open}, 0)`
            : `translate3d(0, ${vars.item.transformY.close}%, 0)`,
        from: { opacity: vars.item.opacity.close, transform: `translate3d(0, ${vars.item.transformY.close}%, 0)` },
    });

    useChain(
        open
            ? [buttonAnimationRef, menuAnimationRef, itemsAnimationRef, bkgAnimationRef]
            : [buttonAnimationRef, itemsAnimationRef, menuAnimationRef, bkgAnimationRef],
        open ? [0.1, 0.2, 0.2, 0.15] : [0.1, 0.2, 0.25, 0.15],
    );

    return (
        <NewPostBackground trans={trans} open={open} onClick={onClickBackground}>
            <div className={classNames(classes.root)}>
                <animated.ul
                    style={menu}
                    id={menuID}
                    role="menu"
                    aria-labelledby={buttonID}
                    tabIndex={-1}
                    aria-activedescendant={`ID-${items[0].id}`} // See id={`${aid}-${item.id}` in ActionItem
                >
                    {trail.map(({ opacity, transform, ...rest }, index) => (
                        <ActionItem key={items[index].id} item={items[index]} style={{ opacity, transform }} aid={ID} />
                    ))}
                </animated.ul>

                <AnimatedButton
                    id={buttonID}
                    aria-haspopup="true"
                    aria-controls={menuID}
                    aria-expanded={toggle}
                    title={"New Post Menu"}
                    aria-label={"New Post Menu"}
                    buttonRef={buttonRef}
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
