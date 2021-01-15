import React, { useContext } from "react";
import Quill from "quill/core";

export interface IEmbedContext {
    inEditor?: boolean;
    descriptionID?: string;
    onRenderComplete?: () => void;
    syncBackEmbedValue?: (values: object) => void;
    quill?: Quill | null;
    isSelected?: boolean;
    selectSelf?: () => void;
    deleteSelf?: () => void;
}
export const EmbedContext = React.createContext<IEmbedContext>({});
export function useEmbedContext() {
    return useContext(EmbedContext);
}
