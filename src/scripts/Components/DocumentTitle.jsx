import classNames from 'classnames';
import { getMeta } from "@core/application";
import PropTypes from 'prop-types';
import React from 'react';

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
export default class DocumentTitle extends React.Component {
    static propTypes = {
        children: PropTypes.node,
        classNames: PropTypes.string,
        id: PropTypes.string,
        title: PropTypes.string,
    };

    constructor(props) {
        super(props);
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
        this.classes = classNames(
            'pageTitle',
            {[this.props.classNames]: this.props.classNames}
        );

        if (this.props.children && this.props.children.length > 0) {
            return this.props.children;
        } else {
            return <h1 id={this.props.id} className={this.classes}>{this.props.title}</h1>;
        }
    }
}
