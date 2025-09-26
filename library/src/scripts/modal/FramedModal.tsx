/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";

interface IFramedModalProps {
    title: React.ReactNode;
    onClose: () => void;
    children?: React.ReactNode;
    footer?: React.ReactNode;
    size?: ModalSizes;
    onBackClick?: () => void;
    onFormSubmit?: (e: React.SyntheticEvent<HTMLFormElement>) => void;
    padding?: "all" | "vertical" | "horizontal" | "none";
}

export function FramedModal(props: IFramedModalProps) {
    const padding = props.padding ?? "horizontal";
    let content = (
        <Frame
            header={<FrameHeader onBackClick={props.onBackClick} title={props.title} closeFrame={props.onClose} />}
            body={
                <FrameBody
                    selfPadded={padding === "none" || padding === "vertical"}
                    hasVerticalPadding={padding === "all" || padding === "vertical"}
                >
                    {props.children}
                </FrameBody>
            }
            footer={props.footer && <FrameFooter justifyRight={true}>{props.footer}</FrameFooter>}
        />
    );

    if (props.onFormSubmit) {
        content = (
            <form
                role="form"
                onSubmit={(e) => {
                    e.preventDefault();
                    props.onFormSubmit?.(e);
                }}
            >
                {content}
            </form>
        );
    }
    return (
        <Modal isVisible={true} exitHandler={props.onClose} size={props.size ?? ModalSizes.MEDIUM}>
            {content}
        </Modal>
    );
}
