/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import TitleBar from "@library/headers/TitleBar";

interface IProps {
    children?: React.ReactNode;
    status?: LoadStatus;
}

/**
 * A class for handling an ILoadable and display error, loading, or children.
 */
const PageLoader: React.FC<IProps> = (props: IProps) => {
    const { status = LoadStatus.LOADING } = props;
    if (status === LoadStatus.LOADING) {
        return <Loader />;
    } else {
        return <>{props.children}</>;
    }
};

export default PageLoader;
