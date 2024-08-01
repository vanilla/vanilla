/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import TitleBar from "@library/headers/TitleBar";

interface IProps {
    status?: LoadStatus;
}

export default function PageLoaderWithTitleBar(props: React.PropsWithChildren<IProps>) {
    const { status = LoadStatus.LOADING } = props;
    if (status === LoadStatus.LOADING) {
        return (
            <>
                <TitleBar useMobileBackButton={false} />
                <Loader />
            </>
        );
    } else {
        return <>{props.children}</>;
    }
}
