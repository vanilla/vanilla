/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useLayoutEffect } from "react";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";

interface IProps {
    children: React.ReactNode;
    status: LoadStatus;
}

/**
 * A class for handling an ILoadable and display error, loading, or children.
 */
const PageLoader: React.FC<IProps> = (props: IProps) => {
    const { status } = props;
    useLayoutEffect(() => {
        if (status === LoadStatus.LOADING) {
            document.body.classList.add("isLoading");
        } else {
            document.body.classList.remove("isLoading");
        }
    }, [status]);

    if (status === LoadStatus.LOADING) {
        return <Loader />;
    } else {
        return <>{props.children}</>;
    }
};

export default PageLoader;
