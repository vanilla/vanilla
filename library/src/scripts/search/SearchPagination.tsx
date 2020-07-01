/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import classNames from "classnames";
import * as React from "react";
import { simplePagerClasses } from "@library/navigation/simplePagerStyles";
import ConditionalWrap from "@library/layout/ConditionalWrap";

interface IProps {
    onNextClick?: React.MouseEventHandler;
    onPreviousClick?: React.MouseEventHandler;
}

/**
 * Previous/next pagination for search results.
 */
export function SearchPagination(props: IProps) {
    const { onNextClick, onPreviousClick } = props;

    const isSingle = (onNextClick && !onPreviousClick) || (!onNextClick && onPreviousClick);
    const classes = simplePagerClasses();
    return (
        <ConditionalWrap className={classes.root} condition={!!onPreviousClick || !!onNextClick}>
            {onPreviousClick && (
                <Button className={classNames(classes.button, { isSingle })} onClick={onPreviousClick}>
                    {t("Previous")}
                </Button>
            )}
            {onNextClick && (
                <Button className={classNames(classes.button, { isSingle })} onClick={onNextClick}>
                    {t("Next")}
                </Button>
            )}
        </ConditionalWrap>
    );
}
