/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyValue } from "@library/vanilla-editor/typescript";
import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor";
import { logError } from "@vanilla/utils";
import isEqual from "lodash/isEqual";
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

const EDITOR_VALUE_AFTER_RESET = [{ type: "p", children: [{ text: "" }] }];

export function useSynchronizationContext() {
    return useContext(SynchronizationContext);
}

interface SynchronizationProviderProps {
    /** Text area which should be synchronized */
    textArea?: HTMLInputElement | HTMLTextAreaElement;
    /** The format of the text area */
    initialFormat?: string;
    /** Is the initial text area content HTML */
    needsHtmlConversion?: boolean;
}

export function SynchronizationProvider(props: PropsWithChildren<SynchronizationProviderProps>) {
    const { textArea, initialFormat, needsHtmlConversion } = props;

    const [showConversionNotice, setConversionNotice] = useState(false);

    const initialValue = useMemo(() => {
        // If the post is already rich2, we can pass it to the editor as JSON
        if (!needsHtmlConversion && initialFormat?.match(/rich2/i) && textArea?.value) {
            return safelyParseJSON(textArea.value);
        }
        if (needsHtmlConversion) {
            setConversionNotice(true);
            return deserializeHtml(textArea?.value ?? "");
        }
        return undefined;
    }, []);

    const syncTextArea = debounce((value: MyValue) => {
        // Wrapped in try to guard against malformed JSON
        const serializedValue = safelySerializeJSON(value);
        if (textArea && serializedValue && !isEqual(value, EDITOR_VALUE_AFTER_RESET)) {
            textArea.value = serializedValue;
            textArea.dispatchEvent(new Event("input", { bubbles: true, cancelable: false }));
        }
    }, 1000 / 60);

    return (
        <SynchronizationContext.Provider value={{ syncTextArea, initialValue, showConversionNotice }}>
            {props.children}
        </SynchronizationContext.Provider>
    );
}

/**
 * Wrapped JSON.stringify method so that is
 * does not explode if JSON is malformed
 */
function safelySerializeJSON(json: unknown) {
    try {
        return JSON.stringify(json);
    } catch (error) {
        logError(error);
    }
}

/**
 * Wrapped JSON.parse method so that is
 * does not explode if JSON is malformed
 */
function safelyParseJSON(string: string) {
    try {
        return JSON.parse(string);
    } catch (error) {
        logError(error);
    }
}
