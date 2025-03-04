/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { cx } from "@emotion/css";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { tagClasses } from "@library/metas/Tags.styles";
import { postReactionsClasses } from "@library/postReactions/PostReactions.classes";
import { PostReactionIconType } from "@library/postReactions/PostReactions.types";
import { usePostReactionsContext } from "@library/postReactions/PostReactionsContext";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import snakeCase from "lodash/snakeCase";
import startCase from "lodash/startCase";
import { useEffect, useState } from "react";
import { PostReactionTooltip } from "./PostReactionTooltip";
import { useReactionLog } from "@library/postReactions/PostReactions.hooks";

/**
 * Render reaction icons as buttons for display in discussion threads and comments
 */
export function PostReactions(props: { reactions?: IReaction[] }) {
    const { reactions } = props;
    const classes = postReactionsClasses();
    const tagStyles = tagClasses();
    const { toggleReaction, counts, recordType, recordID } = usePostReactionsContext();
    const { hasPermission } = usePermissionsContext();
    const reactionLog = useReactionLog({ recordType, recordID });
    // reaction list needs to be in a state to properly update the counts
    const [list, setList] = useState<IReaction[]>();

    // filter out reactions the user don't have permission to use
    // filter out reactions assigned to "Flag" class
    useEffect(() => {
        if (reactions) {
            const tmpList = reactions.filter(({ class: className, urlcode }) => {
                const isAllowed = hasPermission(
                    urlcode === "Promote" ? "curation.manage" : `reactions.${className?.toLowerCase()}.add`,
                );
                return isAllowed && className !== "Flag";
            });
            setList(tmpList);
        }
    }, [reactions, hasPermission]);

    // update the list when counts are updated
    useEffect(() => {
        if (counts && list) {
            const tmpList = list.map((item) => {
                const newCounts = counts.find(({ tagID }) => item.tagID === tagID);
                if (newCounts) {
                    return {
                        ...item,
                        ...(newCounts ?? {}),
                    };
                }
                return {
                    ...item,
                    count: 0,
                    hasReacted: false,
                };
            });
            setList(tmpList);
        }
    }, [counts]);

    // there is no list of reactions, don't display anything
    if (!list || list.length === 0) {
        return null;
    }

    const fetchUserReactionLog = () => {
        if (reactionLog.isStale) {
            void reactionLog.refetch();
        }
    };

    return (
        <div
            className={classes.root}
            onMouseEnter={() => fetchUserReactionLog()}
            onFocus={() => fetchUserReactionLog()}
        >
            {list.map((reaction) => {
                // get the icon type
                const iconType = PostReactionIconType[snakeCase(reaction.urlcode).toUpperCase()];
                // ensure that the count is a number
                const count = reaction.count ?? 0;

                return (
                    <ToolTip
                        key={reaction.tagID}
                        label={
                            <PostReactionTooltip
                                iconType={iconType}
                                name={reaction.name ?? startCase(reaction.urlcode)}
                                tagID={reaction.tagID}
                            />
                        }
                    >
                        <Button
                            ariaLabel={reaction.name}
                            buttonType={ButtonTypes.CUSTOM}
                            className={cx(
                                tagStyles.standard(true),
                                classes.button,
                                reaction.hasReacted && classes.activeButton,
                            )}
                            onClick={() => {
                                if (toggleReaction) {
                                    toggleReaction({ reaction });
                                }
                            }}
                        >
                            <Icon icon={iconType} className={classes.icon} />
                            {count > 0 && <span className={classes.buttonLabel}>{count}</span>}
                        </Button>
                    </ToolTip>
                );
            })}
        </div>
    );
}
