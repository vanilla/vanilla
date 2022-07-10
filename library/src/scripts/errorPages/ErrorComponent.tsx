/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Container from "@library/layout/components/Container";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import React from "react";
import {
    CoreErrorMessages,
    getErrorCode,
    messageFromErrorCode,
    IErrorMessageProps,
} from "@library/errorPages/CoreErrorMessages";
import PanelWidgetVerticalPadding from "@library/layout/components/PanelWidgetVerticalPadding";
import PanelWidget from "@library/layout/components/PanelWidget";

interface IProps extends IErrorMessageProps {}

export function ErrorPage(props: IProps) {
    const code = getErrorCode(props);
    const message = messageFromErrorCode(code);
    const classes = {
        inheritHeight: inheritHeightClass(),
    };

    return (
        <DocumentTitle title={message}>
            <Container className={classes.inheritHeight} tag={"main"}>
                <PanelWidgetVerticalPadding className={classes.inheritHeight}>
                    <PanelWidget className={classes.inheritHeight}>
                        <CoreErrorMessages {...props} className={classes.inheritHeight} />
                    </PanelWidget>
                </PanelWidgetVerticalPadding>
            </Container>
        </DocumentTitle>
    );
}
