/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Omit } from "../@types/utils";

export interface ITabProps {
    setData: (data: any) => void;
    groupID: string;
    defaultTab: any;
    childClass: string;
}

const TabContext = React.createContext<ITabProps>({} as any);
export default TabContext;

/**
 * Inject RadioButtonAsTabs data to RadioButtonTab through props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withTabs<T extends ITabProps = ITabProps>(WrappedComponent: React.ComponentClass<ITabProps>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithTabs extends React.Component<Omit<T, keyof ITabProps>> {
        public static displayName = `withTabs(${displayName})`;
        public render() {
            return (
                <TabContext.Consumer>
                    {context => {
                        return <WrappedComponent {...context} {...this.props} />;
                    }}
                </TabContext.Consumer>
            );
        }
    }
    return ComponentWithTabs;
}
