import { t } from '@core/application';
import React from 'react';
import { Link } from 'react-router-dom';

export default class CreateAnAccountLink extends React.Component {
    public render() {
        return <p className="authenticateUser-paragraph isCentered">{t('Not registered?')} <Link to="/authenticate/signup">{t('Create an Account')}</Link></p>;
    }
}
