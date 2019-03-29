/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Optionalize } from "@library/@types/utils";
import { ISearchOptionData } from "@library/features/search/SearchOption";
import { IComboBoxOption } from "@library/features/search/SearchBar";

const PageContext = React.createContext<IWithPagesProps>({ pages: {} });
export default PageContext;

export interface IPageProvider {
    autocomplete(query: string): Promise<Array<IComboBoxOption<ISearchOptionData>>>;
    makeSearchUrl(query: string): string;
}

interface IPageLoader {
    preload(): void;
    url(data?: undefined): string;
}

export interface IWithPagesProps {
    pages: {
        search?: IPageLoader;
        drafts?: IPageLoader;
    };
}

/**
 * HOC to inject pages context
 *
 * @param WrappedComponent - The component to wrap
 */
export function withPages<T extends IWithPagesProps = IWithPagesProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithDevice extends React.Component<Optionalize<T, IWithPagesProps>> {
        public static displayName = `withPages(${displayName})`;
        public render() {
            return (
                <PageContext.Consumer>
                    {context => {
                        // https://github.com/Microsoft/TypeScript/issues/28938
                        return <WrappedComponent {...context} {...this.props as T} />;
                    }}
                </PageContext.Consumer>
            );
        }
    }
    return ComponentWithDevice;
}
