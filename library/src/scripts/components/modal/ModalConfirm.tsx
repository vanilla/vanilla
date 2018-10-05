/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import { Frame, FrameBody, FrameFooter, FrameHeader, FramePanel } from "@library/components/frame";
import SmartAlign from "@library/components/SmartAlign";
import { ModalSizes } from "@library/components/modal/ModalSizes";
import { getRequiredID } from "@library/componentIDs";
import Modal from "@library/components/modal/Modal";

interface IProps {
    title: string; // required for accessibility
    srOnlyTitle?: boolean;
    className?: string;
    onCancel: () => void;
    onConfirm: () => void;
    children: React.ReactNode;
}

interface IState {
    id: string;
}

/**
 * Basic confirm dialogue.
 */
export default class ModalConfirm extends React.Component<IProps, IState> {
    public static defaultProps = {
        srOnlyTitle: false,
    };

    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "modalConfirm-"),
        };
    }

    public get cancelID() {
        return this.state.id + "-cancelButton";
    }

    public render() {
        return (
            <Modal size={ModalSizes.SMALL} elementToFocus={this.cancelID} exitHandler={this.props.onCancel}>
                <Frame>
                    <FrameHeader closeFrame={this.props.onCancel} srOnlyTitle={this.props.srOnlyTitle!}>
                        {this.props.title}
                    </FrameHeader>
                    <FrameBody>
                        <FramePanel>
                            <SmartAlign className="frameBody-contents">{this.props.children}</SmartAlign>
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter>
                        <Button id={this.cancelID} onClick={this.props.onCancel}>
                            {t("Cancel")}
                        </Button>
                        <Button onClick={this.props.onConfirm} className="buttonPrimary">
                            {t("Ok")}
                        </Button>
                    </FrameFooter>
                </Frame>
            </Modal>
        );
    }
}
