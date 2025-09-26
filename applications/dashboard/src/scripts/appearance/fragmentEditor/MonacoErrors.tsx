/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { Metas, MetaItem } from "@library/metas/Metas";
import type { MonacoError } from "@library/textEditor/MonacoUtils";
import { Icon } from "@vanilla/icons";

export function MonacoErrors(props: { errors: MonacoError[] }) {
    const { errors } = props;
    const classes = fragmentEditorClasses.useAsHook();
    return (
        <div className={classes.monacoErrors}>
            {errors.map((err, i) => {
                const owner = err.code != null ? `${err.owner}(${err.code})` : err.owner;
                return (
                    <div className={classes.errorRow} key={i}>
                        <Icon className={classes.errorIndicator} icon="status-alert" />

                        <div className={classes.errorText}>
                            <span>{err.message}</span>
                            <Metas>
                                <MetaItem>{owner}</MetaItem>
                                <MetaItem>
                                    [{err.startLineNumber}:{err.startColumn}]
                                </MetaItem>
                            </Metas>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
