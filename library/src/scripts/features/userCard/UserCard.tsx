/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { IUser, IUserFragment } from "@library/@types/api/users";
import { hasUserViewPermission } from "@library/features/users/modules/hasUserViewPermission";
import { useUser, useCurrentUserID } from "@library/features/users/userHooks";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import LazyModal from "@library/modal/LazyModal";
import ModalSizes from "@library/modal/ModalSizes";
import { UserCardContext, useUserCardContext } from "@library/features/userCard/UserCard.context";
import { UserCardMinimal, UserCardSkeleton, UserCardView } from "@library/features/userCard/UserCard.views";
import { useUniqueID } from "@library/utility/idUtils";
import Popover, { positionDefault } from "@reach/popover";
import { t } from "@vanilla/i18n";
import { useFocusWatcher } from "@vanilla/react-utils";
import React, { useCallback, useRef, useState } from "react";
import { UserCardError } from "@library/features/userCard/UserCard.views";
import { hasPermission } from "@library/features/users/Permission";
import { getMeta } from "@library/utility/appUtils";

interface IProps {
    /** UserID of the user being loaded. */
    userID: number;

    /** A fragment can help display some data while the full user is loaded. */
    userFragment?: Partial<IUserFragment>;

    /** If a full user is passed, no network requests will be made, and the user will be displayed immediately. */
    user?: IUser;

    /** Callback in the even the close button in the card is clicked. */
    onClose?: () => void;

    /** Show a skeleton  */
    forceSkeleton?: boolean;
}

/**
 * Component representing the inner contents of a user card.
 */
export function UserCard(props: IProps) {
    if (!hasUserViewPermission()) {
        return <></>;
    }

    if (props.user) {
        // We have a full user, just render the view.
        return <UserCardView user={props.user} onClose={props.onClose} />;
    } else {
        return <UserCardDynamic {...props} />;
    }
}

export function UserCardPopup(props: React.PropsWithChildren<IProps> & { forceOpen?: boolean }) {
    const [_isOpen, _setIsOpen] = useState(false);
    const isOpen = props.forceOpen ?? _isOpen;

    function setIsOpen(newOpen: boolean) {
        _setIsOpen(newOpen);
        // Kludge for interaction with old flyout system.
        if (newOpen && window.closeAllFlyouts) {
            window.closeAllFlyouts();
        }
    }

    const triggerID = useUniqueID("popupTrigger");
    const contentID = triggerID + "-content";
    const triggerRef = useRef<HTMLElement>(null);
    const contentRef = useRef<HTMLElement>(null);
    const device = useDevice();
    const forceModal = device === Devices.MOBILE || device === Devices.XS;

    if (!hasUserViewPermission()) {
        return <>{props.children}</>;
    }

    return (
        <UserCardContext.Provider
            value={{
                isOpen,
                setIsOpen,
                triggerRef,
                contentRef,
                triggerID,
                contentID,
            }}
        >
            {props.children}
            {!forceModal && isOpen && (
                // If we aren't a modal, and we're open, show the flyout.
                <Popover targetRef={triggerRef} position={positionPreferTopMiddle}>
                    <UserCardFlyout
                        {...props}
                        onClose={() => {
                            setIsOpen(false);
                        }}
                    />
                </Popover>
            )}
            {forceModal && (
                // On mobile we are forced into a modal, which is controlled by the `isVisible` param instead of conditional rendering.
                <LazyModal
                    isVisible={isOpen}
                    size={ModalSizes.SMALL}
                    exitHandler={() => {
                        setIsOpen(false);
                    }}
                >
                    <UserCard
                        {...props}
                        onClose={() => {
                            setIsOpen(false);
                        }}
                    />
                </LazyModal>
            )}
        </UserCardContext.Provider>
    );
}

/**
 * Call this hook to get the props for a user card trigger.
 * Simply spread the `props` over the component and pass the `triggerRef` to the underlying element.
 */
export function useUserCardTrigger(): {
    props: React.HTMLAttributes<HTMLElement>;
    triggerRef: React.RefObject<HTMLElement | null>;
    isOpen?: boolean;
} {
    const context = useUserCardContext();

    const handleFocusChange = useCallback(
        (hasFocus, newActiveElement) => {
            if (
                !hasFocus &&
                newActiveElement !== context.contentRef.current &&
                !context.contentRef.current?.contains(newActiveElement)
            ) {
                context.setIsOpen(false);
            }
        },
        [context.setIsOpen, context.contentRef],
    );
    useFocusWatcher(context.triggerRef, handleFocusChange);

    return {
        props: hasUserViewPermission()
            ? {
                  "aria-controls": context.contentID,
                  "aria-expanded": context.isOpen,
                  "aria-haspopup": context.isOpen,
                  role: "button",
                  onClick: (e) => {
                      e.preventDefault();
                      context.setIsOpen(!context.isOpen);
                  },
                  onKeyPress: (e) => {
                      if (e.key === " " || e.key === "Enter") {
                          e.preventDefault();
                          context.setIsOpen(!context.isOpen);
                      }

                      if (e.key === "Escape") {
                          e.preventDefault();
                          context.setIsOpen(false);
                          context.triggerRef.current?.focus();
                      }
                  },
              }
            : {},
        triggerRef: context.triggerRef,
        isOpen: context.isOpen,
    };
}

/**
 * Calculate a position for the user card that is centered if possible.
 */
function positionPreferTopMiddle(targetRect?: DOMRect | null, popoverRect?: DOMRect | null): React.CSSProperties {
    const posDefault = positionDefault(targetRect, popoverRect);

    const halfPopoverWidth = (popoverRect?.width ?? 0) / 2;
    const halfTriggerWidth = (targetRect?.width ?? 0) / 2;
    const left = (targetRect?.left ?? 0) + halfTriggerWidth + window.pageXOffset - halfPopoverWidth;

    const minimumInset = 16;
    if (left < minimumInset || left + halfPopoverWidth * 2 > window.innerWidth - minimumInset) {
        // We have a collision.
        // Just use default positioning.
        return posDefault;
    }

    return {
        ...posDefault,
        left,
    };
}

/**
 * The content of the user card, wrapped in a flyout.
 */
export function UserCardFlyout(props: React.ComponentProps<typeof UserCard>) {
    const context = useUserCardContext();

    const handleFocusChange = useCallback(
        (hasFocus, newActiveElement) => {
            if (newActiveElement && !hasFocus && newActiveElement !== context.triggerRef.current) {
                context.setIsOpen(false);
            }
        },
        [context.setIsOpen, context.triggerRef],
    );

    useFocusWatcher(context.contentRef, handleFocusChange);

    return (
        <div
            ref={context.contentRef as any}
            className={cx(dropDownClasses().contentsBox, "isMedium")}
            onKeyDown={(e) => {
                if (e.key === "Escape") {
                    e.preventDefault();
                    context.setIsOpen(false);
                    context.triggerRef.current?.focus();
                }
            }}
            onClick={(e) => {
                e.stopPropagation();
                e.nativeEvent.stopPropagation();
                e.nativeEvent.stopImmediatePropagation();
            }}
        >
            <UserCard {...props} />
        </div>
    );
}

/**
 * Wrapper around `UserCardView` that loads the data dynamically.
 */
function UserCardDynamic(props: IProps) {
    const { userFragment, forceSkeleton = false } = props;
    const user = useUser({ userID: props.userID });
    const currentUseID = useCurrentUserID();
    const isOwnUser = userFragment?.userID === currentUseID;
    const hasPersonalInfoView = hasPermission("personalInfo.view");
    let bannedPrivateProfile = getMeta("ui.bannedPrivateProfile", "0");
    bannedPrivateProfile = bannedPrivateProfile === "" ? "0" : "1";
    const privateBannedProfileEnabled = bannedPrivateProfile !== "0";
    let banned = userFragment?.banned ?? 0;
    let isBanned = banned === 1;

    if ((userFragment?.private || (privateBannedProfileEnabled && isBanned)) && !hasPersonalInfoView && !isOwnUser) {
        return <UserCardMinimal userFragment={userFragment} onClose={props.onClose} />;
    }

    if (forceSkeleton || user.status === LoadStatus.PENDING || user.status === LoadStatus.LOADING) {
        return <UserCardSkeleton userFragment={userFragment} onClose={props.onClose} />;
    }

    if (user.error && user?.error?.response.status === 404) {
        return <UserCardError onClose={props.onClose} />;
    }

    if (!user.data || user.status === LoadStatus.ERROR) {
        return <UserCardError error={t("Failed to load user")} onClose={props.onClose} />;
    }

    return <UserCardView user={user.data} onClose={props.onClose} />;
}
