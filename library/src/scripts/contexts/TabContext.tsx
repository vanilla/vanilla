/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Optionalize } from "@library/@types/utils";

export interface ITabContext {
    setData: (data: any) => void;
    groupID: string;
    activeTab: string | number;
    childClass: string;
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
    class ComponentWithTabs extends React.Component<Optionalize<T, ITabContext>> {
        public static displayName = `withTabs(${displayName})`;
        public render() {
            return (
                <TabContext.Consumer>
                    {context => {
                        // https://github.com/Microsoft/TypeScript/issues/28938
                        return <WrappedComponent {...context} {...(this.props as T)} />;
                    }}
                </TabContext.Consumer>
            );
        }
    }
    return ComponentWithTabs;
}
