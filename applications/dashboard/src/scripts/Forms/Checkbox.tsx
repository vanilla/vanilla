import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import { uniqueID, IComponentID } from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    className?: string;
    checked: boolean;
    disabled?: boolean;
    onChange: any;
    label: string;
}

export default class Button extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
    };
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueID(props, 'checkbox');
    }

    public render() {
        const componentClasses = classNames(
            'checkbox',
            this.props.className
        );

        return <label id={this.ID} className={componentClasses}>
            <input className="checkbox-input" type="checkbox" onChange={this.props.onChange} checked={this.props.checked}/>
            <span className="checkbox-box" aria-hidden="true">
                <span className="checkbox-state">
                    <svg className="checkbox-icon checkbox-checkIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">
                        <title>✓</title>
                        <path fill="currentColor" d="M10,2.7c0-0.2-0.1-0.3-0.2-0.4L8.9,1.3c-0.2-0.2-0.6-0.2-0.9,0L3.8,5.6L1.9,3.7c-0.2-0.2-0.6-0.2-0.9,0L0.2,4.6c-0.2,0.2-0.2,0.6,0,0.9l3.2,3.2c0.2,0.2,0.6,0.2,0.9,0l5.5-5.5C9.9,3,10,2.8,10,2.7z"></path>
                    </svg>
                </span>
            </span>
            <span className="checkbox-label">{this.props.label}</span>
        </label>;
    }
}
