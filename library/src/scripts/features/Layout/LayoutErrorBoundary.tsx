/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ErrorIcon } from "@library/icons/common";
import Message from "@library/messages/Message";
import { logError, RecordID } from "@vanilla/utils";
import React, { Component } from "react";

export interface ILayoutErrorBoundaryProps {
    key?: RecordID;
}

export interface ILayoutErrorBoundaryState {
    hasError: boolean;
}

export default class LayoutErrorBoundary extends Component<ILayoutErrorBoundaryProps, ILayoutErrorBoundaryState> {
    constructor(props: ILayoutErrorBoundaryProps) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error: Error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        logError(error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <>
                    <div style={{ width: "100%", height: "100%", padding: "16px" }}>
                        <Message
                            type={"error"}
                            icon={<ErrorIcon />}
                            stringContents={"There was a problem loading this content"}
                        />
                    </div>
                </>
            );
        }

        return this.props.children;
    }
}
