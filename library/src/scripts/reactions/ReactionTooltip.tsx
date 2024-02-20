/**
 * @author Jenny Seburn<jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { StackedList } from "@library/stackedList/StackedList";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { Icon, IconType } from "@vanilla/icons";
import { RecordID } from "@vanilla/utils";
import { reactionsClasses } from "./Reactions.classes";
import { useReactionUsers } from "./Reactions.hooks";
import { IReactionsProps } from "./Reactions.types";

interface IProps {
    iconType: IconType;
    name: string;
    urlCode: string;
    recordType: IReactionsProps["recordType"];
    recordID: RecordID;
}

// Display a tooltip for a specific reaction on a specific record
export function ReactionTooltip(props: IProps) {
    const { iconType, name, ...queryProps } = props;
    const classes = reactionsClasses();
    const userList = useReactionUsers(queryProps);
    const { hasPermission } = usePermissionsContext();
    // determine if the current logged in user has permission to see who has used this specific reaction
    const viewPermission = hasPermission("reactions.view");

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
