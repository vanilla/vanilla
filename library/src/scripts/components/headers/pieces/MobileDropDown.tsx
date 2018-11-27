/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import classNames from "classnames";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import CloseButton from "@library/components/CloseButton";
import { downTriangle, user } from "@library/components/icons/header";
import { Panel } from "@library/components/layouts/PanelLayout";

export interface IProps {
    className?: string;
    buttonClass?: string;
    title: string;
    mobileDropDownContent: React.ReactNode;
}

interface IState {
    open: boolean;
}

/**
 * Implements Mobile Drop Down, (like a hamburger menu with the page title as the toggle)
 */
export default class MobileDropDown extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state = {
        open: false,
    };

    public render() {
        return this.props.mobileDropDownContent && this.props.mobileDropDownContent.current ? (
            <div className={classNames("headerDropDown", this.props.className)}>
                <Button
                    title={this.props.title}
                    className={classNames("headerDropDown-toggleButton", this.props.buttonClass)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonBaseClass.CUSTOM}
                >
                    <span className="headerDropDown-title">
                        {this.props.title} {downTriangle()}
                    </span>
                </Button>
                {this.state.open && (
                    <Modal
                        size={ModalSizes.MODAL_AS_DROP_DOWN}
                        label={t("Page Menu")}
                        elementToFocusOnExit={this.buttonRef.current!}
                        className="headerDropDown-modal"
                        exitHandler={this.close}
                    >
                        <Panel className="headerDropDown-panel">
                            <CloseButton onClick={this.close} className="headerDropDown-closeModal" />
                            {this.props.mobileDropDownContent}
                        </Panel>
                    </Modal>
                )}
            </div>
        ) : null;
    }

    private open = () => {
        this.setState({
            open: true,
        });
    };
    private close = () => {
        this.setState({
            open: false,
        });
    };
}
