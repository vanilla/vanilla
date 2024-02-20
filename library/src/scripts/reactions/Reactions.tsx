/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { tagClasses } from "@library/metas/Tags.styles";
import { reactionsClasses } from "@library/reactions/Reactions.classes";
import { useToggleReaction } from "@library/reactions/Reactions.hooks";
import { IReactionsProps, ReactionIconType } from "@library/reactions/Reactions.types";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import snakeCase from "lodash/snakeCase";
import { useMemo } from "react";
import { ReactionTooltip } from "./ReactionTooltip";
import { IReaction } from "@dashboard/@types/api/reaction";
import startCase from "lodash/startCase";

/**
 * Render reaction icons as buttons for display in discussion threads and comments
 */
export function Reactions(props: IReactionsProps) {
    const { reactions, ...hookProps } = props;
    const classes = reactionsClasses();
    const tagStyles = tagClasses();
    const { toggleResponse, toggleReaction } = useToggleReaction(hookProps);
    const { hasPermission } = usePermissionsContext();

    // filter out reactions the user don't have permission to use
    // filter out reactions assigned to "Flag" class
    const list = useMemo<IReaction[] | undefined>(() => {
        return reactions
            ?.filter(({ class: className, urlcode }) => {
                const isAllowed = hasPermission(
                    urlcode === "Promote" ? "curation.manage" : `reactions.${className?.toLowerCase()}.add`,
                );
                return isAllowed && className !== "Flag";
            })
            .map((reaction) => {
                // update reaction count and if the user has reacted when toggling a reaction
                return {
                    ...reaction,
                    ...(toggleResponse?.find((newCount) => newCount.tagID === reaction.tagID) ?? {}),
                };
            });
    }, [reactions, hasPermission, toggleResponse]);

    // there is no list of reactions, don't display anything
    if (!list) {
        return null;
    }

    return (
        <div className={classes.root}>
            {list.map((reaction) => {
                // get the icon type
                const iconType = ReactionIconType[snakeCase(reaction.urlcode).toUpperCase()];
                // ensure that the count is a number
                const count = reaction.count ?? 0;

                return (
                    <ToolTip
                        key={reaction.tagID}
                        label={
                            <ReactionTooltip
                                iconType={iconType}
                                name={reaction.name ?? startCase(reaction.urlcode)}
                                urlCode={reaction.urlcode}
                                {...hookProps}
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
                            onClick={async () => {
                                await toggleReaction(reaction);
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
