/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useRef, useMemo, useReducer } from "react";
import classNames from "classnames";
import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuClasses, newPostMenuVariables } from "@library/newPostMenu/newPostMenuStyles";
import { useSpring, animated, interpolate, useChain } from "react-spring";
import NewPostBackground from "./NewPostBackground";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { useTrail } from "react-spring";
import { t } from "@vanilla/i18n";
import { TabHandler } from "@vanilla/dom-utils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Icon, IconType } from "@vanilla/icons";

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
    icon: string;
}

export interface ITransition {
    opacity: number;
    transform: string;
}

interface IProps {
    item: IAddPost;
    style?: ITransition;
    aid?: string; // accessibility id
}

interface NewPostMenuFABProps {
    items: IAddPost[];
}

function ActionItem(props: IProps) {
    const { item, style, aid } = props;
    const { action, className, type, label, icon } = item;
    const classes = newPostMenuClasses();
    const vars = newPostMenuVariables();
    const contents = (
        <>
            <div className={classes.fabItemFocus} aria-hidden={true} />
            <Icon icon={icon as IconType} />
            {!vars.fab.iconsOnly && <span className={classes.fabLabel}>{label}</span>}
        </>
    );

    return (
        <animated.li id={aid} role="menuitem" style={style} className={classNames(classes.fabItem)}>
            {type === PostTypes.BUTTON ? (
                <Button
                    buttonType={ButtonTypes.CUSTOM}
                    className={classNames(className, classes.fabAction)}
                    onClick={action as () => void}
                >
                    {contents}
                </Button>
            ) : (
                <LinkAsButton
                    buttonType={ButtonTypes.CUSTOM}
                    className={classNames(className, classes.fabAction)}
                    to={action as string}
                >
                    {contents}
                </LinkAsButton>
            )}
        </animated.li>
    );
}

interface IState {
    open: boolean;
    buttonFocus: boolean;
    focusedItem?: number;
}

const initialState: IState = {
    open: false,
    buttonFocus: false,
    focusedItem: undefined,
};

const reducer = (state, action) => {
    switch (action.type) {
        case "toggle_open":
            return { ...state, open: !state.open };
        case "set_open":
            return { ...state, open: action.open };
        case "set_button_focus":
            return { ...state, buttonFocus: action.focus };
        case "set_focused_item":
            return { ...state, focusedItem: action.item };
        default:
            throw new Error();
    }
};

export default function NewPostMenuFAB(props: NewPostMenuFABProps) {
    const classes = newPostMenuClasses();
    const vars = newPostMenuVariables();
    const globalVars = globalVariables();
    const { items } = props;
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

    const onBgKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "Escape":
                if (state.open) {
                    event.preventDefault();
                    event.stopPropagation();
                    dispatch({ type: "set_open", open: false });
                    dispatch({ type: "set_button_focus", focus: true });
                    dispatch({ type: "set_focused_item", item: undefined });
                }
                break;
            default:
                break;
        }
    };

    const onMenuItemKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "Escape":
                if (state.open) {
                    dispatch({ type: "set_open", open: false });
                    dispatch({ type: "set_button_focus", focus: true });
                    dispatch({ type: "set_focused_item", item: undefined });
                }
                break;
            case "Home":
                if (state.open) {
                    dispatch({ type: "set_focused_item", item: 0 });
                }
                break;
            case "End":
                if (state.open) {
                    dispatch({ type: "set_focused_item", item: items.length - 1 });
                }
                break;
            case "ArrowUp":
                if (state.open && typeof state.focusedItem != "undefined") {
                    dispatch({ type: "set_focused_item", item: (state.focusedItem - 1 + items.length) % items.length });
                }
                break;
            case "ArrowDown":
                if (state.open && typeof state.focusedItem != "undefined") {
                    dispatch({ type: "set_focused_item", item: (state.focusedItem + 1) % items.length });
                }
                break;
            case "Enter":
                if (typeof state.focusedItem !== "undefined") {
                    const item = items[state.focusedItem];
                    switch (item.type) {
                        case PostTypes.LINK:
                            window.location.href = item.action as string;
                            dispatch({ type: "set_open", open: false });
                            dispatch({ type: "set_button_focus", focus: true });
                            dispatch({ type: "set_focused_item", item: undefined });
                            break;
                        case PostTypes.BUTTON:
                            (item.action as () => void)();
                            dispatch({ type: "set_open", open: false });
                            dispatch({ type: "set_button_focus", focus: true });
                            dispatch({ type: "set_focused_item", item: undefined });
                            break;
                        default:
                            break;
                    }
                }
                break;
            default:
                const itemList = items
                    .map((item, index) => ({ ...item, index }))
                    .filter((item) => item.label.toLocaleUpperCase().startsWith(event.key.toLocaleUpperCase()));
                if (typeof state.focusedItem !== "undefined") {
                    const next = itemList.find((item) => item.index > state.focusedItem);
                    if (next) {
                        dispatch({ type: "set_focused_item", item: next.index });
                    }
                }
                break;
        }
    };

    const onMenuButtonKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "Escape":
                if (state.open) {
                    dispatch({ type: "set_open", open: false });
                    dispatch({ type: "set_button_focus", focus: true });
                    dispatch({ type: "set_focused_item", item: undefined });
                }
                break;
            case "ArrowDown":
            case "Enter":
            case " ":
                if (state.buttonFocus) {
                    dispatch({ type: "set_focused_item", item: 0 });
                    dispatch({ type: "set_open", open: true });
                }
                break;
            case "ArrowUp":
                if (state.buttonFocus) {
                    dispatch({ type: "set_focused_item", item: items.length - 1 });
                    dispatch({ type: "set_open", open: true });
                }
                break;
            default:
                break;
        }
    };

    const handleAccessibility = (items) => {
        if ((state.open || state.buttonFocus) && buttonRef.current) {
            buttonRef.current.focus();
        }

        if (accessMenuRef.current) {
            const tabHandler = new TabHandler(accessMenuRef.current);
            if (state.focusedItem === 0) {
                tabHandler.getInitial()?.focus();
            } else if (state.focusedItem === items.length - 1) {
                tabHandler.getLast()?.focus();
            } else if (typeof state.focusedItem !== "undefined") {
                tabHandler.getInitial()?.focus();
                for (let i = 0; i < state.focusedItem; i++) {
                    tabHandler.getNext()?.focus();
                }
            }
        }
    };

    const ID = useMemo(() => uniqueIDFromPrefix("newpost"), []);
    const buttonID = ID + "-button";
    const menuID = ID + "-menu";

    const bgTransition = useSpring({
        ref: backgroundRef,
        backgroundColor: state.open
            ? ColorsUtils.colorOut(globalVars.elementaryColors.black.fade(0.4))
            : ColorsUtils.colorOut(globalVars.elementaryColors.transparent),
        from: { backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg) },
        config: { duration: 300 },
    });

    const AnimatedButton = animated(Button);
    const { o, d, s } = useSpring({
        config: { duration: 150 },
        o: state.open ? vars.fab.opacity.open : vars.fab.opacity.close,
        d: state.open ? vars.fab.degree.open : vars.fab.degree.close,
        from: { o: vars.fab.opacity.close, d: vars.fab.degree.close },
    });

    const fabMenu = useSpring({
        ref: menuRef,
        config: { duration: 150 },
        opacity: state.open ? vars.fabMenu.opacity.open : vars.fabMenu.opacity.close,
        display: state.open ? vars.fabMenu.display.open : vars.fabMenu.display.close,
        from: { opacity: vars.fabMenu.opacity.close, display: vars.fabMenu.display.close },
        onRest: () => handleAccessibility(items),
    });

    const trail = useTrail(items.length, {
        ref: itemsRef,
        config: { mass: 2, tension: 3500, friction: 100 },
        opacity: state.open ? vars.fabItem.opacity.open : vars.fabItem.opacity.close,
        transform: state.open
            ? `translate3d(0, ${vars.fabItem.transformY.open}, 0)`
            : `translate3d(0, ${vars.fabItem.transformY.close}%, 0)`,
        from: {
            opacity: vars.fabItem.opacity.close,
            transform: `translate3d(0, ${vars.fabItem.transformY.close}%, 0)`,
        },
    });

    useChain(
        state.open ? [menuRef, itemsRef, backgroundRef] : [itemsRef, menuRef, backgroundRef],
        state.open ? [0.2, 0.2, 0.15] : [0.2, 0.25, 0.15],
    );

    return (
        <>
            <NewPostBackground
                onKeyDown={onBgKeyDown}
                bgTransition={bgTransition}
                open={state.open}
                onClick={onClickBackground}
            >
                <div className={classNames(classes.root)}>
                    <animated.ul
                        onKeyDown={onMenuItemKeyDown}
                        style={fabMenu}
                        ref={accessMenuRef}
                        id={menuID}
                        role="menu"
                        aria-labelledby={buttonID}
                        tabIndex={-1}
                        // focusedItem is also the index of an item (see aid below)
                        aria-activedescendant={
                            typeof state.focusedItem === "undefined" ? "" : `${ID}-${state.focusedItem}`
                        }
                    >
                        {trail.map(({ opacity, transform, ...rest }, index) => (
                            <ActionItem
                                aid={`${ID}-${index}`} // accessibility id
                                key={items[index].id}
                                item={items[index]}
                                style={{ opacity, transform }}
                            />
                        ))}
                    </animated.ul>

                    <animated.div className={classes.fabWrap}>
                        <AnimatedButton
                            onKeyDown={onMenuButtonKeyDown}
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
                                    .interpolate((o) => `${o}`),
                                transform: interpolate([d], (d) => `rotate(${d}deg)`),
                            }}
                            buttonType={ButtonTypes.CUSTOM}
                            onClick={() => dispatch({ type: "toggle_open" })}
                            className={classNames(classes.fab)}
                        >
                            <div className={classes.fabFocus} aria-hidden={true} />
                            <NewPostMenuIcon />
                        </AnimatedButton>
                    </animated.div>
                </div>
            </NewPostBackground>
        </>
    );
}
