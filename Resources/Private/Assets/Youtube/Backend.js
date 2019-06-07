import fixPreviews from './Preview';

function backendFixPreview(event) {
    const NODE_TYPE = 'Jonnitto.PrettyEmbedYoutube:YouTube';
    try {
        const node = event.detail.node;
        if (
            (typeof node.get == 'function' &&
                node.get('nodeType') === NODE_TYPE) ||
            (typeof node.nodeType == 'string' && node.nodeType === NODE_TYPE)
        ) {
            fixPreviews();
        }
    } catch (error) {}
}

['NodeCreated', 'NodeSelected'].forEach(event => {
    document.addEventListener('Neos.' + event, backendFixPreview, false);
});

export default backendFixPreview;
