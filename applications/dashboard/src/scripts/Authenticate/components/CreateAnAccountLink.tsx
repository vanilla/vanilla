import { t } from '@core/application';
import React from 'react';
import { Link } from 'react-router-dom';

export default function CreateAnAccountLink() {
    return <p className="authenticateUser-paragraph isCentered">{t('Not registered?')} <Link to="/entry/signup">{t('Create an Account')}</Link></p>;
}
