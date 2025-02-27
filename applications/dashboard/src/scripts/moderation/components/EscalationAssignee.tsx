/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { css, cx } from "@emotion/css";
import apiv2 from "@library/apiv2";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useToast } from "@library/features/toaster/ToastContext";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import { inputClasses } from "@library/forms/inputStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import {
    AutoComplete,
    AutoCompleteLookupOptions,
    IAutoCompleteOption,
    ILookupApi,
} from "@vanilla/ui/src/forms/autoComplete";

interface IProps {
    escalation: IEscalation;
    className?: string;
    autoCompleteClasses?: string;
    inCard?: boolean;
}

const escalationAssigneeClasses = (inCard?: boolean) => ({
    root: css({
        display: "inline-flex",
        alignItems: "center",
        gap: 8,
        padding: "0px!important",
        marginLeft: -6,
        maxWidth: 200,
        position: "relative",
        transition: "border-color 230ms",
        ...(inCard && {
            borderColor: "transparent!important",
            "&:focus-within": {
                borderColor: "rgb(190,194,206)!important",
            },
        }),
    }),
    autoComplete: css({
        border: "none",
        height: 34,
        input: {
            height: 34,
            padding: 0,
            paddingLeft: 38,
            backgroundColor: "transparent",
            transition: "border-color 230ms",
            ...(inCard && {
                "& ~ div": {
                    opacity: 0,
                },
                "&:focus-within": {
                    "& ~ div": {
                        opacity: 1,
                    },
                },
            }),
        },
    }),
    //@ts-ignore-next-line
    photo: css({
        position: "absolute !important",
        top: "50%",
        left: 8,
        transform: "translateY(-50%)",
    }),
});

export function EscalationAssignee(props: IProps) {
    const { escalation } = props;
    const toast = useToast();

    const classes = escalationAssigneeClasses(props.inCard);

    const userLookup: ILookupApi = {
        searchUrl: `/api/v2/escalations/lookup-assignee?name=%s*&limit=10&escalationID=${escalation.escalationID}`,
        labelKey: "name",
        valueKey: "userID",
        singleUrl: "/api/v2/users/%s",
        processOptions: (options: IComboBoxOption[]) => {
            return options.map((option) => {
                return {
                    ...option,
                    data: {
                        ...option.data,
                        icon: option.data,
                    },
                };
            });
        },
        initialOptions:
            escalation.assignedUser && escalation.assignedUser.userID !== 0
                ? [
                      {
                          value: escalation.assignedUser.userID,
                          label: escalation.assignedUser.name,
                          data: { icon: escalation.assignedUser.photoUrl },
                      } as IAutoCompleteOption,
                  ]
                : undefined,
    };

    const queryClient = useQueryClient();
    const mutateEscalation = useMutation({
        mutationFn: async (assignedUserID: number) => {
            const response = await apiv2.patch(`/escalations/${escalation.escalationID}`, {
                assignedUserID,
            });
            return response.data;
        },
        onSuccess: () => {
            toast.addToast({
                autoDismiss: true,
                body: t("Assignee updated"),
            });
            void queryClient.invalidateQueries(["escalation"]);
            void queryClient.invalidateQueries(["escalations"]);
        },
    });

    return (
        <div className={cx(classes.root, inputClasses().inputText, props.className)}>
            <UserPhoto
                className={classes.photo}
                size={UserPhotoSize.XSMALL}
                userInfo={escalation.assignedUser ?? deletedUserFragment()}
            />
            <AutoComplete
                resetOnBlur={true}
                id={"assign-user"}
                value={escalation.assignedUserID === 0 ? -4 : escalation.assignedUserID}
                onChange={(assignedUserID) => {
                    mutateEscalation.mutate(assignedUserID);
                }}
                optionProvider={
                    <AutoCompleteLookupOptions
                        ignoreLookupOnMount={[null, -4, "-4"].includes(escalation.assignedUserID)}
                        lookup={userLookup}
                    />
                }
                options={[{ value: -4, label: "Unassigned", data: { icon: deletedUserFragment() } }]}
                className={cx(classes.autoComplete, props.autoCompleteClasses)}
                size={props.inCard ? "small" : "default"}
            />
        </div>
    );
}
