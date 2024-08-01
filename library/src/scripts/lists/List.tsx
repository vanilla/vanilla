/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { ElementType } from "react";
import { PageBox } from "@library/layout/PageBox";
import { PageBoxContextProvider } from "@library/layout/PageBox.context";
import { IListOptions, listVariables } from "@library/lists/List.variables";
import { ListItemContext } from "@library/lists/ListItem";
import { listItemClasses } from "@library/lists/ListItem.styles";
import { cx } from "@library/styles/styleShim";
import { Variables } from "@library/styles/Variables";
export interface IListProps {
    options?: Partial<IListOptions>;
    children?: React.ReactNode;
    className?: string;
    as?: ElementType;
}

export function List(props: IListProps) {
    const options = listVariables(props.options).options;
    const classes = listItemClasses();
    return (
        <PageBox
            as={props.as ?? "ul"}
            options={options.box}
            className={cx(props.className, {
                [classes.listInTab]: Variables.boxHasOutline(options.itemBox),
            })}
        >
            <PageBoxContextProvider options={options.itemBox}>
                <ListItemContext.Provider value={{ layout: options.itemLayout }}>
                    {props.children}
                </ListItemContext.Provider>
            </PageBoxContextProvider>
        </PageBox>
    );
}
