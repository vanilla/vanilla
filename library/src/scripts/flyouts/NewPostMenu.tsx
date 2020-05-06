import React, { useState, useRef, useMemo, useEffect, useReducer } from "react";
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

const initialState = {
    open: false,
    buttonFocus: false,
    activeItem: "", // for aria-activedescendant
    focusedItem: undefined,
};

const reducer = (state, action) => {
    switch (action.type) {
        case "toggle_open":
            return { ...state, open: !state.open };
        case "set_open":
            return { ...state, open: action.open };
        case "set_active_item":
            return { ...state, activeItem: action.item };
        case "set_button_focus":
            return { ...state, buttonFocus: action.focus };
        case "set_focused_item":
            return { ...state, focusedItem: action.item };
        default:
            throw new Error();
    }
};

export default function NewPostMenu(props: { items: IAddPost[] }) {
    const classes = newPostMenuClasses();
    const vars = newPostMenuVariables();

    let { items } = props;
    const buttonRef = useRef<HTMLButtonElement>(null);
    const backgroundRef = useRef<HTMLElement>(null);
    const menuRef = useRef<HTMLUListElement>(null);
    const itemsRef = useRef<HTMLLIElement>(null);

    const accessMenuRef = useRef<HTMLUListElement>(null);

    const [state, dispatch] = useReducer(reducer, initialState);

    const onClickBackground = (event: React.MouseEvent) => {
        event.stopPropagation();
        if (state.open) {
            dispatch({ type: "set_button_focus", focus: true });
            dispatch({ type: "set_open", open: false });
            dispatch({ type: "set_focused_item", item: undefined });
        }
    };

    const onKeyDown = (event: React.KeyboardEvent<any>) => {
        event.preventDefault();
        event.stopPropagation();

        switch (event.key) {
            case "Escape":
                if (state.open) {
                    dispatch({ type: "set_open", open: false });
                    dispatch({ type: "set_button_focus", focus: true });
                    dispatch({ type: "set_focused_item", item: undefined });
                }
                break;
            case "ArrowDown":
                if (state.buttonFocus) {
                    dispatch({ type: "set_focused_item", item: 0 });
                    dispatch({ type: "set_open", open: true });
                }
                break;
            default:
                // We don't want to lose focus if the user presses other keys
                if (state.buttonFocus) {
                    console.log("here");
                    dispatch({ type: "set_button_focus", focus: true });
                }
                break;
        }
    };

    const onFocus = (id: string) => {
        dispatch({ type: "set_active_item", item: id });
    };

    // useEffect(() => {
    //     // if (accessMenuRef.current) {
    //     //     const tabHandler = new TabHandler(accessMenuRef.current);
    //     //     console.log(tabHandler.getAll()?.length);
    //     // }
    //     if (state.buttonFocus && buttonRef.current) {
    //         buttonRef.current.focus();
    //     }
    //     if (state.open && buttonRef.current) {
    //         buttonRef.current.focus();
    //     }

    //     if (!state.open && state.buttonFocus && buttonRef.current) {
    //         buttonRef.current.focus();
    //     }
    // }, [state.open, buttonRef, state.buttonFocus, state.focusedItem, accessMenuRef, state]);

    const handleAccessibility = () => {
        if ((state.open || state.buttonFocus) && buttonRef.current) {
            buttonRef.current.focus();
        }

        console.log(state);

        if (accessMenuRef.current) {
            const tabHandler = new TabHandler(accessMenuRef.current);
            console.log(tabHandler.getAll()?.length);
            const first = tabHandler.getInitial();
            if (first) {
                // console.log("here");
                first.focus();
            }
        }

        // if (accessMenuRef.current) {
        //     const tabHandler = new TabHandler(accessMenuRef.current);
        //     if (state.focusedItem === 0) {
        //         const first = tabHandler.getInitial();
        //         if (first) {
        //             first.focus();
        //         }
        //     }
        // }

        // if (accessMenuRef.current) {
        //     const tabHandler = new TabHandler(accessMenuRef.current);
        //     console.log(tabHandler.getAll()?.length);
        // }
    };

    const ID = useMemo(() => uniqueIDFromPrefix("newpost"), []);
    const buttonID = ID + "-button";
    const menuID = ID + "-menu";

    const bgVars = newPostBackgroundVariables();
    const trans = useSpring({
        ref: backgroundRef,
        backgroundColor: state.open ? colorOut(bgVars.container.color.open) : colorOut(bgVars.container.color.close),
        from: { backgroundColor: colorOut(bgVars.container.color.close) },
        config: { duration: bgVars.container.duration },
    });

    const AnimatedButton = animated(Button);
    const { o, d, s } = useSpring({
        config: { duration: 150 },
        o: state.open ? vars.toggle.opacity.open : vars.toggle.opacity.close,
        d: state.open ? vars.toggle.degree.open : vars.toggle.degree.close,
        s: state.open ? vars.toggle.scale.open : vars.toggle.scale.close,
        from: { o: vars.toggle.opacity.close, d: vars.toggle.degree.close, s: vars.toggle.scale.close },
    });

    const menu = useSpring({
        ref: menuRef,
        config: { duration: 150 },
        opacity: state.open ? vars.menu.opacity.open : vars.menu.opacity.close,
        display: state.open ? vars.menu.display.open : vars.menu.display.close,
        from: { opacity: vars.menu.opacity.close, display: vars.menu.display.close },
        // onRest: handleAccessibility,
    });

    const trail = useTrail(items.length, {
        ref: itemsRef,
        config: { mass: 2, tension: 3500, friction: 100 },
        opacity: state.open ? vars.item.opacity.open : vars.item.opacity.close,
        transform: state.open
            ? `translate3d(0, ${vars.item.transformY.open}, 0)`
            : `translate3d(0, ${vars.item.transformY.close}%, 0)`,
        from: { opacity: vars.item.opacity.close, transform: `translate3d(0, ${vars.item.transformY.close}%, 0)` },
        onRest: handleAccessibility,
    });

    useChain(
        state.open ? [menuRef, itemsRef, backgroundRef] : [itemsRef, menuRef, backgroundRef],
        state.open ? [0.2, 0.2, 0.15] : [0.2, 0.25, 0.15],
    );

    return (
        <NewPostBackground onKeyDown={onKeyDown} trans={trans} open={state.open} onClick={onClickBackground}>
            <div className={classNames(classes.root)}>
                <animated.ul
                    style={menu}
                    ref={accessMenuRef}
                    id={menuID}
                    role="menu"
                    aria-labelledby={buttonID}
                    tabIndex={-1}
                    aria-activedescendant={state.activeItem}
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
                    aria-expanded={state.open}
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
                    onClick={() => dispatch({ type: "toggle_open" })}
                    className={classNames(classes.toggle)}
                >
                    <NewPostMenuIcon />
                </AnimatedButton>
            </div>
        </NewPostBackground>
    );
}
