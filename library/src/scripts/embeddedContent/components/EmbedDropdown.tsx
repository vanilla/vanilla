/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { createContext, PropsWithChildren, useContext, useEffect, useRef } from "react";
import { EmbedMenuContext } from "@rich-editor/editor/pieces/EmbedMenu";
import { embedDropdownClasses } from "@library/embeddedContent/components/embedDropdownStyles";
import { CheckIcon } from "@library/icons/common";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { useUniqueID } from "@library/utility/idUtils";

interface IEmbedDropdownContext {
    selected?: string;
}

const EmbedDropdownContext = createContext<IEmbedDropdownContext>({ selected: undefined });

interface IEmbedDropdownProps {
    Icon: React.FunctionComponent;
    name: string;
    value: string;
    label: string;
}

export function EmbedDropdown(props: PropsWithChildren<IEmbedDropdownProps>) {
    const { Icon, name, children, value, label } = props;
    const { selected, setSelected } = useContext(EmbedMenuContext);

    const isSelected = name === selected;
    const buttonRef = useRef<HTMLButtonElement>(null);

    const id = useUniqueID("embedDropdown");
    const buttonId = id + "-button";
    const contentId = id + "-content";

    return (
        <>
            <EmbedButton
                id={buttonId}
                aria-aria-haspopup="true"
                aria-controls={contentId}
                aria-expanded={isSelected ? "true" : "false"}
                aria-label={label}
                buttonRef={buttonRef}
                isActive={isSelected}
                onKeyDown={(e) => {
                    const key = e.key;
                    switch (key) {
                        case "Enter":
                        case "":
                        case "ArrowDown": {
                            e.preventDefault();
                            setSelected(name);
                            break;
                        }
                        default:
                            break;
                    }
                }}
                onClick={() => {
                    if (isSelected) setSelected(undefined);
                    else setSelected(name);
                }}
            >
                <Icon />
            </EmbedButton>
            {isSelected && (
                <EmbedDropdownMenuContainer
                    buttonRef={buttonRef}
                    setSelected={setSelected}
                    label={label}
                    id={contentId}
                >
                    <EmbedDropdownContext.Provider value={{ selected: value }}>
                        {children}
                    </EmbedDropdownContext.Provider>
                </EmbedDropdownMenuContainer>
            )}
        </>
    );
}
interface IEmbedDropdownMenuContainerProps {
    setSelected: (name: string | undefined) => void;
    id: string;
    label: string;
    buttonRef: React.RefObject<HTMLButtonElement>;
}

function EmbedDropdownMenuContainer(props: PropsWithChildren<IEmbedDropdownMenuContainerProps>) {
    const { children, setSelected, label, buttonRef, id } = props;
    const classes = embedDropdownClasses();
    const menuRef = useRef<HTMLUListElement | null>(null);

    useEffect(() => {
        if (menuRef && menuRef.current) {
            (menuRef.current.firstChild as HTMLElement).focus();
        }
    }, [menuRef]);

    return (
        <ul
            role="menu"
            aria-label={label}
            id={id}
            tabIndex={-1}
            onKeyDown={(e: React.KeyboardEvent<HTMLElement>) => {
                const key = e.key;
                let target = e.target as HTMLElement & EventTarget;
                const firstSibling = () => target.parentElement?.firstChild as HTMLElement | undefined;
                const lastSibling = () => target.parentElement?.lastChild as HTMLElement | undefined;
                switch (key) {
                    case "ArrowDown": {
                        e.preventDefault();
                        let nextSibling = target.nextSibling as HTMLElement | undefined;
                        if (!nextSibling && target.parentElement) {
                            nextSibling = firstSibling();
                        }
                        (nextSibling as HTMLElement).focus();
                        break;
                    }
                    case "ArrowUp": {
                        e.preventDefault();
                        let previousSibling = target.previousSibling as HTMLElement | undefined;
                        if (!previousSibling && target.parentElement) {
                            previousSibling = lastSibling();
                        }
                        (previousSibling as HTMLElement).focus();
                        break;
                    }
                    case "Home": {
                        e.preventDefault();
                        (firstSibling() as HTMLElement)?.focus();
                        break;
                    }
                    case "End": {
                        e.preventDefault();
                        (lastSibling() as HTMLElement)?.focus();
                        break;
                    }
                    case "Escape":
                        e.preventDefault();
                        setSelected(undefined);
                        buttonRef.current?.focus();
                        break;
                    default:
                        break;
                }
            }}
            ref={menuRef}
            className={classes.container}
        >
            {children}
        </ul>
    );
}

interface IEmbedDropdownOptionProps {
    Icon: React.FunctionComponent;
    value: string;
    label: string;
    onClick(e): void;
}

EmbedDropdown.Option = function EmbedDropdownOption(props: IEmbedDropdownOptionProps) {
    const { Icon, value, label, onClick } = props;
    const { selected } = useContext(EmbedDropdownContext);
    const isSelected = selected === value;
    const classes = embedDropdownClasses();

    return (
        <li
            role="menuitemradio"
            aria-label={label}
            aria-checked={isSelected ? "true" : "false"}
            tabIndex={-1}
            className={classes.option}
            onClick={onClick}
            onKeyDown={(e: React.KeyboardEvent<HTMLElement>) => {
                const key = e.key;
                switch (key) {
                    case "Enter":
                    case " ":
                        e.preventDefault();
                        onClick((e) => undefined);
                        break;
                    default:
                        break;
                }
            }}
        >
            <Icon />
            <div className={classes.optionLabel}>{label}</div>
            {isSelected && (
                <div className={classes.check}>
                    <CheckIcon />
                </div>
            )}
        </li>
    );
};
