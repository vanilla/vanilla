/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PageBox } from "@library/layout/PageBox";
import { PageBoxContextProvider } from "@library/layout/PageBox.context";
import { IListOptions, listVariables } from "@library/lists/List.variables";
import { ListItem, ListItemContext } from "@library/lists/ListItem";
import { IListItemOptions } from "@library/lists/ListItem.variables";
import { IBoxOptions, IPartialBoxOptions } from "@library/styles/cssUtilsTypes";
import React from "react";

export interface IListProps {
    options?: Partial<IListOptions>;
    children?: React.ReactNode;
}

export function List(props: IListProps) {
    const options = listVariables(props.options).options;
    return (
        <PageBox options={options.box}>
            <PageBoxContextProvider options={options.itemBox}>
                <ListItemContext.Provider value={{ layout: options.itemLayout }}>
                    {props.children}
                </ListItemContext.Provider>
            </PageBoxContextProvider>
        </PageBox>
    );
}
