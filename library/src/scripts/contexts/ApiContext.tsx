/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Omit } from "@library/@types/utils";
import { AxiosInstance } from "axios";
import { ISearchOptionData } from "@library/components/search/SearchOption";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
const ApiContext = React.createContext<IApiProps>({} as any);
export default ApiContext;

export interface ISearchOptionProvider {
    autocomplete(query: string): Promise<Array<IComboBoxOption<ISearchOptionData>>>;
    makeSearchUrl(query: string): string;
}

export interface IApiProps {
    api: AxiosInstance;
    searchOptionProvider: ISearchOptionProvider;
}

/**
 * HOC to inject API context
 *
 * @param WrappedComponent - The component to wrap
 */
export function withApi<T extends IApiProps = IApiProps>(WrappedComponent: React.ComponentClass<IApiProps>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithDevice extends React.Component<Omit<T, keyof IApiProps>> {
        public static displayName = `withApi(${displayName})`;
        public render() {
            return (
                <ApiContext.Consumer>
                    {context => {
                        return <WrappedComponent {...context} {...this.props} />;
                    }}
                </ApiContext.Consumer>
            );
        }
    }
    return ComponentWithDevice;
}
