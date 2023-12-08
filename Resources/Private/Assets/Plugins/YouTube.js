import { eventName, playClass, loadYoutubeApi } from './Helper';

export default function (Alpine) {
    Alpine.directive('prettyembedyoutube', (element, { modifiers, expression }, { evaluate }) => {
        const style = modifiers.includes('lightbox') ? 'lightbox' : 'inline';
        const slim = modifiers.includes('slim');
        const options = { slim, style, ...evaluate(expression || '{}') };
        handeleRoot({ element, Alpine, options });
    });
}

function handeleRoot({ element, Alpine, options }) {
    const type = 'YouTube';
    let { videoId, playlistId, style, slim, loop } = options;

    const videoPlayerOptions = {
        playerVars: {
            autoplay: 1,
            modestbranding: 1,
            playsinline: 1,
            rel: 0,
            showinfo: 0,
            controls: slim ? 0 : 1,
            loop: loop ? 1 : 0,
        },
    };

    if (videoId) {
        videoPlayerOptions.videoId = videoId;
    }

    if (playlistId) {
        if (!playlistId.startsWith('PL')) {
            playlistId = 'PL' + playlistId;
        }
        videoPlayerOptions.playerVars.listType = 'playlist';
        videoPlayerOptions.playerVars.list = playlistId;
    }

    const localStorage = window.localStorage;
    const storageKey = `jonnittoprettyembed_gdpr_${type.toLowerCase()}`;

    const data = {
        title: options.title,
        loaded: false,
        playing: false,
        lightbox: style === 'lightbox' ? false : null,
        // gdpr can be 'isAccepted', 'isOpen' or 'needCheck'
        gdpr: options.gdpr && localStorage.getItem(storageKey) !== 'true' ? 'needCheck' : 'isAccepted',
    };

    let player = null;

    Alpine.bind(element, {
        ':class'() {
            return this.playing && playClass;
        },
        'x-data'() {
            return {
                ...data,
                init() {
                    if (this.ligtbox !== null) {
                        this.$watch('lightbox', (value, oldValue) => {
                            if (!value && value !== oldValue) {
                                this.pause();
                            }
                        });
                    }
                },
                acceptGdpr() {
                    localStorage.setItem(storageKey, 'true');
                    this.$dispatch('prettyEmbedAcceptGdpr', type);
                    this.load();
                },
                play() {
                    player?.playVideo();
                },
                pause() {
                    player?.pauseVideo();
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
                    loadYoutubeApi(() => {
                        const target = this.$refs.youtube || element;
                        const dispatchDetails = () => {
                            const videoUrl = player.getVideoUrl();
                            const { title, author, video_id, video_quality, list } = player.getVideoData();
                            return {
                                type,
                                style,
                                title,
                                author,
                                videoUrl,
                                videoId: video_id,
                                quality: video_quality,
                                playlistId: list || null,
                            };
                        };
                        player = new YT.Player(target, {
                            ...videoPlayerOptions,
                            events: {
                                onStateChange: ({ data }) => {
                                    const currentTime = player.getCurrentTime();

                                    if (data === YT.PlayerState.PLAYING) {
                                        this.$dispatch('prettyEmbedPause', element);
                                        this.loaded = true;
                                        this.playing = true;

                                        this.$dispatch(eventName, {
                                            event: 'play',
                                            currentTime,
                                            ...dispatchDetails(),
                                        });
                                        return;
                                    }

                                    if (data === YT.PlayerState.PAUSED) {
                                        this.playing = false;
                                        this.$dispatch(eventName, {
                                            event: 'pause',
                                            currentTime,
                                            ...dispatchDetails(),
                                        });
                                        return;
                                    }

                                    if (data === YT.PlayerState.ENDED) {
                                        this.playing = false;
                                        this.$dispatch(eventName, {
                                            event: 'finished',
                                            currentTime,
                                            ...dispatchDetails(),
                                        });
                                        return;
                                    }
                                },
                            },
                        });
                    });
                },
                reset() {
                    if (!player) {
                        return;
                    }
                    player?.stopVideo();
                    player.destroy();
                    player = null;
                    this.loaded = false;
                    this.playing = false;
                },
            };
        },
        '@prettyEmbedAcceptGdpr.window'({ detail }) {
            // detail is the type who is accepted
            if (detail == type) {
                this.gdpr = 'isAccepted';
            }
        },
        '@prettyEmbedReset.window'() {
            this.reset();
        },
        '@prettyEmbedPause.window'({ detail }) {
            // detail is the rootElement
            if (detail == this.$root) {
                return;
            }
            this.pause();
        },
    });
}
