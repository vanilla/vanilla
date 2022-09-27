/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";

import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetItemContentType, IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import { DeepPartial } from "redux";
import { IUserFragment } from "@library/@types/api/users";
import ProfileLink from "@library/navigation/ProfileLink";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { cx } from "@emotion/css";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { leaderboardWidgetClasses } from "@library/leaderboardWidget/LeaderboardWidget.styles";
import { HomeWidget, IWidgetCommonProps } from "@library/homeWidget/HomeWidget";
import { HomeWidgetItem } from "@library/homeWidget/HomeWidgetItem";
import { labelize, RecordID } from "@vanilla/utils";
import { t } from "@vanilla/i18n";
import { Widget } from "@library/layout/Widget";

export interface ILeader {
    user: IUserFragment;
    [key: string]: RecordID | IUserFragment;
}
interface IProps extends IWidgetCommonProps {
    /** Config describing the Widget Container */
    containerOptions?: IHomeWidgetContainerOptions;
    /** An array of objects where keys match the column name */
    leaders: ILeader[];
}

/**
 * A component displaying a list of users with associated data
 * Note: This component does not sort data, it will be displayed in the
 * order it is provided
 */
export function LeaderboardWidget(props: IProps) {
    const { title, subtitle, description, containerOptions, leaders } = props;

    const countKeys = useMemo(
        () => (leaders && leaders[0] ? Object.keys(leaders[0]).filter((key) => key !== "user") : []),
        [leaders],
    );

    enum displayTypesUsingDefaultView {
        "list" = WidgetContainerDisplayType.LIST,
        "link" = WidgetContainerDisplayType.LINK,
    }
    const isDefaultView = containerOptions
        ? containerOptions.displayType === undefined || containerOptions.displayType in displayTypesUsingDefaultView
        : true;

    return (
        <Widget>
            <HomeWidgetContainer {...{ title, subtitle, description }} options={containerOptions}>
                {leaders && !isDefaultView ? (
                    leaders.map((leader) => (
                        <HomeWidgetItem
                            key={leader.user.userID}
                            to={leader.user.url ?? ""}
                            name={leader.user.name}
                            description={leader.user.title}
                            counts={countKeys.map((key) => ({
                                count: Number(leader[key]),
                                labelCode: labelize(key),
                            }))}
                            iconUrl={leader.user.photoUrl}
                            options={{
                                contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
                            }}
                        />
                    ))
                ) : (
                    <LeaderboardTable countKeys={countKeys} rows={leaders} />
                )}
            </HomeWidgetContainer>
        </Widget>
    );
}

export default LeaderboardWidget;

interface ILeaderboardTableProps {
    countKeys: string[];
    rows: ILeader[];
}

/**
 * This implements a simple table displaying user cards in one cell
 * and any counts in subsequent cells
 */
function LeaderboardTable(props: ILeaderboardTableProps) {
    const { countKeys, rows } = props;
    const classes = leaderboardWidgetClasses();
    return (
        <table className={classes.table}>
            <thead className={cx(visibility().visuallyHidden)}>
                <tr className={cx(classes.row)}>
                    <td className={cx(classes.cell)}>{t("Member")}</td>
                    {countKeys.map((key, index) => (
                        <td key={key + index} className={cx(classes.cell)}>
                            {labelize(key)}
                        </td>
                    ))}
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => {
                    const userFragment: IUserFragment = row.user;
                    return (
                        <tr key={userFragment.userID} className={cx(classes.row)}>
                            <td className={cx(classes.cell)}>
                                <span className={cx(classes.userStyles, "Leaderboard-User")}>
                                    <img
                                        src={userFragment.photoUrl}
                                        className={cx(classes.profilePhotoStyles, "ProfilePhoto", "ProfilePhotoSmall")}
                                        loading="lazy"
                                    />
                                    <span className={cx(classes.usernameStyles, "Username")}>
                                        <ProfileLink
                                            className={classes.linkStyles}
                                            userFragment={userFragment}
                                            isUserCard
                                        />
                                    </span>
                                </span>
                            </td>
                            {countKeys.map((key, index) => (
                                <td key={`${key}${index}`} className={cx(classes.cell)}>
                                    <span className={cx(classes.countStyles, "Count")}>{row[key]}</span>
                                </td>
                            ))}
                        </tr>
                    );
                })}
            </tbody>
        </table>
    );
}
