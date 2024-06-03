import React, { Fragment } from 'react';
import { neos } from '@neos-project/neos-ui-decorators';
import style from './style.module.css';

const neosifier = neos((globalRegistry) => ({
    i18nRegistry: globalRegistry.get('i18n'),
}));

function Editor(props) {
    const { value, options, i18nRegistry } = props;

    const array = Object.entries(options)
        .map(([key, labelKey]) => {
            const itemValue = value[key];
            if (!itemValue) {
                return null;
            }
            const secondValue = key === 'duration' ? convertSeconds(itemValue) : null;
            return {
                label: i18nRegistry.translate(labelKey + '.label'),
                value: i18nRegistry.translate(labelKey + '.value', itemValue, [itemValue, secondValue]),
            };
        })
        .filter((value) => !!value);

    if (!array.length) {
        return <div>{i18nRegistry.translate('Jonnitto.PrettyEmbedHelper:NodeTypes.Mixin.Metadata:noMetadataSet')}</div>;
    }

    return (
        <dl className={style.infoView}>
            {array.map(({ label, value }) => (
                <Fragment>
                    <dt className={style.propertyLabel}>{label}</dt>
                    <dd className={style.propertyValue} dangerouslySetInnerHTML={{ __html: value }}></dd>
                </Fragment>
            ))}
        </dl>
    );
}

const convertSeconds = (duration) => {
    const twoDigits = (number) => `0${number}`.slice(-2);
    const hours = ~~(duration / 3600);
    const minutes = ~~((duration % 3600) / 60);
    const seconds = duration % 60;

    if (hours) {
        return `${hours}:${twoDigits(minutes)}:${twoDigits(seconds)}`;
    }
    if (minutes) {
        return `${minutes}:${twoDigits(seconds)}`;
    }
    return seconds;
};

export default neosifier(Editor);
