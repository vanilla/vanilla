/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useMemo } from "react";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useQuery } from "@tanstack/react-query";
import { ITag } from "@library/features/tags/TagsReducer";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import sortBy from "lodash-es/sortBy";
import { IGroupOption } from "@library/forms/select/Tokens.loadable";
import { getMeta, t } from "@library/utility/appUtils";
import { useConfigsByKeys } from "@library/config/configHooks";
import { LoadStatus } from "@library/@types/api/core";

/**
 * Get a list of User defined tags as combo box options sorted alphabetically
 */
export function useTagOptions(): IComboBoxOption[] {
    const { isSuccess, data } = useQuery<any, IError, ITag[]>({
        queryKey: ["tags", { type: "User" }],
        queryFn: async ({ queryKey }) => {
            const [_, query] = queryKey;
            const { data } = await apiv2.get("/tags", { params: query });
            return sortBy(data, [(tag) => tag.name.toLowerCase()]);
        },
    });

    const options = useMemo<IComboBoxOption[]>(() => {
        if (isSuccess) {
            return data.map(
                ({ tagID, name }) =>
                    ({
                        value: tagID.toString(),
                        label: name,
                    } as IComboBoxOption),
            );
        }
        return [];
    }, [isSuccess, data]);

    return options;
}

export function useTypeOptions(): IComboBoxOption[] {
    /**
     * Get a list of discussions types as combo box options and display types if addon is enabled
     */
    const PLUGIN_LIST = {
        "plugins.qna": { label: t("Question"), value: "Question" },
        "plugins.ideation": { label: t("Idea"), value: "Idea" },
        "plugins.polls": { label: t("Poll"), value: "Poll" },
    };

    const { status, data }: { status: LoadStatus; data?: Record<string, boolean> } = useConfigsByKeys(
        Object.keys(PLUGIN_LIST),
    );

    const options = useMemo<IComboBoxOption[]>(() => {
        const tmpList: IComboBoxOption[] = [{ label: t("Discussion"), value: "Discussion" }];

        if (status === LoadStatus.SUCCESS && data) {
            Object.entries(PLUGIN_LIST).forEach(([key, option]) => {
                if (data[key]) {
                    tmpList.push(option);
                }
            });
        }

        return tmpList;
    }, [status, data]);

    return options;
}

/**
 * Get a list of discussion statuses as combo box options
 */
export function useStatusOptions(internal?: boolean): IGroupOption[] | IComboBoxOption[] {
    const query = useQuery<any, IError, Array<Record<string, any>>>({
        queryKey: ["discussionStatuses"],
        queryFn: async () => {
            const { data } = await apiv2.get("/discussions/statuses");
            return data;
        },
    });

    const { data, isSuccess } = query;

    const options = useMemo<IGroupOption[] | IComboBoxOption[]>(() => {
        if (isSuccess) {
            const statusMap: Record<string, any> = {
                question: {
                    label: t("Q & A"),
                    options: [],
                },
                ideation: {
                    label: t("Ideas"),
                    options: [],
                },
                internal: {
                    label: t("Resolution Status"),
                    options: [],
                },
            };

            data.forEach(({ statusID, name, recordSubtype, isInternal }) => {
                if (statusID > 0) {
                    statusMap[isInternal ? "internal" : recordSubtype].options.push({
                        value: statusID,
                        label: t(name),
                    });
                }
            });

            // remove "Rejected" status from Q & A list
            statusMap.question.options = statusMap.question.options.filter(({ label }) => label !== "Rejected");

            // Sort ideation options alphabetically
            statusMap.ideation.options = sortBy(statusMap.ideation.options, ({ label }) => label.toLowerCase());

            if (internal) {
                return statusMap.internal.options;
            }

            return [statusMap.question, statusMap.ideation].filter((group) => group.options.length > 0);
        }

        return [];
    }, [isSuccess, data, internal]);

    return options;
}
