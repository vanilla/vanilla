/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { BottomChevronIcon } from "@library/icons/common";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { StackedList } from "@library/stackedList/StackedList";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/comments/NestedComments.classes";
import type { IThreadItem } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { t } from "@vanilla/i18n";
import { debug } from "@vanilla/utils";
import { useState } from "react";

interface IHoleProps {
    threadItem: IThreadItem & { type: "hole" };
}

/**
 * Renders a comment thread hole with a button to load more comments
 */
export function NestedCommentHole(props: IHoleProps) {
    const { countAllComments, insertUsers, countAllInsertUsers, apiUrl, path } = props.threadItem;

    const { addToThread } = useNestedCommentContext();
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<IError[] | null>(null);

    const classes = nestCommentListClasses();

    const otherUserCount = countAllInsertUsers - insertUsers.length;

    return (
        <Button
            buttonType={ButtonTypes.CUSTOM}
            className={cx(classes.hole, "hole")}
            onClick={() => {
                setIsLoading(true);
                addToThread(apiUrl, path)
                    .catch((err) => {
                        setError(() => {
                            const payload: IError[] = [{ message: t("Something went wrong. Please try again.") }];
                            if (debug()) {
                                payload.push(err);
                            }
                            return payload;
                        });
                    })
                    .finally(() => {
                        setIsLoading(false);
                    });
            }}
        >
            {isLoading ? <ButtonLoader /> : <BottomChevronIcon />}
            <StackedList
                themingVariables={{
                    ...stackedListVariables("thread-participants"),
                    sizing: {
                        ...stackedListVariables("thread-participants").sizing,
                        width: userPhotoVariables().sizing.xsmall,
                        offset: 10,
                    },
                    plus: {
                        ...stackedListVariables("thread-participants").plus,
                        font: globalVariables().fontSizeAndWeightVars("medium"),
                    },
                }}
                data={insertUsers}
                maxCount={5}
                ItemComponent={(user) => <UserPhoto size={UserPhotoSize.XSMALL} userInfo={user} />}
            />
            <span>
                <Translate
                    source={"<0/> <1/> more comments"}
                    c0={`${otherUserCount > 1 ? `+ ${otherUserCount} others in` : ""}`}
                    c1={countAllComments}
                />
            </span>
            {!!error && <ErrorMessages className={classes.holeError} errors={error} />}
        </Button>
    );
}
