/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Toast } from "@library/features/toaster/Toast";
import { toastManagerClasses } from "@library/features/toaster/ToastContext.styles";
import { uuidv4 } from "@vanilla/utils";
import React, { ReactNode, useContext, useEffect, useState } from "react";

interface IToast {
    /** If the toast should dismiss itself */
    autoDismiss?: boolean;
    /** If the toast should persist at the bottom of the stack */
    persistent?: boolean;
    /** If a toast should be dismissible by the user*/
    dismissible?: boolean;
    /** React body of the toast */
    body: ReactNode;
    /** Apply custom styling to the toast */
    className?: string;
}

interface IToasterContext {
    /** List of toast currently being displayed */
    toasts: IToast[] | null;
    /** Add a new toast to the top of the stack */
    addToast(toast: IToast): string;
    /** Update a specific toast */
    updateToast(toastID: string, toast: IToast): void;
    /** Remove a specific toast */
    removeToast(toastID: string): void;
}

interface IToastState extends IToast {
    /** Identifier for the toast, used for update and remove  */
    toastID?: string;
    /** Animate the toast in or out  */
    visibility?: boolean;
}

/**
 * Context for the global toast notifications
 */
export const ToastContext = React.createContext<IToasterContext>({
    toasts: null,
    addToast: () => "",
    updateToast: () => {},
    removeToast: () => {},
});

/**
 * Hook to used to post to global toast notifications
 */
export function useToast() {
    return useContext(ToastContext);
}

/**
 * Toast notifications logic
 */
export function ToastProvider(props: { children: ReactNode }) {
    const { children } = props;
    const [toasts, setToast] = useState<IToastState[] | null>(null);

    const addToast = (toast: IToastState) => {
        const toastID = uuidv4();
        const newToast: IToastState = { ...toast, toastID, visibility: true };
        setToast((prevState) => (prevState ? [...prevState, newToast] : [newToast]));
        return toastID;
    };

    // Expose new toasts so regular js and legacy views can use them
    window.__LEGACY_ADD_TOAST__ = addToast;

    const updateToast = (toastID: string, updatedToast: IToast) => {
        if (toasts && updatedToast) {
            setToast((prevState) =>
                prevState
                    ? prevState.map((prevToast) => {
                          if (prevToast.toastID === toastID) {
                              return {
                                  ...prevToast,
                                  ...updatedToast,
                                  toastID: prevToast.toastID,
                              };
                          }
                          return prevToast;
                      })
                    : prevState,
            );
        }
    };

    const removeToast = (toastID: string) => {
        if (toasts) {
            // First set the visibility to false (to allow the exit animation)
            setToast((prevState) => {
                if (prevState) {
                    return prevState.map((prevToast, index) => {
                        if (prevToast.toastID === toastID) {
                            // prevToast does not hold maintain the content of the body field,
                            // so I am spreading it directly from the state
                            return { ...toasts[index], visibility: false };
                        }
                        return prevToast;
                    });
                }
                return prevState;
            });
            // Then remove the item altogether
            setTimeout(() => {
                setToast((prevState) =>
                    prevState ? prevState.filter((prevToast) => prevToast.toastID !== toastID) : prevState,
                );
            }, 2000);
        }
    };

    return (
        <ToastContext.Provider
            value={{
                toasts,
                addToast,
                updateToast,
                removeToast,
            }}
        >
            <ToastManager />
            {children}
        </ToastContext.Provider>
    );
}

/**
 * Renders all notifications
 */
function ToastManager() {
    const classes = toastManagerClasses();
    const { toasts } = useContext(ToastContext);

    return (
        <>
            {toasts && toasts.length > 0 && (
                <section className={classes.area}>
                    <>
                        {/* Render persistent toasts first */}
                        {toasts
                            .filter((toast) => toast.persistent)
                            .map((toast: IToastState) => {
                                return (
                                    <Toast
                                        key={toast.toastID}
                                        visibility={toast.visibility ?? true}
                                        autoCloseDuration={toast.autoDismiss ? 3000 : undefined}
                                        dismissible={toast.dismissible}
                                        className={toast.className}
                                    >
                                        {toast.body}
                                    </Toast>
                                );
                            })}
                        {/* Render non-persistent toasts */}
                        {toasts
                            .filter((toast) => !toast.persistent)
                            .map((toast: IToastState) => {
                                return (
                                    <Toast
                                        key={toast.toastID}
                                        visibility={toast.visibility ?? true}
                                        autoCloseDuration={toast.autoDismiss ? 3000 : undefined}
                                        dismissible={toast.dismissible}
                                        className={toast.className}
                                    >
                                        {toast.body}
                                    </Toast>
                                );
                            })}
                    </>
                </section>
            )}
        </>
    );
}
