/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { usePostRevisions } from "@dashboard/moderation/CommunityManagement.hooks";
import { Sort } from "@library/sort/Sort";
import { t } from "@vanilla/i18n";

interface IProps {
    className?: string;
    postRevisionOptions: ReturnType<typeof usePostRevisions>;
}

export function PostRevisionPicker(props: IProps) {
    const { postRevisionOptions, className } = props;

    return (
        <span className={className}>
            {postRevisionOptions.options.length > 1 ? (
                <Sort
                    sortID={"postRevision"}
                    sortLabel={t("Revision: ")}
                    sortOptions={postRevisionOptions.options.map((option) => {
                        return {
                            name: option.label,
                            value: option.value,
                        };
                    })}
                    selectedSort={
                        postRevisionOptions.selectedRevision
                            ? {
                                  name: postRevisionOptions.selectedRevision.label,
                                  value: postRevisionOptions.selectedRevision.value,
                              }
                            : undefined
                    }
                    onChange={(revision) => {
                        postRevisionOptions.setSelectedRevisionValue(revision.value);
                    }}
                />
            ) : (
                <span>
                    {t("Revision: ")}
                    <strong>{postRevisionOptions.options[0]?.label ?? t("Live")}</strong>
                </span>
            )}
        </span>
    );
}
