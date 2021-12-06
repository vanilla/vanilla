import { ILoadable, LoadStatus } from "@library/@types/api/core";
import React, { ReactNode } from "react";
import { CoreErrorMessages, IAPIErrorFragment } from "@library/errorPages/CoreErrorMessages";
import Loader from "@library/loaders/Loader";

export function PageLoadStatus({
    loadable,
    children,
    loaderComponent = Loader,
}: {
    loadable: ILoadable<any, IAPIErrorFragment>;
    children: ReactNode;
    loaderComponent?: React.ComponentType;
}) {
    const LoaderComponent = loaderComponent;
    if (loadable.status === LoadStatus.ERROR) {
        return <CoreErrorMessages apiError={loadable.error} />;
    } else if (loadable.status === LoadStatus.SUCCESS && loadable.data !== undefined) {
        return <>{children}</>;
    } else {
        return <LoaderComponent />;
    }
}
