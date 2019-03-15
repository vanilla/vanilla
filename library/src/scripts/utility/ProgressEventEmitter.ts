/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export type ProgressEventHandler = (event: ProgressEvent) => void;

export default class ProgressEventEmitter {
    private listeners: ProgressEventHandler[] = [];

    public emit = (event: ProgressEvent) => {
        this.listeners.forEach(listener => {
            listener(event);
        });
    };

    public addEventListener = (listener: ProgressEventHandler) => {
        this.listeners.push(listener);
    };

    public removeEventListener = (listener: ProgressEventHandler) => {
        this.listeners = this.listeners.filter(registeredListener => listener !== registeredListener);
    };
}
