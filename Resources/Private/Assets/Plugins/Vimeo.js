import { loadVimeoApi } from './Helper';

const eventName = 'prettyembed';

export default function (Alpine) {
    Alpine.directive('prettyembedvimeo', (element, { modifiers, expression }, { evaluate }) => {
        const style = modifiers.includes('lightbox') ? 'lightbox' : 'inline';
        const slim = modifiers.includes('slim');
        const options = { style, slim, ...evaluate(expression || '{}') };
        handeleRoot({ element, Alpine, options });
    });
}

function handeleRoot({ element, Alpine, options }) {
    const type = 'Vimeo';
    const { style, slim, video, color, loop, gdpr, background } = options;
    const videoPlayerOptions = {
        id: video,
        autopip: true,
        autoplay: true,
        pip: true,
        portrait: false,
        responsive: false,
        title: false,
        byline: false,
        background: !!background,
        loop: !!loop,
        autopause: false,
        controls: !slim,
    };
    if (color) {
        videoPlayerOptions.color = color;
    }
    const localStorage = window.localStorage;
    const storageKey = `jonnittoprettyembed_gdpr_${type.toLowerCase()}`;

    const data = {
        loaded: false,
        playing: false,
        lightbox: style === 'lightbox' ? false : null,
        // gdpr can be 'isAccepted', 'isOpen' or 'needCheck'
        gdpr: gdpr && localStorage.getItem(storageKey) !== 'true' ? 'needCheck' : 'isAccepted',
    };

    let player = null;

    Alpine.bind(element, {
        'x-data'() {
            return {
                ...data,
                init() {
                    if (this.lightbox !== null) {
                        this.$watch('lightbox', (value, oldValue) => {
                            if (!value && value !== oldValue) {
                                this.pause();
                            }
                        });
                    }
                },
                acceptGdpr() {
                    localStorage.setItem(storageKey, 'true');
                    this.$dispatch('prettyembedAcceptGdpr', type);
                    this.load();
                },
                play() {
                    player?.play();
                },
                pause() {
                    player?.pause();
                },
                load(event) {
                    if (event) {
                        event.preventDefault();
                    }
                    if (this.gdpr != 'isAccepted') {
                        this.gdpr = 'isOpen';
                        return;
                    }
                    if (this.lightbox !== null) {
                        this.lightbox = true;
                    }
                    if (this.loaded) {
                        this.play();
                        return;
                    }
                    loadVimeoApi(() => {
                        const target = this.$refs?.vimeo || element;
                        player = new Vimeo.Player(target, videoPlayerOptions);

                        const dispatchDetails = async (event) => {
                            const promises = [
                                player.getVideoTitle(),
                                player.getVideoId(),
                                player.getVideoUrl(),
                                player.getCurrentTime(),
                                player.getEnded(),
                            ];
                            const results = await Promise.allSettled(promises);

                            if (results[4].value) {
                                event = 'finished';
                            }

                            return {
                                event,
                                type,
                                style,
                                title: results[0].value,
                                videoID: results[1].value,
                                videoUrl: results[2].value,
                                currentTime: results[3].value,
                            };
                        };

                        player.on('play', async () => {
                            const details = await dispatchDetails('play');
                            this.$dispatch('prettyembedPause', element);
                            this.loaded = true;
                            this.playing = true;
                            this.$dispatch(eventName, details);
                        });
                        player.on('pause', async () => {
                            const details = await dispatchDetails('pause');
                            this.playing = false;
                            this.$dispatch(eventName, details);
                        });
                    });
                },
                async reset() {
                    if (!player) {
                        return;
                    }
                    await this.pause();
                    await player.destroy();
                    player = null;
                    this.loaded = false;
                    this.playing = false;
                },
            };
        },
        '@prettyembedAcceptGdpr.window'({ detail }) {
            // detail is the type who is accepted
            if (detail == type) {
                this.gdpr = 'isAccepted';
            }
        },
        '@prettyembedReset.window'() {
            this.reset();
        },
        '@prettyembedPause.window'({ detail }) {
            // detail is the rootElement
            if (detail == this.$root) {
                return;
            }
            this.pause();
        },
    });
}
