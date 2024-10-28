/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useQuery } from "@tanstack/react-query";
import { PropsWithChildren, createContext, useContext } from "react";

interface IExternalDataQueryFunction {
    name: string;
    queryFunction: () => Promise<any>;
}
interface IExternalDataContextValue {
    externalDataQueryFunctions: IExternalDataQueryFunction[];
}

const ExternalDataContext = createContext<IExternalDataContextValue>({
    externalDataQueryFunctions: [],
});

export function useExternalDataContext() {
    return useContext(ExternalDataContext);
}

export function useExternalDataQueryFunction(name: string) {
    const context = useExternalDataContext();
    const match = context.externalDataQueryFunctions.find((f) => f.name === name);
    const queryFunction = match?.queryFunction;

    return useQuery({
        queryKey: [name],
        queryFn: async function () {
            return queryFunction ? await queryFunction() : undefined;
        },
    });
}

export function ExternalDataContextProvider({ children }: PropsWithChildren<{}>) {
    return (
        <ExternalDataContext.Provider
            value={{ externalDataQueryFunctions: ExternalDataContextProvider.queryFunctions }}
        >
            {children}
        </ExternalDataContext.Provider>
    );
}

ExternalDataContextProvider.queryFunctions = [] as IExternalDataQueryFunction[];

ExternalDataContextProvider.registerExternalDataQueryFunction = function (
    name: string,
    queryFunction: () => Promise<any>,
) {
    ExternalDataContextProvider.queryFunctions.push({
        name,
        queryFunction,
    });
};
