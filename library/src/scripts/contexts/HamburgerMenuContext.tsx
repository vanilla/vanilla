/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationTreeItem } from "@library/@types/api/core";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { getActiveRecord } from "@library/flyouts/Hamburger";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import { RecordID, stableObjectHash } from "@vanilla/utils";
import omit from "lodash/omit";
import React, { createContext, ReactNode, useContext, useState } from "react";

/** Supported Component Types */
export enum DynamicComponentTypes {
    "tree",
    "node",
}

export type IHamburgerDynamicComponent = {
    /** Unique ID for the component */
    id: number;
    /** Title of thr component*/
    title?: string;
} & (
    | {
          type: DynamicComponentTypes.tree;
          tree: INavigationTreeItem[];
          node?: never;
      }
    | {
          type: DynamicComponentTypes.node;
          node: ReactNode;
          tree?: never;
      }
);

/** Utility type to make a specific field optional */
export type PartialBy<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>;

interface IComponentListItem {
    id: IHamburgerDynamicComponent["id"];
    title: IHamburgerDynamicComponent["title"];
    component: ReactNode;
}

interface IHamburgerMenuContext {
    /** The list of components that should be rendered */
    dynamicComponents: Record<string, IComponentListItem> | null;
    addComponent: (componentConfig: PartialBy<IHamburgerDynamicComponent, "id">) => number;
    removeComponentByID: (id: RecordID) => void;
    isCompact: boolean;
}

export const HamburgerMenuContext = createContext<IHamburgerMenuContext>({
    dynamicComponents: {},
    addComponent: (componentConfig) => -1,
    removeComponentByID: (id) => null,
    isCompact: false,
});

export function HamburgerMenuContextProvider(props: { children: ReactNode }) {
    const { children } = props;

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    /** This state maintains the various components that should be rendered in the hamburger menu */
    const [dynamicComponents, _setDynamicComponents] = useState<Record<string, IComponentListItem> | null>(null);

    const setDynamicComponents = (component: IComponentListItem) => {
        _setDynamicComponents((prevState) => {
            if (prevState) {
                return {
                    ...prevState,
                    [component.id]: component,
                };
            }
            return { [component.id]: component };
        });
    };

    /** Create a new item entry from a component config */
    const createNewEntry = (componentConfig: PartialBy<IHamburgerDynamicComponent, "id">): IComponentListItem => {
        const { id, title, tree, node, type } = componentConfig;
        // Generate a new ID
        const newID = stableObjectHash(componentConfig);

        const newEntry: IComponentListItem = {
            id: newID,
            title,
            component:
                type === DynamicComponentTypes.node
                    ? node
                    : tree && (
                          <>
                              <hr className={dropDownClasses().separator} />
                              <DropDownPanelNav navItems={tree} isNestable activeRecord={getActiveRecord(tree)} />
                          </>
                      ),
        };

        return newEntry;
    };

    /** Pass this function a componentConfig to have it update an existing component */
    const updateComponent = (componentConfig: PartialBy<IHamburgerDynamicComponent, "id">): number => {
        if (dynamicComponents?.hasOwnProperty(componentConfig.id ?? "")) {
            const updatedEntry = createNewEntry(componentConfig);
            setDynamicComponents(updatedEntry);

            return updatedEntry.id;
        }
        return -1;
    };

    /** Pass this function a componentConfig to have it render its content in the hamburger menu */
    const addComponent = (componentConfig: PartialBy<IHamburgerDynamicComponent, "id">): number => {
        // Only add new, if the id does not already exist in the store
        if (!!componentConfig.id || !Object.keys(dynamicComponents ?? {}).includes(`${componentConfig.id}`)) {
            // Create new component entry
            const newEntry = createNewEntry(componentConfig);
            // Add it to the store
            setDynamicComponents(newEntry);
            // Pass back the ID
            return newEntry.id;
        }
        // If it already exists, we want to update it instead
        return updateComponent(componentConfig);
    };

    const removeComponentByID = (id: RecordID): void => {
        if (dynamicComponents?.hasOwnProperty(id)) {
            _setDynamicComponents((prevState) => omit(prevState, id));
        }
    };

    return (
        <HamburgerMenuContext.Provider value={{ dynamicComponents, addComponent, removeComponentByID, isCompact }}>
            {children}
        </HamburgerMenuContext.Provider>
    );
}

export function useHamburgerMenuContext() {
    return useContext(HamburgerMenuContext);
}
