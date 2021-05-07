/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUser, IUserFragment } from "@library/@types/api/users";
import NumberFormatted from "@library/content/NumberFormatted";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { userCardClasses } from "@library/features/userCard/UserCard.styles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize, UserPhotoSkeleton } from "@library/headers/mebox/pieces/UserPhoto";
import { CloseCompactIcon } from "@library/icons/common";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { MetaItem, Metas } from "@library/metas/Metas";
import LinkAsButton from "@library/routing/LinkAsButton";
import { getMeta, makeProfileUrl, t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import DateTime from "@library/content/DateTime";

interface IProps {
    user: IUser;
    onClose?: () => void;
}

export function UserCardView(props: IProps) {
    const classes = userCardClasses();
    const { user } = props;
    const device = useDevice();
    const isCompact = device === Devices.MOBILE || device === Devices.XS;
    const photoSize: UserPhotoSize = isCompact ? UserPhotoSize.XLARGE : UserPhotoSize.LARGE;
    const isConversationsEnabled = getMeta("context.conversationsEnabled", false);

    const label = user.title ?? user.label;

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

            <UserPhoto userInfo={user} size={photoSize} className={classes.userPhoto} />

            <div className={classes.metaContainer}>
                <div className={classes.row}>
                    <div className={classes.name}>{user.name}</div>
                </div>
                {
                    /* We don't want this section to show at all if there's no label */
                    label && (
                        <div className={classes.row}>
                            {
                                /* HTML here is sanitized server side. */
                                label ? (
                                    <div className={classes.label} dangerouslySetInnerHTML={{ __html: label }} />
                                ) : null
                            }
                        </div>
                    )
                }

                {user.email && (
                    <Permission permission={"personalInfo.view"}>
                        <div className={classes.row}>
                            <a className={classes.email} href={`mailto:${user.email}`}>
                                {user.email}
                            </a>
                        </div>
                    </Permission>
                )}
            </div>

            <div className={classNames(classes.container, classes.actionContainer)}>
                <CardButton to={makeProfileUrl(user.name)}>{t("View Profile")}</CardButton>
                <Permission permission={"conversations.add"}>
                    {isConversationsEnabled && (
                        <CardButton to={`/messages/add/${user.name}`}>{t("Message")}</CardButton>
                    )}
                </Permission>
            </div>

            <Container borderTop={true}>
                <Stat count={user.countDiscussions} text={t("Discussions")} position={"left"} />
                <Stat count={user.countComments} text={t("Comments")} position={"right"} />
            </Container>

            <Container borderTop={true}>
                <Metas className={classes.metas}>
                    <MetaItem>
                        {t("Joined")}: <DateTime timestamp={user.dateInserted} />
                    </MetaItem>
                    {user.dateLastActive && (
                        <MetaItem>
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
            {userFragment?.photoUrl ? (
                <UserPhoto userInfo={userFragment} size={photoSize} className={classes.userPhoto} />
            ) : (
                <UserPhotoSkeleton size={photoSize} className={classes.userPhoto} />
            )}

            <div className={classes.metaContainer}>
                <div className={classes.row}>
                    <div className={classes.name}>
                        {userFragment?.name ?? <LoadingRectangle inline height={12} width={60} />}
                    </div>
                </div>

                <Permission permission={"personalInfo.view"} mode={PermissionMode.GLOBAL}>
                    <div className={classes.row}>
                        <span className={classes.email}>
                            <LoadingRectangle inline height={12} width={120} />
                        </span>
                    </div>
                </Permission>
            </div>

            <div className={classNames(classes.container, classes.actionContainer)}>
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
            </div>

            <Container borderTop={true}>
                <StatSkeleton text={t("Discussions")} position={"left"} />
                <StatSkeleton text={t("Comments")} position={"right"} />
            </Container>

            <Container borderTop={true}>
                <Metas className={classes.metas}>
                    <MetaItem>
                        {t("Joined")}: <LoadingRectangle inline height={8} width={60} />
                    </MetaItem>
                    <MetaItem>
                        {t("Last Active")}: <LoadingRectangle inline height={8} width={60} />
                    </MetaItem>
                </Metas>
            </Container>
        </>
    );
}

function CardButton(props: { disabled?: boolean; to?: string; children?: React.ReactNode }) {
    const classes = userCardClasses();

    return (
        <div className={classes.buttonContainer}>
            <LinkAsButton
                disabled={props.disabled}
                to={props.to}
                buttonType={ButtonTypes.STANDARD}
                className={classes.button}
            >
                {props.children}
            </LinkAsButton>
        </div>
    );
}

function StatSkeleton(props: { text: string; position: "left" | "right" }) {
    const classes = userCardClasses();

    const { text, position } = props;
    return (
        <div
            className={classNames(classes.stat, {
                [classes.statLeft]: position === "left",
                [classes.statRight]: position === "right",
            })}
        >
            <div className={classes.count}>
                <LoadingRectangle height={27} width={48} />
            </div>
            <div className={classes.statLabel}>{text}</div>
        </div>
    );
}

function Stat(props: { count?: number; text: string; position: "left" | "right" }) {
    const classes = userCardClasses();

    const { count, text, position } = props;
    return (
        <div
            className={classNames(classes.stat, {
                [classes.statLeft]: position === "left",
                [classes.statRight]: position === "right",
            })}
        >
            <div className={classes.count}>
                <NumberFormatted fallbackTag={"div"} value={count || 0} />
            </div>
            <div className={classes.statLabel}>{text}</div>
        </div>
    );
}

function Container(props: { children: React.ReactNode; borderTop?: boolean }) {
    const { borderTop } = props;
    const classes = userCardClasses();
    return (
        <div className={classNames(classes.container, { [classes.containerWithBorder]: borderTop })}>
            {props.children}
        </div>
    );
}
