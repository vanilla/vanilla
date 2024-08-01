import React, { useContext } from "react";

export interface IEmbedContext {
    inEditor?: boolean;
    isNewEditor?: boolean;
    descriptionID?: string;
    onRenderComplete?: () => void;
    syncBackEmbedValue?: (values: object) => void;
    isSelected?: boolean;
    selectSelf?: () => void;
    deleteSelf?: () => void;
}
export const EmbedContext = React.createContext<IEmbedContext>({});
export function useEmbedContext() {
    return useContext(EmbedContext);
}
