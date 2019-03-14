/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "../dom/appUtils";
import Button from "../forms/Button";
import { Frame, FrameBody, FrameFooter, FrameHeader, FramePanel } from "../layout/frame";
import SmartAlign from "../utility/SmartAlign";
import ModalSizes from "ModalSizes";
import { getRequiredID } from "../utility/idUtils";
import Modal from "Modal";
import ButtonLoader from "../loaders/ButtonLoader";
import { buttonClasses, ButtonTypes } from "@library/styles/buttonStyles";
import { frameBodyClasses } from "../layout/frame/frameStyles";
import classNames from "classnames";

interface IProps {
    title: string; // required for accessibility
    srOnlyTitle?: boolean;
    className?: string;
    onCancel: () => void;
    onConfirm: () => void;
    children: React.ReactNode;
    isConfirmLoading?: boolean;
    elementToFocusOnExit: HTMLElement;
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

    private cancelRef;
    private id;

    constructor(props) {
        super(props);
        this.id = getRequiredID(props, "confirmModal");
        this.cancelRef = React.createRef();
    }

    public render() {
        const { onCancel, onConfirm, srOnlyTitle, isConfirmLoading, title, children } = this.props;
        const buttons = buttonClasses();
        const classesFrameBody = frameBodyClasses();
        return (
            <Modal
                size={ModalSizes.SMALL}
                elementToFocus={this.cancelRef.current}
                exitHandler={onCancel}
                titleID={this.titleID}
                elementToFocusOnExit={this.props.elementToFocusOnExit}
            >
                <Frame>
                    <FrameHeader
                        titleID={this.titleID}
                        closeFrame={onCancel}
                        srOnlyTitle={srOnlyTitle!}
                        title={title}
                    />
                    <FrameBody>
                        <FramePanel>
                            <SmartAlign className={classNames("frameBody-contents", classesFrameBody.contents)}>
                                {children}
                            </SmartAlign>
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter selfPadded={true}>
                        <Button baseClass={ButtonTypes.COMPACT} buttonRef={this.cancelRef} onClick={onCancel}>
                            {t("Cancel")}
                        </Button>
                        <Button onClick={onConfirm} baseClass={ButtonTypes.COMPACT_PRIMARY} disabled={isConfirmLoading}>
                            {isConfirmLoading ? <ButtonLoader /> : t("Ok")}
                        </Button>
                    </FrameFooter>
                </Frame>
            </Modal>
        );
    }

    public get titleID() {
        return this.id + "-title";
    }

    public componentDidMount() {
        this.forceUpdate();
    }
}
