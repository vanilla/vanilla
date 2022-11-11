/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentProps, ComponentType, useMemo } from "react";
import { IUser, IUserFragment } from "@library/@types/api/users";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { userCardClasses } from "@library/features/userCard/UserCard.styles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize, UserPhotoSkeleton } from "@library/headers/mebox/pieces/UserPhoto";
import { CloseCompactIcon } from "@library/icons/common";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { MetaItem, Metas } from "@library/metas/Metas";
import { statClasses } from "@library/stats/Stat.styles";
import { Stat } from "@library/stats/Stat";
import LinkAsButton from "@library/routing/LinkAsButton";
import {
    getMeta,
    makeProfileCommentsUrl,
    makeProfileDiscussionsUrl,
    makeProfileUrl,
    t,
} from "@library/utility/appUtils";
import { cx } from "@emotion/css";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import DateTime from "@library/content/DateTime";
import { hasPermission } from "@library/features/users/Permission";
import { formatUrl } from "@library/utility/appUtils";
import { useCurrentUserID } from "@library/features/users/userHooks";
import { useStackingContext } from "@vanilla/react-utils";

interface IProps {
    user: IUser;
    onClose?: () => void;
}

const DELETED_USER_MSG = "This user has been deleted.";
const BANNED_USER_MSG = "This user has been banned.";
const PRIVATE_USER_MSG = "This user's profile is private.";
const BANNED = "Banned";
const PRIVATE = "Private";
const ERROR = "ERROR";
const DELETED = "DELETED";

interface IExtraUserCardContent {
    key: string;
    component: ComponentType<{ userID: IUser["userID"] }>; //switch it to just a userID
    skeleton?: ComponentType<{ userID: IUser["userID"] }>;
}

UserCardView.extraContent = [] as IExtraUserCardContent[];
UserCardView.registerContent = function (registeredContent: IExtraUserCardContent) {
    if (!UserCardView.extraContent.find((content) => content.key === registeredContent.key)) {
        UserCardView.extraContent.push(registeredContent);
    }
};

UserCardView.extraButtons = [] as IExtraUserCardContent[];
UserCardView.registerButtons = function (registeredButtons: IExtraUserCardContent) {
    if (!UserCardView.extraButtons.find((button) => button.key === registeredButtons.key)) {
        UserCardView.extraButtons.push(registeredButtons);
    }
};

UserCardView.extraLinks = [] as IExtraUserCardContent[];
UserCardView.registerLinks = function (registeredLinks: IExtraUserCardContent) {
    if (!UserCardView.extraLinks.find((button) => button.key === registeredLinks.key)) {
        UserCardView.extraLinks.push(registeredLinks);
    }
};

export function UserCardView(props: IProps) {
    const { zIndex } = useStackingContext();
    const classes = userCardClasses({ zIndex: zIndex });
    const { user } = props;
    const device = useDevice();
    const isCompact = device === Devices.MOBILE || device === Devices.XS;
    const photoSize: UserPhotoSize = isCompact ? UserPhotoSize.XLARGE : UserPhotoSize.LARGE;
    const isConversationsEnabled = getMeta("context.conversationsEnabled", false);

    const currentUseID = useCurrentUserID();
    const isOwnUser = user.userID === currentUseID;

    const privateProfile = user?.private ?? false;
    const hasPersonalInfoView = hasPermission("personalInfo.view");
    const banned = user?.banned ?? 0;
    const isBanned = banned === 1;
    let bannedPrivateProfile = getMeta("ui.bannedPrivateProfile", "0");
    bannedPrivateProfile = bannedPrivateProfile === "" ? "0" : "1";
    const privateBannedProfileEnabled = bannedPrivateProfile !== "0";
    const showPrivateBannedProfile = isBanned && privateBannedProfileEnabled;

    const labelSection = useMemo(() => {
        const { label, title } = user;
        if (isBanned) {
            return <div className={classes.label}>{t(BANNED)}</div>;
        }
        if (title) {
            /* Title can be input by the user. */
            return <div className={classes.label}>{title}</div>;
        }
        if (!title && label) {
            /* Labels (from rank label) are sanitized server side. */
            return <div className={classes.label} dangerouslySetInnerHTML={{ __html: label }} />;
        }
        return null;
    }, [isBanned, user]);

    if ((privateProfile || showPrivateBannedProfile) && !hasPersonalInfoView && !isOwnUser) {
        return <UserCardMinimal user={user} onClose={props.onClose} />;
    }
    return (
        <>
            <div className={classes.header}>
                {isCompact && (
                    <Button className={classes.close} onClick={props.onClose} buttonType={ButtonTypes.ICON}>
                        <>
                            <CloseCompactIcon />
                            <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                        </>
                    </Button>
                )}
            </div>
            <Container>
                <div className={classes.row}>
                    <UserPhoto userInfo={user} size={photoSize} className={classes.userPhoto} />
                </div>

                <div className={classes.row}>
                    <div className={classes.name}>{user.name}</div>
                </div>
                {
                    /* We don't want this section to show at all if there's no label */
                    labelSection && <div className={classes.row}>{labelSection}</div>
                }

                {user.email && (
                    <Permission permission={"personalInfo.view"}>
                        <div className={classes.row}>
                            <a className={classes.headerLink} href={`mailto:${user.email}`}>
                                {user.email}
                            </a>
                        </div>
                    </Permission>
                )}

                {UserCardView.extraLinks.map((link, i) => (
                    <link.component key={i} userID={user.userID} />
                ))}

                {isBanned && (
                    <div className={cx(classes.row, classes.message)}>
                        <div>{t(BANNED_USER_MSG)}</div>
                    </div>
                )}
            </Container>

            <div className={cx(classes.row, classes.buttonsContainer)}>
                <CardButton to={makeProfileUrl(user.name)}>{t("View Profile")}</CardButton>
                <Permission permission={"conversations.add"}>
                    {isConversationsEnabled && !banned && (
                        <CardButton to={`/messages/add/${user.name}`}>{t("Message")}</CardButton>
                    )}
                </Permission>
                {UserCardView.extraButtons.map((button, i) => (
                    <button.component key={i} userID={user.userID} />
                ))}
            </div>

            <Container borderTop>
                <Stat
                    to={makeProfileDiscussionsUrl(user.name)}
                    value={user.countDiscussions}
                    label={t("Discussions")}
                    classNames={classes.statLeft}
                />
                <Stat
                    to={makeProfileCommentsUrl(user.name)}
                    value={user.countComments}
                    label={t("Comments")}
                    classNames={classes.statRight}
                />
            </Container>

            {UserCardView.extraContent.map((content, index) => (
                <Container key={index} borderTop>
                    <content.component userID={user.userID} />
                </Container>
            ))}

            <Container borderTop>
                <Metas className={classes.metas}>
                    <MetaItem className={classes.metaItem}>
                        {t("Joined")}: <DateTime timestamp={user.dateInserted} />
                    </MetaItem>
                    {user.dateLastActive && (
                        <MetaItem className={classes.metaItem}>
                            {t("Last Active")}: <DateTime timestamp={user.dateLastActive} />
                        </MetaItem>
                    )}
                </Metas>
            </Container>
        </>
    );
}

interface ISkeletonProps {
    userFragment?: Partial<IUserFragment>;
    onClose?: () => void;
}

export function UserCardSkeleton(props: ISkeletonProps) {
    const { userFragment } = props;
    const device = useDevice();
    const isCompact = device === Devices.MOBILE || device === Devices.XS;
    const photoSize: UserPhotoSize = isCompact ? UserPhotoSize.XLARGE : UserPhotoSize.LARGE;
    const isConversationsEnabled = getMeta("context.conversationsEnabled", false);
    const classes = userCardClasses();
    return (
        <>
            <div className={classes.header}>
                {isCompact && (
                    <Button className={classes.close} onClick={props.onClose} buttonType={ButtonTypes.ICON}>
                        <>
                            <CloseCompactIcon />
                            <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                        </>
                    </Button>
                )}
            </div>
            <Container>
                <div className={classes.row}>
                    {userFragment?.photoUrl ? (
                        <UserPhoto userInfo={userFragment} size={photoSize} className={classes.userPhoto} />
                    ) : (
                        <UserPhotoSkeleton size={photoSize} className={classes.userPhoto} />
                    )}
                </div>

                <div className={classes.row}>
                    <div className={classes.name}>
                        {userFragment?.name ?? <LoadingRectangle inline height={12} width={60} />}
                    </div>
                </div>

                <Permission permission={"personalInfo.view"} mode={PermissionMode.GLOBAL}>
                    <div className={classes.row}>
                        <span className={classes.headerLink}>
                            <LoadingRectangle inline height={12} width={120} />
                        </span>
                    </div>
                </Permission>
                {!!userFragment?.userID &&
                    UserCardView.extraLinks.map((link, i) => (
                        <React.Fragment key={i}>
                            {link.skeleton ? (
                                <link.skeleton userID={userFragment.userID!} />
                            ) : (
                                <LoadingRectangle inline height={12} width={120} />
                            )}
                        </React.Fragment>
                    ))}
            </Container>

            <div className={cx(classes.row, classes.buttonsContainer)}>
                <CardButton
                    disabled={!userFragment?.name}
                    to={userFragment?.name ? makeProfileUrl(userFragment?.name) : ""}
                >
                    {t("View Profile")}
                </CardButton>
                <Permission permission={"conversations.add"}>
                    {isConversationsEnabled && (
                        <CardButton disabled={!userFragment?.name} to={`/messages/add/${userFragment?.name}`}>
                            {t("Message")}
                        </CardButton>
                    )}
                </Permission>
                {!!userFragment?.userID &&
                    UserCardView.extraButtons.map((button, i) => (
                        <>
                            {button.skeleton ? (
                                <button.skeleton key={i} userID={userFragment.userID!} />
                            ) : (
                                <LoadingRectangle inline height="2em" width={120} />
                            )}
                        </>
                    ))}
            </div>

            <Container borderTop>
                <StatSkeleton text={t("Discussions")} position={"left"} />
                <StatSkeleton text={t("Comments")} position={"right"} />
            </Container>

            {!!userFragment?.userID &&
                UserCardView.extraContent.map((content, index) => (
                    <Container key={index} borderTop>
                        {content.skeleton ? (
                            <content.skeleton userID={userFragment.userID!} />
                        ) : (
                            <LoadingRectangle inline height="2em" width={120} />
                        )}
                    </Container>
                ))}

            <Container borderTop>
                <Metas className={classes.metas}>
                    <MetaItem className={classes.metaItem}>
                        {t("Joined")}: <LoadingRectangle inline height={8} width={60} />
                    </MetaItem>
                    <MetaItem className={classes.metaItem}>
                        {t("Last Active")}: <LoadingRectangle inline height={8} width={60} />
                    </MetaItem>
                </Metas>
            </Container>
        </>
    );
}

interface IMinimalProps {
    user?: IUser;
    userFragment?: Partial<IUserFragment>;
    onClose?: () => void;
}

export function UserCardMinimal(props: IMinimalProps) {
    const { user, userFragment } = props;
    const classes = userCardClasses();
    const device = useDevice();

    const isCompact = device === Devices.MOBILE || device === Devices.XS;
    const photoSize: UserPhotoSize = isCompact ? UserPhotoSize.XLARGE : UserPhotoSize.LARGE;

    let banned = user?.banned ?? userFragment?.banned ?? 0;
    let isBanned = banned === 1;
    let labelText = isBanned ? t(BANNED) : t(PRIVATE);
    let msg = isBanned ? t(BANNED_USER_MSG) : t(PRIVATE_USER_MSG);
    let name = user?.name ?? userFragment?.name;
    let userInfo = user ?? userFragment;

    return (
        <>
            <div className={classes.header} />
            <Container>
                <div className={classes.row}>
                    <UserPhoto userInfo={userInfo} size={photoSize} className={classes.userPhoto} />
                </div>

                <div className={classes.row}>
                    <div className={classes.name}>{name}</div>
                </div>
                <div className={classes.row}>
                    <div className={classes.label}>{labelText}</div>
                </div>
                <div className={cx(classes.row, classes.message)}>
                    <div>{msg}</div>
                </div>
            </Container>
        </>
    );
}

interface IUserCardErrorProps {
    error?: string | null;
    onClose?: () => void;
}

export function UserCardError(props: IUserCardErrorProps) {
    const classes = userCardClasses();
    const device = useDevice();

    const isCompact = device === Devices.MOBILE || device === Devices.XS;
    const photoSize: UserPhotoSize = isCompact ? UserPhotoSize.XLARGE : UserPhotoSize.LARGE;
    const user = {
        photoUrl: formatUrl("/applications/dashboard/design/images/banned.png", true),
    };
    const msg = props.error ? props.error : DELETED_USER_MSG;
    const label = props.error ? t(ERROR) : t(DELETED);

    return (
        <>
            <div className={classes.header} />
            <Container>
                <div className={classes.row}>
                    <UserPhoto userInfo={user} size={photoSize} className={classes.userPhoto} />
                </div>

                <div className={classes.row}>
                    <div className={classes.label}>{label}</div>
                </div>
                <div className={cx(classes.row, classes.message)}>
                    <div>{msg}</div>
                </div>
            </Container>
        </>
    );
}

export function CardButton(props: { disabled?: boolean; to?: string; target?: string; children?: React.ReactNode }) {
    const { zIndex } = useStackingContext();
    const classes = userCardClasses({ zIndex: zIndex });

    return (
        <div className={classes.buttonContainer}>
            <LinkAsButton
                disabled={props.disabled}
                to={props.to ?? ""}
                buttonType={ButtonTypes.STANDARD}
                className={classes.button}
                target={props.target}
            >
                {props.children}
            </LinkAsButton>
        </div>
    );
}

function StatSkeleton(props: { text: string; position: "left" | "right" }) {
    const { zIndex } = useStackingContext();
    const classes = statClasses();
    const cardClasses = userCardClasses({ zIndex: zIndex });

    const { text, position } = props;
    return (
        <div
            className={cx(
                classes.statItem,
                { [cardClasses.statLeft]: position === "left" },
                { [cardClasses.statRight]: position === "right" },
            )}
        >
            <div className={classes.statData}>
                <LoadingRectangle height={35} width={48} />
            </div>
            <div className={classes.statLabel}>{text}</div>
        </div>
    );
}

function Container(props: { children: React.ReactNode; borderTop?: boolean }) {
    const { borderTop } = props;
    const { zIndex } = useStackingContext();
    const classes = userCardClasses({ zIndex: zIndex });
    return <div className={borderTop ? classes.containerWithBorder : classes.container}>{props.children}</div>;
}
