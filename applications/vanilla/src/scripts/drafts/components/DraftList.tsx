/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { draftsClasses } from "@vanilla/addon-vanilla/drafts/Drafts.classes";
import { List } from "@library/lists/List";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { MetaItem } from "@library/metas/Metas";
import DateTime from "@library/content/DateTime";
import { groupDraftsByDateScheduled } from "@vanilla/addon-vanilla/drafts/utils";
import { DraftsPageTab } from "@vanilla/addon-vanilla/drafts/pages/DraftsPage";
import { DraftListItem } from "@vanilla/addon-vanilla/drafts/components/DraftListItem";
import { cx } from "@emotion/css";
import { t } from "@vanilla/i18n";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";

interface IProps {
    currentTab: DraftsPageTab;
    drafts: IDraft[];
}

export function DraftList(props: IProps) {
    const { currentTab, drafts } = props;
    const classes = draftsClasses();

    const hasDrafts = drafts?.length && drafts?.length > 0;

    const draftsByScheduledDate = groupDraftsByDateScheduled(drafts ?? []);

    if (currentTab === DraftsPageTab.DRAFTS || !Object.keys(draftsByScheduledDate).length) {
        return (
            <>
                {hasDrafts ? (
                    <List options={{ itemLayout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                        {drafts.map((draft) => {
                            return <DraftListItem key={draft.draftID} draft={draft} isSchedule={false} />;
                        })}
                    </List>
                ) : (
                    t("No results found.")
                )}
            </>
        );
    }

    return (
        <>
            {hasDrafts &&
                Object.keys(draftsByScheduledDate).map((dateGroup, dateGroupIndex) => {
                    return (
                        <div key={dateGroupIndex}>
                            <MetaItem>
                                <DateTime
                                    className={cx({
                                        [classes.failedSchedulesDate]: currentTab === DraftsPageTab.ERRORS,
                                    })}
                                    timestamp={draftsByScheduledDate[dateGroup][0].dateScheduled}
                                />
                            </MetaItem>
                            <List options={{ itemLayout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                                {draftsByScheduledDate[dateGroup].map((draft, index) => {
                                    return (
                                        <DraftListItem
                                            key={draft.draftID}
                                            draft={draft}
                                            lastItemInDateGroup={
                                                dateGroupIndex < Object.keys(draftsByScheduledDate).length - 1 &&
                                                index === draftsByScheduledDate[dateGroup].length - 1
                                            }
                                            isSchedule
                                        />
                                    );
                                })}
                            </List>
                        </div>
                    );
                })}
        </>
    );
}
