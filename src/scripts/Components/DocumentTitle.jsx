import React from 'react';
import PropTypes from 'prop-types';
import { getMeta } from "@core/application";
import classNames from 'classnames';
import {getUniqueID, IComponentID } from '@core/Interfaces/componentIDs';

/**
 * A component for displaying and setting the document title.
 *
 * This component can render a default title or a custom title depending on the usage.
 *
 * @example <caption>Render the title in the default h1</caption>
 * <DocumentTitle title="Page Title" />
 *
 * @example <caption>Render a custom title</caption>
 * <DocumentTitle title="Title Bar Title >
 *     <h1>Page Title</h1>
 * </DocumentTitle>
 */
export default class DocumentTitle extends React.Component<IComponentID> {
    static propTypes = {
        children: PropTypes.node,
        classNames: PropTypes.string,
        title: PropTypes.string,
    };

    constructor(props) {
        super(props);

        this.classes = classNames(
            'pageTitle',
            {[this.props.classNames]: this.props.classNames}
        );
    }

    componentDidMount() {
        document.title = this.getHeadTitle(this.props);
    }

    componentWillUpdate(nextProps) {
        document.title = this.getHeadTitle(nextProps);
    }

    getHeadTitle(props) {
        const siteTitle = getMeta('ui.siteName', '');
        const parts = [];

        if (props.title && props.title.length > 0) {
            parts.push(props.title);
        }
        if (siteTitle.length > 0 && siteTitle !== props.title) {
            parts.push(siteTitle);
        }

        return parts.join(' - ');
    }

    render() {
        if (this.props.children && this.props.children.length > 0) {
            return this.props.children;
        } else {
            return <h1 className={this.classes}>{this.props.title}</h1>;
        }
    }
}
