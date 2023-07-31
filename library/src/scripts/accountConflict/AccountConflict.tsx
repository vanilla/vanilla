/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import { useSignOutLink } from "@library/contexts/EntryLinkContext";
import { useCurrentUser, useUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { getMeta, t } from "@library/utility/appUtils";
import React, { useMemo, useState } from "react";

interface IImplProps {
    searchQuery: Record<string, any>;
    pathname: string;
    /** Additional actions to take prior to signing the user out */
    onSignOut?: () => Promise<void>;
    /** Additiona actions to take prior to closing the modal and remaining signed in */
    onClose?: () => Promise<void>;
}

/**
 * Modal to alert the user they are accessing a profile page for the logged in user and not the referenced user
 * Example: The user has navigated from the Unsubscribe landing page for a different account then what they are
 * currently logged in as. The notification alerts the user to the this and allows them the opportunity to
 * sign out and sign back in as the referenced user
 */
export function AccountConflictImpl(props: IImplProps) {
    const { searchQuery = {}, pathname = "/", onSignOut = () => null, onClose = () => null } = props;
    const [showModal, setShowModal] = useState<boolean>(searchQuery.accountConflict);
    const currentUser = useCurrentUser();
    const currentUserExpanded = useUser({ userID: currentUser?.userID });
    const classFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const signOutLink = useSignOutLink();

    // The `currentUser` object does not include the email address. We need to fetch it.
    const userEmail = useMemo<string | undefined>(() => {
        return currentUserExpanded.data?.email;
    }, [currentUserExpanded]);

    const redirectLink = useMemo<string>(() => {
        const linkParts = [pathname];

        const urlQuery = Object.entries(searchQuery)
            .map((keyValue) => keyValue.join("="))
            .filter((keyValue) => !keyValue.includes("accountConflict"))
            .join("&");

        if (urlQuery.length > 0) {
            linkParts.push(urlQuery);
        }

        return linkParts.join("?");
    }, [searchQuery, pathname]);

    const closeModal = async () => {
        await onClose?.();
        // react-router hooks causing errors on some pages. Use `window.history` instead
        window.history.replaceState(null, document.title, redirectLink);
        setShowModal(false);
    };

    const signOut = async () => {
        await onSignOut?.();
        const transientKey = getMeta("TransientKey");
        const link = `${signOutLink}?TransientKey=${transientKey}&Target=${redirectLink}`;
        setShowModal(false);
        // react-router hooks causing errors on some pages. Use `window.history` instead
        window.location.replace(link);
    };

    return (
        <Modal id="account-conflict" size={ModalSizes.MEDIUM} isVisible={showModal} exitHandler={closeModal}>
            <Frame
                header={<FrameHeader title={t("Possible Account Conflict")} closeFrame={closeModal} />}
                body={
                    <FrameBody>
                        <div className={classFrameBody.contents}>
                            <Translate
                                source="You're signed in as <0/> (<1/>). This is different than the source that linked here. You may sign out to change accounts."
                                c0={currentUser?.name}
                                c1={userEmail}
                            />
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            className={classFrameFooter.actionButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={closeModal}
                        >
                            {t("Stay Here")}
                        </Button>
                        <Button
                            className={classFrameFooter.actionButton}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            onClick={signOut}
                        >
                            {t("Sign Out")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}

export function AccountConflict(props: Pick<IImplProps, "onSignOut" | "onClose">) {
    const { onSignOut, onClose } = props;
    // `useLocation` from react-router caused errors. Use `window.location` instead
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery: Record<string, any> = Object.fromEntries(urlParams.entries());
    const pathname = window.location.pathname;

    if (searchQuery.accountConflict === "true") {
        searchQuery.accountConflict = true;
        return (
            <AccountConflictImpl
                searchQuery={searchQuery}
                pathname={pathname}
                onSignOut={onSignOut}
                onClose={onClose}
            />
        );
    }

    return null;
}
