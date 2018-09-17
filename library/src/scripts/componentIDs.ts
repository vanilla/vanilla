/**
 * Utilities and types for handling unique and/or required HTML ids in react components.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import uniqueId from "lodash/uniqueId";

// Optional ID
export interface IOptionalComponentID {
    id?: string | boolean;
}

// Requires ID
export interface IRequiredComponentID {
    id: string;
}

export function uniqueIDFromPrefix(suffix: string) {
    return (suffix + uniqueId()) as string;
}

export function getRequiredID(props: IRequiredComponentID, suffix: string): string {
    if (props.id) {
        return props.id;
    } else {
        return uniqueIDFromPrefix(suffix);
    }
}

export function getOptionalID(props: IOptionalComponentID, suffix?: string): string | null {
    if (props.id) {
        // we want an ID
        if (typeof props.id === "string") {
            // Handled by parent component
            return props.id;
        } else if (typeof props.id === "boolean" && suffix) {
            return uniqueIDFromPrefix(suffix);
        }
        throw new Error("To generate and ID, you must provide a suffix.");
    } else {
        return null;
    }
}
