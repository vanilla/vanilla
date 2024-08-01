/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { logError } from "@vanilla/utils";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";
import { ErrorPage } from "@library/errorPages/ErrorComponent";

interface IProps {
    children?: React.ReactNode;
    errorComponent?: React.ComponentType<any>;
}

interface IState {
    error: Error | null;
}

export class ErrorPageBoundary extends React.Component<IProps, IState> {
    public state: IState = {
        error: null,
    };

    static getDerivedStateFromError(error: Error) {
        // Update state so the next render will show the fallback UI.
        return { error };
    }

    componentDidCatch(error: Error, errorInfo: any) {
        // You can also log the error to an error reporting service
        logError(error, errorInfo);
    }

    render() {
        const { error } = this.state;
        if (error) {
            // You can render any custom fallback UI
            return <ErrorPage error={error} />;
        }

        return this.props.children;
    }
}
