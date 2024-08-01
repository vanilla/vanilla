import React, { PropsWithChildren, useContext } from "react";

import { ISiteSection, getMeta } from "./appUtils";

interface ISiteSectionContextValue {
    siteSection: ISiteSection | undefined;
}

export const SiteSectionContext = React.createContext<ISiteSectionContextValue>({
    siteSection: undefined,
});

export function SiteSectionContextProvider(props: PropsWithChildren<{}>) {
    const siteSection: ISiteSection | undefined = getMeta("siteSection", undefined);

    return (
        <SiteSectionContext.Provider
            value={{
                siteSection,
            }}
        >
            {props.children}
        </SiteSectionContext.Provider>
    );
}

export function useSiteSectionContext() {
    return useContext(SiteSectionContext);
}
