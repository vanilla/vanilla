/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import { IStoryTileAndTextProps, StoryTileAndText } from "@library/storybook/StoryTileAndText";
import Button from "@library/forms/Button";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import SmartAlign from "@library/layout/SmartAlign";
import classNames from "classnames";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@library/utility/appUtils";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useUniqueID } from "@library/utility/idUtils";
import HTML = Mocha.reporters.HTML;
import set = Reflect.set;

interface IProps extends IStoryTileAndTextProps {
    buttonText: string;
}

/**
 * Separator, for react storybook.
 */
export function StoryModalExample(props: IProps) {
    const [open, setOpen] = useState(false);

    const openButtonRef = useRef<Button>(null);
    const toggleButton = useUniqueID("exampleConfirmModal_toggleButton");
    const titleID = useUniqueID("exampleConfirmModal_title");

    // Similar to componentDidMount and componentDidUpdate:
    useEffect(() => {}, []);

    return (
        <>
            <Button
                id={toggleButton}
                onClick={() => {
                    setOpen(true);
                }}
                ref={openButtonRef}
            >
                Confirm Modal
            </Button>
            {/*{open && (*/}
            {/*<Modal*/}
            {/*    size={ModalSizes.SMALL}*/}
            {/*    elementToFocus={*/}
            {/*        openButtonRef ? ((openButtonRef.current as unknown) as HTMLButtonElement) : undefined*/}
            {/*    }*/}
            {/*    exitHandler={() => {*/}
            {/*        setOpen(false);*/}
            {/*    }}*/}
            {/*    titleID={titleID}*/}
            {/*    elementToFocusOnExit={(openButtonRef.current as unknown) as HTMLButtonElement}*/}
            {/*>*/}
            {/*    <Frame*/}
            {/*        header={*/}
            {/*            <FrameHeader*/}
            {/*                titleID={titleID}*/}
            {/*                closeFrame={() => {*/}
            {/*                    setOpen(false);*/}
            {/*                }}*/}
            {/*                title={t("Example Modal")}*/}
            {/*            />*/}
            {/*        }*/}
            {/*        body={*/}
            {/*            <FrameBody>*/}
            {/*                <SmartAlign className={classNames("frameBody-contents", classesFrameBody.contents)}>*/}
            {/*                    {}*/}
            {/*                </SmartAlign>*/}
            {/*            </FrameBody>*/}
            {/*        }*/}
            {/*        footer={*/}
            {/*            <FrameFooter justifyRight={true}>*/}
            {/*                <Button*/}
            {/*                    className={classFrameFooter.actionButton}*/}
            {/*                    baseClass={ButtonTypes.TEXT}*/}
            {/*                    buttonRef={this.cancelRef}*/}
            {/*                    onClick={onCancel}*/}
            {/*                >*/}
            {/*                    {t("Cancel")}*/}
            {/*                </Button>*/}
            {/*                <Button*/}
            {/*                    className={classFrameFooter.actionButton}*/}
            {/*                    onClick={onConfirm}*/}
            {/*                    baseClass={ButtonTypes.TEXT_PRIMARY}*/}
            {/*                    disabled={isConfirmLoading}*/}
            {/*                >*/}
            {/*                    {isConfirmLoading ? <ButtonLoader /> : this.props.confirmTitle}*/}
            {/*                </Button>*/}
            {/*            </FrameFooter>*/}
            {/*        }*/}
            {/*    />*/}
            {/*</Modal>*/}
            {/*)}*/}
        </>
    );
}
