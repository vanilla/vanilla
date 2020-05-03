import React, { ReactNode, useState, useRef, useMemo } from "react";
import classNames from "classnames";

import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses } from "@library/flyouts/newPostMenuStyles";
import { Trail } from "react-spring/renderprops";
import { useSpring, animated, interpolate, useChain } from "react-spring";
import NewPostBackground from "./NewPostBackground";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { newPostBackgroundClasses, newPostBackgroundVariables } from "./newPostBackgroundStyles";
import { colorOut } from "@library/styles/styleHelpers";
import { useTransition } from "react-spring";
import { useTrail } from "react-spring";

export enum PostTypes {
    LINK = "link",
    BUTTON = "button",
}

export interface IAddPost {
    id: string;
    aid?: string;
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

function ActionItem({ item, style }: { item: IAddPost; style?: ITransition }) {
    const { action, className, type, label, icon, aid } = item;
    const classes = newPostMenuClasses();

    const contents = (
        <>
            {icon}
            <span className={classes.label}>{label}</span>
        </>
    );

    return (
        <animated.li id={aid} style={style} className={classNames(classes.item)} role="menuitem">
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
    let { items } = props;

    const [open, setOpen] = useState(false);

    const buttonRef = useRef<HTMLButtonElement>(null);
    const ID = useMemo(() => uniqueIDFromPrefix("newpost"), []);
    const buttonID = ID + "-button";
    const menuID = ID + "-menu";
    // const menuItemIDs = items.map(item => ID + `-${item.id}`);
    items = items.map(item => ({ ...item, aid: ID + `-${item.id}` }));

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

    const vars = newPostBackgroundVariables();

    const bkgAnimationRef = useRef();
    const trans = useSpring({
        ref: bkgAnimationRef,
        backgroundColor: open ? colorOut(vars.container.color.open) : colorOut(vars.container.color.close),
        from: { backgroundColor: colorOut(vars.container.color.close) },
        config: { duration: vars.container.duration },
    });

    // Animation parameters: o (opacity), d (degree), s (scale)
    const butAnimationRef = useRef();
    const AnimatedButton = animated(Button);
    const { o, d, s } = useSpring({
        ref: butAnimationRef,
        config: { duration: 150 },
        o: open ? 1 : 0,
        d: open ? -135 : 0,
        s: open ? 0.9 : 1,
        from: { o: 0, d: 0, s: 1 },
    });

    const menuAnimationRef = useRef();
    const ma = useSpring({
        ref: menuAnimationRef,
        config: { duration: 150 },
        opacity: open ? 1 : 0,
        display: open ? "block" : "none",
        from: { opacity: 0, display: "none" },
    });

    const itemsAnimationRef = useRef();
    const trail = useTrail(items.length, {
        ref: itemsAnimationRef,
        config: { mass: 2, tension: 3500, friction: 100 },
        opacity: toggle ? 1 : 0,
        // x: toggle ? 0 : 20,
        // height: toggle ? 80 : 0,
        // from: { opacity: 0, x: 20, height: 0 },
        transform: open ? "translate3d(0, 0, 0)" : "translate3d(0, 100%, 0)",
        from: { opacity: 0, transform: "translate3d(0, 100%, 0)" },
    });

    useChain(
        open
            ? [butAnimationRef, menuAnimationRef, itemsAnimationRef, bkgAnimationRef]
            : [butAnimationRef, itemsAnimationRef, menuAnimationRef, bkgAnimationRef],
        [0.1, 0.1, 0.13, 0.14],
    );
    // useChain([butAnimationRef, itemsAnimationRef]);

    return (
        <NewPostBackground trans={trans} open={open} onClick={onClickBackground}>
            <div className={classNames(classes.root)}>
                <animated.ul
                    style={ma}
                    id={menuID}
                    role="menu"
                    aria-labelledby={buttonID}
                    tabIndex={-1}
                    aria-activedescendant={buttonID}
                >
                    {/* <Trail
                        reverse={open}
                        config={{ mass: 2, tension: 4000, friction: 100 }}
                        items={items}
                        keys={item => item.id}
                        from={{ opacity: 0, transform: "translate3d(0, 100%, 0)" }}
                        to={{
                            opacity: open ? 1 : 0,
                            transform: open ? "translate3d(0, 0, 0)" : "translate3d(0, 100%, 0)",
                        }}
                    >
                        {item => props => <ActionItem key={item.id} item={item} style={props} />}
                    </Trail> */}
                    {/* {items.map(item => (
                            <ActionItem key={item.id} item={item} />
                        ))} */}
                    {trail.map(({ opacity, transform, ...rest }, index) => {
                        // console.log(transform);
                        // console.log(index);
                        return <ActionItem style={{ opacity, transform }} key={items[index].id} item={items[index]} />;
                    })}

                    {/* {itemTransitions.map(({ item }) => {
                            // console.log(item);
                            return <ActionItem key={item.id} item={item} />;
                        })} */}
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
