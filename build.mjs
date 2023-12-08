import esbuild from 'esbuild';

const options = {
    logLevel: 'info',
    bundle: true,
    minify: process.argv.includes('--production'),
    sourcemap: true,
    target: 'es2020',
    legalComments: 'none',
    entryPoints: ['Resources/Private/Assets/*.js'],
    outdir: 'Resources/Public/Scripts',
    format: 'iife',
};

async function watch(options) {
    const context = await esbuild.context(options);
    await context.watch();
}

if (process.argv.includes('--watch')) {
    watch(options);
} else {
    esbuild.build(options);
}
