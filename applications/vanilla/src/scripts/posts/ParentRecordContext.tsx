/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICreatePostForm } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";
import { logDebug } from "@vanilla/utils";
import { createContext, useContext } from "react";

interface IParentRecordContext<T> {
    parentRecordType?: string;
    parentRecordID?: string;
    recordType?: string;
    recordID?: string;
    record?: ICreatePostForm;
    getExternalData: (key: string, args: unknown[]) => T | undefined;
}

export const ParentRecordContext = createContext<IParentRecordContext<unknown>>({
    parentRecordType: "none",
    getExternalData: () => undefined,
});

export function useParentRecordContext<T>() {
    return useContext(ParentRecordContext) as IParentRecordContext<T>;
}

interface IProps extends React.PropsWithChildren<Omit<IParentRecordContext<unknown>, "getExternalData">> {}

export function ParentRecordContextProvider<T>(props: IProps) {
    const { children, ...rest } = props;

    const getExternalData = (key: string, args: unknown[]): T | undefined => {
        const getter = ParentRecordContextProvider.optionalRecordData.find((x) => x.key === key) as
            | OptionalRecordData<T>
            | undefined;
        if (!getter) {
            logDebug(`ParentRecordContextProvider: No getter found for key ${key}`);
        }
        return getter?.fn(...args);
    };

    return <ParentRecordContext.Provider value={{ ...rest, getExternalData }}>{children}</ParentRecordContext.Provider>;
}

interface OptionalRecordData<T> {
    key: string;
    fn: (...args: unknown[]) => T;
}

ParentRecordContextProvider.optionalRecordData = [] as Array<OptionalRecordData<unknown>>;

ParentRecordContextProvider.registerOptionalRecordData = <T,>(getter: OptionalRecordData<T>) => {
    ParentRecordContextProvider.optionalRecordData.push(getter);
};
