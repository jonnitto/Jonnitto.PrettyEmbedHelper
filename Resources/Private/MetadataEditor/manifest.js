import manifest from '@neos-project/neos-ui-extensibility';

import Editor from './Editor';

manifest('Jonnitto.PrettyEmbed:Metadata', {}, (globalRegistry) => {
    const editorsRegistry = globalRegistry.get('inspector').get('editors');

    editorsRegistry.set('Jonnitto.PrettyEmbed/Metadata', {
        component: Editor,
    });
});
