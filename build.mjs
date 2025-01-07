import esbuild from 'esbuild';
import extensibilityMap from '@neos-project/neos-ui-extensibility/extensibilityMap.json' with { type: 'json' };
import { cssModules } from 'esbuild-plugin-lightningcss-modules';

const baseOptions = {
    logLevel: 'info',
    bundle: true,
    minify: process.argv.includes('--production'),
    sourcemap: !process.argv.includes('--production'),
    target: 'es2020',
    legalComments: 'none',
};

const scriptOptions = {
    ...baseOptions,
    entryPoints: ['Resources/Private/Assets/*.js'],
    outdir: 'Resources/Public/Scripts',
    format: 'iife',
};

const moduleOptions = {
    ...baseOptions,
    entryPoints: ['Resources/Private/Assets/*.mjs'],
    outdir: 'Resources/Public/Modules',
    format: 'esm',
    splitting: true,
};

const editorOptions = {
    ...baseOptions,
    entryPoints: { Metadata: 'Resources/Private/MetadataEditor/manifest.js' },
    outdir: 'Resources/Public/Plugin',
    format: 'iife',
    loader: {
        '.js': 'tsx',
        '.pcss': 'css',
    },
    alias: extensibilityMap,
    plugins: [
        cssModules({
            targets: {
                chrome: 80, // aligns somewhat to es2020
            },
            cssModules: {
                dashedIdents: true,
                pattern: 'jonnitto-prettyembed-[hash]-[local]',
            },
        }),
    ],
};

async function watch(options) {
    const context = await esbuild.context(options);
    await context.watch();
}

[scriptOptions, moduleOptions, editorOptions].forEach((options) => {
    process.argv.includes('--watch') ? watch(options) : esbuild.build(options);
});
