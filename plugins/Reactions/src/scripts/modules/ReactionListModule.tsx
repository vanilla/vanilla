/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { ComponentProps, useState } from "react";
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
import { reactionsVariables } from "@Reactions/variables/Reactions.variables";
import { IGetUserReactionsParams } from "@Reactions/state/ReactionsActions";
import { IReaction } from "@Reactions/types/Reaction";
import { useUserReactions } from "@Reactions/hooks/ReactionsHooks";
import { IUser } from "@library/@types/api/users";

interface IProps extends Pick<ComponentProps<typeof ReactionList>, "maximumLength" | "stacked"> {
    apiParams: IGetUserReactionsParams;
    apiData?: IReaction[];
    homeWidget?: boolean;
    title?: string;
}

export function ReactionListModule(props: IProps) {
    const {
        limit: { maxItems },
    } = reactionsVariables();

    const { title = t("Reactions") } = props;

    const maximumLength = props.maximumLength ?? maxItems;

    const [displayViewAll, setDisplayViewAll] = useState(
        props.apiData?.length ? props.apiData?.length > maximumLength : false,
    );

    const [modalIsVisible, setModalIsVisible] = useState(false);
    const openModal = () => setModalIsVisible(true);
    const closeModal = () => setModalIsVisible(false);

    const data = useUserReactions(props.apiParams, props.apiData, (reactions: IReaction[]) => {
        setDisplayViewAll(reactions.length > maximumLength);
    });

    let content = (
        <ReactionList
            {...data}
            stacked={props.stacked}
            maximumLength={maximumLength}
            openModal={displayViewAll ? openModal : undefined}
        />
    );

    if (props.homeWidget) {
        content = (
            <HomeWidgetContainer
                title={title}
                options={{
                    viewAll: displayViewAll
                        ? {
                              onClick: openModal,
                          }
                        : undefined,
                }}
            >
                {content}
            </HomeWidgetContainer>
        );
    }

    return (
        <>
            {content}
            {displayViewAll && (
                <ReactionListModal
                    userID={props.apiParams.userID}
                    title={title}
                    isVisible={modalIsVisible}
                    exitHandler={closeModal}
                />
            )}
        </>
    );
}

interface IReactionListModalProps {
    userID: IUser["userID"];
    isVisible: ComponentProps<typeof Modal>["isVisible"];
    exitHandler: ComponentProps<typeof Modal>["exitHandler"];
    title?: string;
}

export function ReactionListModal(props: IReactionListModalProps) {
    const { userID, isVisible, exitHandler, title = t("Reactions") } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const data = useUserReactions({ userID });

    return (
        <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={exitHandler}>
            <Frame
                header={<FrameHeader closeFrame={exitHandler} title={title} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <ReactionList {...data} />
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={exitHandler}
                            className={classFrameFooter.actionButton}
                        >
                            {t("OK")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
