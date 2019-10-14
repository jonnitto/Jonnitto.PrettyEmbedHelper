import fixPreviews from './Preview';

function backendFixPreview(event) {
    const NODE_TYPE = 'Jonnitto.PrettyEmbedYoutube:YouTube';
    try {
        const NODE = event.detail.node;
        if (
            (typeof NODE.get == 'function' &&
                NODE.get('nodeType') === NODE_TYPE) ||
            (typeof NODE.nodeType == 'string' && NODE.nodeType === NODE_TYPE)
        ) {
            fixPreviews();
        }
    } catch (error) {}
}

['NodeCreated', 'NodeSelected'].forEach(event => {
    document.addEventListener('Neos.' + event, backendFixPreview, false);
});

export default backendFixPreview;
