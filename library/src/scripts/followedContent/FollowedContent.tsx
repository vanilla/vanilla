/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { followedContentClasses } from "@library/followedContent/FollowedContent.classes";
import Heading from "@library/layout/Heading";
import { t } from "@vanilla/i18n";
import { FollowedContentProvider, useFollowedContent } from "@library/followedContent/FollowedContentContext";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { AccountConflict } from "@library/accountConflict/AccountConflict";
import { FollowedCategoriesContent } from "@library/followedContent/FollowedCategoriesContent";

interface IProps {
    userID: number;
    renderAdditionalFollowedContent?: boolean;
}

export function FollowedContentImpl(props: IProps) {
    const classes = followedContentClasses();
    const { additionalFollowedContent } = useFollowedContent();

    return (
        <section className={classes.section}>
            <Heading depth={1} renderAsDepth={1}>
                {t("Followed Content")}
            </Heading>
            {props.renderAdditionalFollowedContent ? (
                <Tabs
                    tabsRootClass={classes.tabsContent}
                    tabPanelClasses={classes.tabsPanel}
                    tabListClasses={classes.tabList}
                    tabType={TabsTypes.BROWSE}
                    largeTabs
                    includeBorder={false}
                    data={[
                        {
                            tabID: "followedCategories",
                            label: "Manage Categories",
                            contents: <FollowedCategoriesContent />,
                        },
                        ...additionalFollowedContent.map((content) => ({
                            tabID: `followed${content.contentName}`,
                            label: `Manage ${content.contentName}`,
                            contents: <content.contentRenderer />,
                        })),
                    ]}
                />
            ) : (
                <FollowedCategoriesContent withTitle />
            )}
        </section>
    );
}

export function FollowedContent(props: IProps) {
    return (
        <div>
            <FollowedContentProvider userID={props.userID}>
                <ErrorPageBoundary>
                    <FollowedContentImpl {...props} />
                </ErrorPageBoundary>
            </FollowedContentProvider>
            <AccountConflict />
        </div>
    );
}

export default FollowedContent;
