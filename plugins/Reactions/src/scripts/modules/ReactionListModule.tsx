/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useState } from "react";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { t } from "@library/utility/appUtils";
import { ReactionList } from "@Reactions/components/ReactionList";
import { reactionVariables } from "@Reactions/variables/Reaction.variables";

interface IProps extends React.ComponentProps<typeof ReactionList> {
    title?: string;
    description?: string;
    subtitle?: string;
}

export function ReactionListModule(props: IProps) {
    const { title = t("Reactions"), description, subtitle } = props;

    const {
        limit: { maxItems },
    } = reactionVariables();

    const displayViewAll = props.apiData?.length ? props.apiData?.length > maxItems : false;

    const [modalIsVisible, setModalIsVisible] = useState(false);
    const openModal = () => setModalIsVisible(true);
    const closeModal = () => setModalIsVisible(false);

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    return (
        <HomeWidgetContainer
            title={title}
            description={description}
            subtitle={subtitle}
            options={{
                isGrid: false,
                viewAll: displayViewAll
                    ? {
                          onClick: openModal,
                      }
                    : undefined,
            }}
        >
            <ReactionList apiParams={props.apiParams} apiData={props.apiData} maximumLength={maxItems} />
            <Modal isVisible={modalIsVisible} size={ModalSizes.MEDIUM} exitHandler={closeModal}>
                <Frame
                    header={<FrameHeader closeFrame={closeModal} title={title} />}
                    body={
                        <FrameBody>
                            <div className={classesFrameBody.contents}>
                                <ReactionList apiParams={{ userID: props.apiParams.userID }} />
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={closeModal}
                                className={classFrameFooter.actionButton}
                            >
                                {t("OK")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </Modal>
        </HomeWidgetContainer>
    );
}
