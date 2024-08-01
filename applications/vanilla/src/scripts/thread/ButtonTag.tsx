/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css, cx } from "@emotion/css";
import ActsAsCheckbox from "@library/forms/ActsAsCheckbox";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import { Icon } from "@vanilla/icons";

const layout = css({
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
    fontSize: "initial",
    borderRadius: 13,
});

export function ButtonTag({ onClick, title, isActive }) {
    return (
        <>
            <ActsAsCheckbox onChange={() => onClick()} title={title}>
                {() => (
                    <>
                        <Tag preset={isActive ? TagPreset.COLORED : TagPreset.PRIMARY} className={cx(layout)}>
                            {title} <Icon size="compact" icon={isActive ? "data-checked" : "data-add"} />
                        </Tag>
                    </>
                )}
            </ActsAsCheckbox>
        </>
    );
}
