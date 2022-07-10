/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LoadingRectangle, LoadingCircle } from "@library/loaders/LoadingRectangle";
import { searchResultClasses } from "@library/features/search/searchResultsStyles";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem } from "@library/metas/Metas";

export default function CollapseCommentsSearchMetaLoader() {
    const classes = searchResultClasses();
    return (
        <ListItem
            as="div"
            name={<LoadingRectangle width={300} />}
            icon={<LoadingCircle height={26} />}
            iconWrapperClass={classes.iconWrap}
            metas={
                <>
                    <MetaItem>
                        <LoadingRectangle width={230} />
                    </MetaItem>
                    <MetaItem>
                        <LoadingRectangle width={50} />
                    </MetaItem>
                </>
            }
        />
    );
}
