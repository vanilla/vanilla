/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { createContext, useContext } from "react";

interface ILayoutQueryContext {
    layoutQuery: ILayoutQuery;
}

export const LayoutQueryContext = createContext<ILayoutQueryContext>({
    layoutQuery: {} as ILayoutQuery,
});

export function useLayoutQueryContext() {
    return useContext(LayoutQueryContext);
}

interface IProps extends React.PropsWithChildren<ILayoutQueryContext> {}

/**
 * This context serves as a way for inner widgets and assets to accest the layout query
 * since some layout views are not hydrated on the BE and would need to fetch on the client.
 */
export function LayoutQueryContextProvider(props: IProps) {
    const { children, layoutQuery } = props;

    return <LayoutQueryContext.Provider value={{ layoutQuery }}>{children}</LayoutQueryContext.Provider>;
}
