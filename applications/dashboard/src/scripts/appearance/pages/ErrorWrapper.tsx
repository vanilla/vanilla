/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ErrorIcon } from "@library/icons/common";
import { Container } from "@library/layout/components/Container";
import Message from "@library/messages/Message";
import React from "react";

interface IErrorProps {
    children?: React.ReactElement;
    message: string;
}

export function ErrorWrapper(props: IErrorProps) {
    return (
        <Container>
            <div style={{ width: "100%", height: "100%", padding: "16px" }}>
                <Message type={"error"} icon={<ErrorIcon />} contents={props.children} stringContents={props.message} />
            </div>
        </Container>
    );
}
