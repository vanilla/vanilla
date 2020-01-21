/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { PanelWidget, PanelWidgetVerticalPadding } from "@library/layout/PanelLayout";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import React from "react";
import ErrorMessagePage, {getErrorCode, messageFromErrorCode, IErrorMessageProps} from "@library/errorPages/ErrorMessagePage";
interface IProps  extends IErrorMessageProps {
    code?: IErrorMessageProps;
}

export function ErrorPage(props: IProps){
        console.log('ErrorPage');
        console.log('code', props);
        const code = getErrorCode(props.code);
        const message = messageFromErrorCode(code);
        const classes = {
            inheritHeight: inheritHeightClass(),
        };

        return (
            <DocumentTitle title={message}>
                <TitleBar />
                <Container className={classes.inheritHeight}>
                    <PanelWidgetVerticalPadding className={classes.inheritHeight}>
                        <PanelWidget className={classes.inheritHeight}>
                            <ErrorMessagePage {...props} className={classes.inheritHeight} />
                        </PanelWidget>
                    </PanelWidgetVerticalPadding>
                </Container>
            </DocumentTitle>
        );
}




