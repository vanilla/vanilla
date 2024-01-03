import { sortedTagCloudClasses } from "@dashboard/components/panels/SortedTagCloud.styles";
import { cx } from "@emotion/css";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { TokenItem } from "@library/metas/TokenItem";
import { CloseIcon } from "@vanilla/ui/src/forms/shared/CloseIcon";
import { RecordID, stableObjectHash } from "@vanilla/utils";
import React from "react";

export interface GroupedTag {
    id: RecordID;
    label: string;
    tags: string[];
    onRemove?(tag: Partial<GroupedTag> & { value: string }): void;
    [key: string]: any;
}

interface ISortedTagCloudProps {
    groupedTags: GroupedTag[];
}

/**
 * Generic component to display labeled values as tokens
 */
export function SortedTagCloud(props: ISortedTagCloudProps) {
    const { groupedTags } = props;

    const classes = sortedTagCloudClasses();

    return (
        <>
            {groupedTags.map((groupTag) => {
                return (
                    <React.Fragment key={stableObjectHash(groupTag)}>
                        <div className={cx(inputBlockClasses().sectionTitle, inputBlockClasses().labelAndDescription)}>
                            {groupTag.label}
                        </div>
                        <div className={classes.tokenGroup}>
                            {groupTag.tags.map((tagValue) => (
                                <TokenItem
                                    key={stableObjectHash({ label: groupTag.label, value: tagValue })}
                                    className={classes.token}
                                >
                                    <span>{tagValue}</span>
                                    {groupTag?.onRemove && (
                                        <button
                                            type={"button"}
                                            className={classes.closeButton}
                                            onClick={(event) => {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                groupTag.onRemove &&
                                                    groupTag.onRemove({ ...groupTag, value: tagValue });
                                            }}
                                        >
                                            <CloseIcon className={classes.closeIcon} />
                                        </button>
                                    )}
                                </TokenItem>
                            ))}
                        </div>
                    </React.Fragment>
                );
            })}
        </>
    );
}
