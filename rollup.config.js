import babel from '@rollup/plugin-babel';
import terser from '@rollup/plugin-terser';
import resolve from '@rollup/plugin-node-resolve';

export default [
    {
        input: 'Resources/Private/Assets/Main.js',
        plugins: [
            babel({
                babelHelpers: 'bundled',
            }),
            terser(),
        ],
        output: {
            sourcemap: true,
            file: 'Resources/Public/Scripts/Main.js',
            format: 'iife',
        },
    },
    {
        input: 'Resources/Private/Assets/Hls.js',
        context: 'window',
        plugins: [
            resolve({
                exclude: 'node_modules/**',
                babelHelpers: 'bundled',
            }),
            babel({
                babelHelpers: 'bundled',
            }),
            terser(),
        ],
        output: {
            sourcemap: false,
            file: 'Resources/Public/Scripts/Hls.js',
            format: 'iife',
        },
    },
    {
        input: 'Resources/Private/Assets/Backend.js',
        plugins: [
            babel({
                babelHelpers: 'bundled',
            }),
            terser(),
        ],
        output: {
            sourcemap: true,
            file: 'Resources/Public/Scripts/Backend.js',
            format: 'iife',
        },
    },
];
