/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

interface IParams<TProps extends {}> {
    loadFunction(): Promise<React.ComponentType<TProps> | { default: React.ComponentType<TProps> }>;
    fallback: React.ComponentType<{}>;
}

export type LoadableComponent<TProps extends {}> = {
    (props: TProps & JSX.IntrinsicAttributes): React.ReactElement;
    preload(): void;
};

/**
 * Create a wrapper for lazy loading a component and showing a loader while it is loading.
 */
export function createLoadableComponent<TProps extends {}>(params: IParams<TProps>) {
    const Lazy = React.lazy<React.ComponentType<TProps>>(() =>
        params.loadFunction().then((loaded) => ("default" in loaded ? loaded : { default: loaded })),
    );

    const NewComponent = (props: TProps) => {
        return (
            <React.Suspense fallback={<params.fallback />}>
                <Lazy {...props} />
            </React.Suspense>
        );
    };

    const result: LoadableComponent<TProps> = Object.assign(NewComponent, {
        preload: () => {
            void params.loadFunction();
        },
    });
    return result;
}
