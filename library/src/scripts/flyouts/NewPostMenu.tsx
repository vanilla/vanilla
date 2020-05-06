import React, { useState, useRef, useMemo, useEffect } from "react";
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
import { t } from "@vanilla/i18n";
import { TabHandler } from "@vanilla/dom-utils";

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

function ActionItem({
    item,
    style,
    aid,
    onFocus,
}: {
    item: IAddPost;
    style?: ITransition;
    aid?: string;
    onFocus: (string) => void;
}) {
    const { action, className, type, label, icon } = item;
    const classes = newPostMenuClasses();

    const contents = (
        <>
            {icon}
            <span className={classes.label}>{label}</span>
        </>
    );

    const id = `${aid}-${item.id}`;
    return (
        <animated.li
            id={id}
            onFocus={() => onFocus(id)}
            role="menuitem"
            style={style}
            className={classNames(classes.item)}
        >
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
    const backgroundRef = useRef<HTMLElement>(null);
    const menuRef = useRef<HTMLUListElement>(null);
    const itemsRef = useRef<HTMLLIElement>(null);

    const accessMenuRef = useRef<HTMLUListElement>(null);

    const [open, setOpen] = useState(false);
    const [buttonFocus, setButtonFocus] = useState(false);
    const [activeItem, setActiveItem] = useState("");

    const toggle = () => setOpen(!open);

    const onClickBackground = (event: React.MouseEvent) => {
        event.stopPropagation();
        if (open) {
            setButtonFocus(true);
            setOpen(false);
        }
    };

    const onKeyDown = (event: React.KeyboardEvent<any>) => {
        event.preventDefault();
        event.stopPropagation();
        let tabHandler;

        if (accessMenuRef.current) {
            tabHandler = new TabHandler(accessMenuRef.current);
            // if (tabHandler) {
            //     console.log(tabHandler.tabbableElements.length);
            // }
        }
        if (!open) {
            switch (event.key) {
                case "ArrowDown":
                    setOpen(true);
                    console.log(tabHandler.tabbableElements.length);
                    break;
                default:
                    break;
            }
        }

        // if (open) {
        //     switch (event.key) {
        //         case "Escape":
        //             setOpen(false);
        //             setButtonFocus(true);
        //             break;
        //         default:
        //             break;
        //     }
        // } else {
        //     switch (event.key) {
        //         case "ArrowDown":
        //             setOpen(true);
        //             if (tabHandler) {
        //                 const initial = tabHandler.getInitial();
        //                 if (initial) {
        //                     // console.log("here");
        //                     initial.focus();
        //                 }
        //             }
        //             break;
        //         default:
        //             break;
        //     }
        // }

        // if (event.key === "Escape" && open) {
        //     setButtonFocus(true);
        //     setOpen(false);
        // }
        // if (accessMenuRef.current) {
        //     const tabHandler = new TabHandler(accessMenuRef.current);
        //     // console.log(tabHandler.tabbableElements.length);
        // }
    };

    const onFocus = (id: string) => {
        console.log(id);
        setActiveItem(id);
    };

    useEffect(() => {
        if (open && buttonRef.current) {
            buttonRef.current.focus();
        }

        if (!open && buttonFocus && buttonRef.current) {
            buttonRef.current.focus();
        }
    }, [open, buttonRef, buttonFocus]);

    const ID = useMemo(() => uniqueIDFromPrefix("newpost"), []);
    const buttonID = ID + "-button";
    const menuID = ID + "-menu";

    const bgVars = newPostBackgroundVariables();
    const trans = useSpring({
        ref: backgroundRef,
        backgroundColor: open ? colorOut(bgVars.container.color.open) : colorOut(bgVars.container.color.close),
        from: { backgroundColor: colorOut(bgVars.container.color.close) },
        config: { duration: bgVars.container.duration },
    });

    const AnimatedButton = animated(Button);
    const { o, d, s } = useSpring({
        config: { duration: 150 },
        o: open ? vars.toggle.opacity.open : vars.toggle.opacity.close,
        d: open ? vars.toggle.degree.open : vars.toggle.degree.close,
        s: open ? vars.toggle.scale.open : vars.toggle.scale.close,
        from: { o: vars.toggle.opacity.close, d: vars.toggle.degree.close, s: vars.toggle.scale.close },
    });

    const menu = useSpring({
        ref: menuRef,
        config: { duration: 150 },
        opacity: open ? vars.menu.opacity.open : vars.menu.opacity.close,
        display: open ? vars.menu.display.open : vars.menu.display.close,
        from: { opacity: vars.menu.opacity.close, display: vars.menu.display.close },
    });

    const trail = useTrail(items.length, {
        ref: itemsRef,
        config: { mass: 2, tension: 3500, friction: 100 },
        opacity: toggle ? vars.item.opacity.open : vars.item.opacity.close,
        transform: open
            ? `translate3d(0, ${vars.item.transformY.open}, 0)`
            : `translate3d(0, ${vars.item.transformY.close}%, 0)`,
        from: { opacity: vars.item.opacity.close, transform: `translate3d(0, ${vars.item.transformY.close}%, 0)` },
    });

    useChain(
        open ? [menuRef, itemsRef, backgroundRef] : [itemsRef, menuRef, backgroundRef],
        open ? [0.2, 0.2, 0.15] : [0.2, 0.25, 0.15],
    );

    return (
        <NewPostBackground onKeyDown={onKeyDown} trans={trans} open={open} onClick={onClickBackground}>
            <div className={classNames(classes.root)}>
                <animated.ul
                    ref={accessMenuRef}
                    style={menu}
                    id={menuID}
                    role="menu"
                    aria-labelledby={buttonID}
                    tabIndex={-1}
                    aria-activedescendant={activeItem}
                >
                    {trail.map(({ opacity, transform, ...rest }, index) => (
                        <ActionItem
                            onFocus={onFocus}
                            key={items[index].id}
                            item={items[index]}
                            style={{ opacity, transform }}
                            aid={ID}
                        />
                    ))}
                </animated.ul>

                <AnimatedButton
                    id={buttonID}
                    aria-haspopup="true"
                    aria-controls={menuID}
                    aria-expanded={toggle}
                    title={t("New Post Menu")}
                    aria-label={t("New Post Menu")}
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
