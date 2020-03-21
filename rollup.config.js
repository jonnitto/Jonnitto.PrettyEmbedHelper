import babel from 'rollup-plugin-babel';
import { terser } from 'rollup-plugin-terser';
import composer from './composer.json';

const AUTHOR = composer.authors[0].name;
const BANNER_CONTENT = `/*!
 * ${composer.extra.neos['package-key']} - created by ${AUTHOR}
 * @link ${composer.homepage}
 * Copyright 2019-${parseInt(new Date().getFullYear(), 10)} ${AUTHOR}
 * Licensed under ${composer.license}
 */`;

export default [
    {
        input: 'Resources/Private/Assets/Main.js',
        plugins: [babel(), terser()],
        output: {
            banner: BANNER_CONTENT,
            sourcemap: true,
            file: 'Resources/Public/Scripts/Main.js',
            format: 'iife'
        }
    },
    {
        input: 'Resources/Private/Assets/Backend.js',
        plugins: [babel(), terser()],
        output: {
            banner: BANNER_CONTENT,
            sourcemap: true,
            file: 'Resources/Public/Scripts/Backend.js',
            format: 'iife'
        }
    }
];
