/**
 * @typedef {Object} MessengerStreamSource
 * @property {string} id id of the source. will begin with `window:` if the source is a window, and with `screen:` if it is a screen
 * @property {string} name app/screen name
 * @property {?string} icon icon data as a data URL. `undefined` if the source is a screen.
 */

/**
 * @typedef {Object} MessengerState
 * @property {?string} userToken
 * @property {?number} voiceChannelState
 * @property {Set<string>} usersInVoiceChannel
 * @property {?(Object[])} messages
 */

const __messenger_iceConfiguration = {'iceServers': [{'urls': 'stun:stun.l.google.com:19302'}]};
const __messenger_sfxPlayer = new Audio();

/**
 * @param name {"vc_join"|"vc_leave"|"stream_start"|"stream_end"}
 * @private
 */
function __messenger_playSoundEffect(name) {
    __messenger_sfxPlayer.src = `/assets/sfx/${name}.mp3`;
    __messenger_sfxPlayer.currentTime = 0;
    __messenger_sfxPlayer.play();
}

class VoiceChannelState {
    static Connecting = 1;
    static Connected = 2;
}

class MessengerComponent extends StatefulComponent {
    realtimeConnection;

    /** @type {Object<string, RTCPeerConnection>} */
    peerConnections = {};

    /** @type {Object<string, HTMLAudioElement>} */
    players = {};

    /**
     * @param placeholderId {string}
     * @param groupId {number}
     */
    constructor(placeholderId, groupId) {
        /** @type {MessengerState} */
        const initialState = {
            userToken: null,
            voiceChannelState: null,
            usersInVoiceChannel: new Set(),
            messages: null
        };
        super(placeholderId, {groupId}, initialState);
        this.postRender = () => {
            $("#join-voice-channel-btn").on("click", async () => {
                await this._connectToVoiceChannel();
            });

            $("#voice-channel-disconnect-btn").on("click", async () => {
                await this.disconnectFromVoiceChannel();
            });

            $("#share-screen-btn").on("click", async () => {
                if (IS_ELECTRON_APP) {
                    const $chooseStreamSourceModalSourcesList = $("#choose-stream-source-modal-sources-list");

                    $chooseStreamSourceModalSourcesList.html("");
                    $("#choose-stream-source-modal").modal("show");

                    // this method is available by exposing an internal API from the Main process in electron to the
                    // renderer process.
                    // noinspection JSUnresolvedVariable,JSUnresolvedFunction
                    /** @type {MessengerStreamSource[]} */
                    const streamSources = await streamingApi.getStreamSources();

                    $chooseStreamSourceModalSourcesList.html(this._getSourcesList(streamSources));
                    $("#choose-stream-source-modal-sources-list li[data-sourceid]").on("click", event => {
                        const self = $(event.currentTarget);
                        const sourceId = self.attr("data-sourceid");
                        $("#choose-stream-source-modal").modal("hide");
                        Toast.show("twój stream zaraz się zacznie!", "clock", 2);
                    });
                } else {
                    alert("udostępnianie ekranu nie jest jeszcze wspierane w przeglądarce.");
                }
            });

            $(".voice-channel-user > img").on("error", function() {
                this.src = "/assets/img/no-pic.png";
            });
        };
    }

    render() {
        /** @type {MessengerState} */
        const state = this.state;

        if (!state.userToken) {
            return `<h1 style="font-family: 'Josefin Sans', sans-serif;">logowanie...</h1>`;
        }

        return [
            this._getLeftPanel(),
            `
                <div id="voice-channel-user-list-panel">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-volume" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M15 8a5 5 0 0 1 0 8"></path>
                            <path d="M17.7 5a9 9 0 0 1 0 14"></path>
                            <path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a0.8 .8 0 0 1 1.5 .5v14a0.8 .8 0 0 1 -1.5 .5l-3.5 -4.5"></path>
                        </svg>
                        kanał głosowy
                    </h3>
                    <ul class="list-unstyled">
            `,
            ...[...state.usersInVoiceChannel].map(username => `
                        <li class="voice-channel-user">
                            <img src="/cdn/pfp/${escapeHtml(username)}" />
                            ${username}
                        </li>
            `),
            `
                    </ul>
                    ${this._getVoiceChannelStateDisplay()}
                </div>
                <div class="modal fade" role="dialog" tabindex="-1" id="choose-stream-source-modal">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">udostępnianie ekranu</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>wybierz źródło obrazu, aby rozpocząć udostępnianie na tym kanale głosowym.</p>
                                <ul id="choose-stream-source-modal-sources-list" class="list-unstyled"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            `
        ];
    }

    /**
     * @param token {string}
     */
    login(token) {
        this.realtimeConnection = io("https://placki-socket.herokuapp.com", {
            auth: { token }
        });
        this.realtimeConnection.on("vc:userJoined", username => {
            this.modifyStateField(
                "usersInVoiceChannel",
                currentValue => {
                    currentValue.add(username);

                    return currentValue;
                }
            );

            if (this.state.voiceChannelState === VoiceChannelState.Connected) {
                __messenger_playSoundEffect("vc_join");
            }
        });
        this.realtimeConnection.on("vc:userLeft", (socketId, username) => {
            this.modifyStateField(
                "usersInVoiceChannel",
                currentValue => {
                    if (socketId in this.peerConnections) {
                        this.peerConnections[socketId].close();
                        delete this.peerConnections[socketId];
                        this.players[socketId].pause();
                        delete this.players[socketId];
                    }

                    currentValue.delete(username);

                    return currentValue;
                }
            );

            if (this.state.voiceChannelState === VoiceChannelState.Connected) {
                __messenger_playSoundEffect("vc_leave");
            }
        });
        this.realtimeConnection.emit("vc:users", GROUP_ID, usernames => {
            this.setStateField("userToken", token);
            this.setStateField("usersInVoiceChannel", new Set(usernames));
        });
    }

    /**
     * @param state {?number}
     */
    changeVoiceChannelState(state) {
        this.setStateField("voiceChannelState", state);
    }

    /**
     * @param socketId {string}
     * @param localStream {MediaStream}
     * @returns {RTCPeerConnection}
     * @private
     */
    _handleNewUserInVoiceChannel(socketId, localStream) {
        const peerConnection = new RTCPeerConnection(__messenger_iceConfiguration);
        this.peerConnections[socketId] = peerConnection;

        peerConnection.addEventListener("icecandidate", ev => {
            if (ev.candidate) {
                this.realtimeConnection.emit("vc:newICECandidate", socketId, ev.candidate);
            }
        });
        peerConnection.addEventListener("track", ev => {
            const [mediaStream] = ev.streams;
            const audioPlayer = new Audio();
            audioPlayer.srcObject = mediaStream;
            audioPlayer.play();
            this.players[socketId] = audioPlayer;
        });

        localStream.getTracks().forEach(track => void peerConnection.addTrack(track, localStream));

        return peerConnection;
    }

    /**
     * @returns {Promise<void>}
     * @private
     */
    async _connectToVoiceChannel() {
        this.changeVoiceChannelState(VoiceChannelState.Connecting);

        this.realtimeConnection.emit("vc:join", GROUP_ID, async status_ => {
            /** @type {{success: boolean, sockets: ?(string[])}} */
            const status = status_;

            if (!status.success) {
                Toast.show("nie udało się połączyć", "alert", 2);
                this.changeVoiceChannelState(null);
                return;
            }

            const localStream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false
            });

            const getPeer = socketId => this.peerConnections[socketId];

            this.realtimeConnection.on("vc:RTCAnswer", async (socketId, answer) => {
                const remoteDesc = new RTCSessionDescription(answer);
                await getPeer(socketId).setRemoteDescription(remoteDesc);
            });
            this.realtimeConnection.on("vc:RTCOffer", async (socketId, offer) => {
                let peerConnection = getPeer(socketId);

                if (!peerConnection) {
                    peerConnection = this._handleNewUserInVoiceChannel(socketId, localStream);
                }

                await peerConnection.setRemoteDescription(offer);
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);
                this.realtimeConnection.emit("vc:RTCAnswer", socketId, answer);
            });
            this.realtimeConnection.on("vc:newICECandidate", async (socketId, candidate) => {
                await getPeer(socketId).addIceCandidate(candidate);
            });

            if (status.sockets.length > 0) {
                for (const socketId of status.sockets) {
                    const peerConnection = this._handleNewUserInVoiceChannel(socketId, localStream);
                    const offer = await peerConnection.createOffer();
                    await peerConnection.setLocalDescription(offer);

                    peerConnection.addEventListener("connectionstatechange", () => {
                        if (peerConnection.connectionState === "connected") {
                            this.changeVoiceChannelState(VoiceChannelState.Connected);
                        }
                    });

                    this.realtimeConnection.emit(`vc:RTCOffer`, socketId, offer);
                }
            } else {
                this.changeVoiceChannelState(VoiceChannelState.Connected);
            }

            __messenger_playSoundEffect("vc_join");
        });
    }

    /**
     * @returns {Promise<void>}
     */
    async disconnectFromVoiceChannel() {
        for (const [ socketId, peerConnection ] of Object.entries(this.peerConnections)) {
            peerConnection.close();
            delete this.peerConnections[socketId];
        }

        for (const [ socketId, audioPlayer ] of Object.entries(this.players)) {
            audioPlayer.pause();
            delete this.players[socketId];
        }

        this.realtimeConnection.emit("vc:leave");
        this.changeVoiceChannelState(null);
        __messenger_playSoundEffect("vc_leave");
    }

    /**
     * @param sourceId {string}
     * @returns {Promise<void>}
     * @private
     */
    async _stream(sourceId) {

    }

    /**
     * @private
     * @returns {string}
     */
    _getLeftPanel() {
        switch (this.state.voiceChannelState) {
            case null:
                return `
                    <div id="text-channel-panel">
                        <ul class="list-unstyled">
                        
                        </ul>
                    </div>
                `;
            case VoiceChannelState.Connecting:
                return `
                    <div id="voice-channel-main-panel">
                        <label style="font-family: 'Josefin Sans', sans-serif; font-weight: bold; font-size: 50px; margin-left: 25px; animation: blinker 2s linear infinite;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-phone-calling" width="60" height="60" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="position: relative; top: -5px;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"></path>
                                <line x1="15" y1="7" x2="15" y2="7.01"></line>
                                <line x1="18" y1="7" x2="18" y2="7.01"></line>
                                <line x1="21" y1="7" x2="21" y2="7.01"></line>
                            </svg>
                            łączenie z kanałem głosowym...
                        </label>
                    </div>
                `
            case VoiceChannelState.Connected:
                return `
                    <div id="voice-channel-main-panel">
                        <span>foo bar</span>
                        <video id="stream-preview"></video>
                    </div>
                `;
        }
    }

    /**
     * @private
     * @returns {string}
     */
    _getVoiceChannelStateDisplay() {
        switch (this.state.voiceChannelState) {
            case null:
                return `<button id="join-voice-channel-btn" class="btn btn-primary">dołącz do kanału</button>`
            case VoiceChannelState.Connecting:
                return `<button class="btn btn-primary" disabled>łączenie...</button>`
            case VoiceChannelState.Connected:
                return `
                    <div id="voice-channel-state-display">
                        <div style="width: 100%; display: flex; flex-direction: row; align-items: center;">
                            <label style="font-family: 'Josefin Sans', sans-serif; font-weight: bold; color: #4CAF50;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-antenna-bars-5" width="1.25em" height="1.25em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <line x1="6" y1="18" x2="6" y2="15"></line>
                                    <line x1="10" y1="18" x2="10" y2="12"></line>
                                    <line x1="14" y1="18" x2="14" y2="9"></line>
                                    <line x1="18" y1="18" x2="18" y2="6"></line>
                                </svg>
                                połączono z kanałem głosowym
                            </label>
                            <button id="voice-channel-disconnect-btn" class="btn btn-danger btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-phone-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <line x1="3" y1="21" x2="21" y2="3"></line>
                                    <path d="M5.831 14.161a15.946 15.946 0 0 1 -2.831 -8.161a2 2 0 0 1 2 -2h4l2 5l-2.5 1.5c.108 .22 .223 .435 .345 .645m1.751 2.277c.843 .84 1.822 1.544 2.904 2.078l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a15.963 15.963 0 0 1 -10.344 -4.657"></path>
                                </svg>
                            </button>
                        </div>
                        <div style="width: 100%; display: flex; flex-direction: row; margin-top: 5px;">
                            <button class="btn btn-secondary" style="flex-grow: 1; margin-right: 3px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-computer-camera" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <circle cx="12" cy="10" r="7"></circle>
                                    <circle cx="12" cy="10" r="3"></circle>
                                    <path d="M8 16l-2.091 3.486a1 1 0 0 0 .857 1.514h10.468a1 1 0 0 0 .857 -1.514l-2.091 -3.486"></path>
                                </svg>
                                wideo
                            </button>
                            <button id="share-screen-btn" class="btn btn-secondary" style="flex-grow: 1; margin-left: 3px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-screen-share" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M21 12v3a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1v-10a1 1 0 0 1 1 -1h9"></path>
                                    <line x1="7" y1="20" x2="17" y2="20"></line>
                                    <line x1="9" y1="16" x2="9" y2="20"></line>
                                    <line x1="15" y1="16" x2="15" y2="20"></line>
                                    <path d="M17 4h4v4"></path>
                                    <path d="M16 9l5 -5"></path>
                                </svg>
                                ekran
                            </button>
                        </div>
                    </div>
                `
        }
    }

    /**
     * @param {MessengerStreamSource[]} sources
     * @returns {string}
     * @private
     */
    _getSourcesList(sources) {
        return sources.map(source => {
            let imageElement = `<img src="${source.icon}" />`

            if (source.id.startsWith("window:") && source.icon.endsWith("base64,")) {
                imageElement = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-app-window" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="M6 8h.01"></path>
                        <path d="M9 8h.01"></path>
                    </svg>
                `;
            } else if (source.id.startsWith("screen:")) {
                imageElement = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-tv" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <rect x="3" y="7" width="18" height="13" rx="2"></rect>
                        <polyline points="16 3 12 7 8 3"></polyline>
                    </svg>
                `;
            }

            return `
                <li class="d-flex" data-sourceid="${source.id}">
                    ${imageElement}
                    <p>${escapeHtml(source.name)}</p>
                </li>
            `;
        }).join("");
    }
}