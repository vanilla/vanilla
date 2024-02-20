/**
 * @author Jenny Seburn<jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { postReactionsClasses } from "@library/postReactions/PostReactions.classes";
import { usePostReactionsContext } from "@library/postReactions/PostReactionsContext";
import { StackedList } from "@library/stackedList/StackedList";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { Icon, IconType } from "@vanilla/icons";
import { useMemo } from "react";

interface IProps {
    iconType: IconType;
    name: string;
    tagID?: number;
}

// Display a tooltip for a specific reaction on a specific record
export function PostReactionTooltip(props: IProps) {
    const { iconType, name, tagID } = props;
    const classes = postReactionsClasses();
    const { getUsers } = usePostReactionsContext();
    const { hasPermission } = usePermissionsContext();
    // determine if the current logged in user has permission to see who has used this specific reaction
    const viewPermission = hasPermission("reactions.view");

    const userList = useMemo<IUserFragment[]>(() => {
        if (getUsers && tagID) {
            return getUsers(tagID);
        }
        return [];
    }, [getUsers, tagID]);

    return (
        <div className={classes.tooltip}>
            <div className={classes.tooltipTitle}>
                <Icon icon={iconType} className={classes.tooltipIcon} />
                {name}
            </div>
            {viewPermission && userList.length > 0 && (
                <>
                    {userList.length > 5 ? (
                        <StackedList<IUserFragment>
                            themingVariables={stackedListVariables("reactions")}
                            data={userList}
                            maxCount={5}
                            ItemComponent={(user) => <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />}
                        />
                    ) : (
                        <ul className={classes.tooltipUserList}>
                            {userList.map((user) => (
                                <li key={user.userID}>
                                    <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
                                    <span>{user.name}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </>
            )}
        </div>
    );
}
