/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CollapsableContent } from "@library/content/CollapsableContent";
import { ErrorIcon } from "@library/icons/common";
import { Container } from "@library/layout/components/Container";
import Message from "@library/messages/Message";
import { messagesVariables } from "@library/messages/messageStyles";
import { t } from "@vanilla/i18n";
import { debug, logError, RecordID } from "@vanilla/utils";
import React, { Component } from "react";

export interface ILayoutErrorBoundaryProps {
    key?: RecordID;
    componentName?: string;
}

export interface ILayoutErrorBoundaryState {
    error: Error | null;
    trace?: string;
}

interface ILayoutErrorProps {
    componentName: string;
    message?: string;
    trace?: string;
}

export function LayoutError(props: ILayoutErrorProps) {
    const error = `There was a problem loading "${props.componentName ?? t("Invalid component name")}"`;
    return (
        <Container>
            <div style={{ width: "100%", height: "100%", padding: "16px" }}>
                <Message
                    type={"error"}
                    icon={<ErrorIcon />}
                    title={props.message ? error : undefined}
                    stringContents={props.message ?? error}
                    contents={
                        props.message &&
                        debug() && (
                            <>
                                {props.message}
                                <CollapsableContent bgColor={messagesVariables().colors.error.bg} maxHeight={100}>
                                    <br></br>
                                    {props.trace}
                                </CollapsableContent>
                            </>
                        )
                    }
                />
            </div>
        </Container>
    );
}

export default class LayoutErrorBoundary extends Component<ILayoutErrorBoundaryProps, ILayoutErrorBoundaryState> {
    constructor(props: ILayoutErrorBoundaryProps) {
        super(props);
        this.state = { error: null };
    }

    static getDerivedStateFromError(error: Error) {
        return { error: error };
    }

    componentDidCatch(error, errorInfo) {
        logError({ error, errorInfo });
        if (errorInfo?.componentStack) {
            this.setState({ ...this.state, trace: errorInfo.componentStack });
        }
    }

    render() {
        if (this.state.error !== null) {
            return (
                <LayoutError
                    componentName={this.props.componentName ?? "Unknown Component"}
                    message={this.state.error.message}
                    trace={this.state.trace ?? this.state.error.stack}
                />
            );
        }

        return this.props.children;
    }
}
