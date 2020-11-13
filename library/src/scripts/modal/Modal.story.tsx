/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import Modal from "@library/modal/Modal";
import { STORY_IPSUM_LONG, STORY_IPSUM_SHORT, StoryTextContent } from "@library/storybook/storyData";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { StoryContent } from "@library/storybook/StoryContent";
import FrameBody from "@library/layout/frame/FrameBody";
import { Tabs } from "@library/sectioning/Tabs";

export default {
    title: "Layout/Modals",
};

export function DismissableModal() {
    const [isVisible, setIsVisible] = useState(true);
    const close = () => setIsVisible(false);

    return (
        <>
            <StoryContent>
                <Button onClick={() => setIsVisible(true)} baseClass={ButtonTypes.PRIMARY}>
                    Open Modal
                </Button>
            </StoryContent>
            <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                <Frame
                    header={<FrameHeader title="Dismissable" closeFrame={close} />}
                    body={
                        <FrameBody selfPadded={true}>
                            <StoryTextContent />
                        </FrameBody>
                    }
                />
            </Modal>
        </>
    );
}

export function VisibleModal() {
    return (
        <Modal isVisible={true} size={ModalSizes.MEDIUM}>
            <StoryTextContent />
        </Modal>
    );
}

export function InvisibleModal() {
    return (
        <Modal isVisible={false} size={ModalSizes.MEDIUM}>
            <StoryTextContent />
        </Modal>
    );
}

export function StackedModals() {
    return (
        <>
            <Modal isVisible={true} size={ModalSizes.FULL_SCREEN}>
                <StoryTextContent />
            </Modal>
            <Modal isVisible={true} size={ModalSizes.XL}>
                <StoryTextContent />
            </Modal>
            <Modal isVisible={true} size={ModalSizes.LARGE}>
                <StoryTextContent />
            </Modal>
            <Modal isVisible={true} size={ModalSizes.MEDIUM}>
                <StoryTextContent />
            </Modal>
            <Modal isVisible={true} size={ModalSizes.SMALL}>
                <StoryTextContent />
            </Modal>
        </>
    );
}

export function ModalWithTabs() {
    return (
        <Modal size={ModalSizes.XL} isVisible={true}>
            <Frame
                header={<FrameHeader title="Dismissable" closeFrame={() => {}} />}
                body={
                    <FrameBody selfPadded={true}>
                        <Tabs
                            data={[
                                {
                                    label: "Tab 1",
                                    panelData: "",
                                    contents: <StoryTextContent firstTitle={"Hello Tab 1"} />,
                                },
                                {
                                    label: "Tab 2",
                                    panelData: "",
                                    contents: <StoryTextContent firstTitle={"Hello Tab 2"} />,
                                },
                                {
                                    label: "Tab 3",
                                    panelData: "",
                                    contents: <StoryTextContent firstTitle={"Hello Tab 3"} />,
                                },
                            ]}
                        />
                    </FrameBody>
                }
            />
        </Modal>
    );
}
