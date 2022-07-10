/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Optionalize } from "@library/@types/utils";
import RadioGroupContext, { IRadioGroupProps } from "@library/forms/radioAsButtons/RadioGroupContext";
import { RecordID } from "@vanilla/utils";

export interface ITabContext {
    setData: (data: any) => void;
    groupID: string;
    childClass: string;
    activeItem?: RecordID;
}

const TabContext = React.createContext<ITabContext>({} as any);
export default TabContext;

/**
 * Inject RadioButtonAsTabs data to RadioButtonTab through props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withTabs<T extends ITabContext = ITabContext>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithTabs = (props: Optionalize<T, ITabContext>) => {
        const { activeItem = 0 } = props;
        return (
            <TabContext.Consumer>
                {(context) => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent {...context} {...(props as T)} />;
                }}
            </TabContext.Consumer>
        );
    };
    ComponentWithTabs.displayName = `withTabs(${displayName})`;
    return ComponentWithTabs;
}
