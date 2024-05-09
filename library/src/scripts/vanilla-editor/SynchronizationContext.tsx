/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { safelyParseJSON, safelySerializeJSON } from "@library/utility/appUtils";
import { MyValue } from "@library/vanilla-editor/typescript";
import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor.loadable";
import debounce from "lodash/debounce";
import React, { PropsWithChildren, useContext, useMemo, useState } from "react";

interface ISynchronizationContext {
    /** Update the hidden text area with Rich2 formatted data */
    syncTextArea(value: MyValue): void;
    /** JSON structure describing the document */
    initialValue?: MyValue;
    /** If a conversion notice should be displayed */
    showConversionNotice: boolean;
}

export const SynchronizationContext = React.createContext<ISynchronizationContext>({
    syncTextArea: () => null,
    showConversionNotice: false,
});

export function useSynchronizationContext() {
    return useContext(SynchronizationContext);
}

interface SynchronizationProviderProps {
    /** Text area which should be synchronized */
    textArea?: HTMLInputElement | HTMLTextAreaElement;
    initialValue?: string;

    /** The format of the text area */
    initialFormat?: string;
    /** Is the initial text area content HTML */
    needsHtmlConversion?: boolean;
}

export function SynchronizationProvider(props: PropsWithChildren<SynchronizationProviderProps>) {
    const { textArea, initialFormat, needsHtmlConversion } = props;

    const initialValue = useMemo(() => {
        // If the post is already rich2, we can pass it to the editor as JSON
        const initialString = props.initialValue ?? textArea?.value;
        if (!needsHtmlConversion && initialFormat?.match(/rich2/i) && initialString) {
            return safelyParseJSON(initialString ?? []);
        }
        if (needsHtmlConversion) {
            const emptyHtml = "<p></p>";
            return deserializeHtml(!!initialString && initialString !== "" ? initialString : emptyHtml ?? emptyHtml);
        }
        return undefined;
    }, []);

    const syncTextArea = debounce((value: MyValue) => {
        // Wrapped in try to guard against malformed JSON
        const serializedValue = safelySerializeJSON(value);
        if (textArea && serializedValue) {
            textArea.value = serializedValue;
            textArea.dispatchEvent(new Event("input", { bubbles: true, cancelable: false }));
        }
    }, 1000 / 60);

    return (
        <SynchronizationContext.Provider
            value={{
                syncTextArea,
                initialValue,
                showConversionNotice: needsHtmlConversion ?? false,
            }}
        >
            {props.children}
        </SynchronizationContext.Provider>
    );
}
