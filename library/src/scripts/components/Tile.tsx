/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import Heading from "@library/components/Heading";
import Paragraph from "@library/components/Paragraph";
import SmartLink from "@library/components/navigation/SmartLink";
import Translate from "@library/components/translation/Translate";

interface IProps {
    url: string;
    tag?: string; // Don't use "a"
    title: string;
    className?: string;
    description?: string;
    icon?: string; // url
    iconAltText?: string; // If you want alternative alt text, title is passed in
}

/**
 * Component representing a list of visible knowledge bases.
 **/
class Tile extends React.Component<IProps> {
    public static defaultProps = {
        tag: "div",
        iconAltText: 'Icon for "<0/>"',
    };

    public constructor(props) {
        super(props);
        if (props.tag === "a") {
            throw Error("You cannot use this tag for this component. A link will be generated");
        }
    }

    public render() {
        const { tag, url, className, title, description, icon } = this.props;
        const Tag = `${tag ? tag : "div"}`;
        const alt = `${<Translate source={this.props.iconAltText} c0={title} />}`;
        return (
            <Tag className={classNames("tile", className)}>
                <SmartLink to={url}>
                    {icon && (
                        <div className="tile-iconFrame">
                            <img src={icon} alt={alt} className="tile-iconFrame" />
                        </div>
                    )}
                    <Heading title={title} className={"tile-title"} />
                    <Paragraph className={"tile-description"}>{description}</Paragraph>
                </SmartLink>
            </Tag>
        );
    }
}
