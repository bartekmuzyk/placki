/**
 * @typedef {Object} MessengerStreamSource
 * @property {string} id id of the source. will begin with `window:` if the source is a window, and with `screen:` if it is a screen
 * @property {string} name app/screen name
 * @property {?string} icon icon data as a data URL. `undefined` if the source is a screen.
 */

/**
 * @typedef {{from: string, stream: MediaStream}} MessengerStreamInfo
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

countdown.setLabels(
    " milisekundę| sekundę| minutę| godzinę| tydzień| miesiąc| rok| dekadę| wiek| milenium",
    " milisekund| sekund| minut| godzin| tygodni| miesięcy| lat| dekad| wieków| mileniów",
    " i ",
    ", ",
    "mniej niż sekundę"
);

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

    /** @type {?number} */
    callTimerId = null;

    /** @type {string} */
    callTimeString = "";

    /** @type {Object<string, string>} */
    _userInfo = {};

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
            messages: null,
            voiceChannelPanelExpanded: false
        };
        super(placeholderId, {groupId}, initialState);
        this.postRender = () => {
            $("#join-voice-channel-btn").on("click", async () => {
                await this._connectToVoiceChannel();
            });

            $("#voice-channel-disconnect-btn").on("click", async () => {
                await this.disconnectFromVoiceChannel();
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
            `
                <div id="messenger-left-panel">
            `,
            ...this._getVoiceChannelPanel(),
            `
                    <div id="text-channel-panel" style="display: ${state.voiceChannelPanelExpanded ? 'none' : 'initial'};">
                        <ul class="list-unstyled">
                            
                        </ul>
                    </div>
                </div>
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
                            <img src="/api/pfp?uzytkownik=${escapeHtml(username)}" />
                            ${escapeHtml(username)}
                        </li>
            `),
            `
                    </ul>
                    ${this._getVoiceChannelStateDisplay()}
                </div>
            `
        ];
    }

    /**
     * @param token {string}
     */
    login(token) {
        this.realtimeConnection = io(SOCKET_URL, {
            auth: { token }
        });
        this.realtimeConnection.on("vc:userJoined", (socketId, username) => {
            this._userInfo[socketId] = username;

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

            this.modifyStateField(
                "videoStreams",
                currentValue => {
                    /** @type {MessengerStreamInfo[]} */
                    const streams = currentValue;
                    let streamIndex = streams.findIndex(streamInfo => streamInfo.from === this._userInfo[socketId]);

                    while (streamIndex > -1) {
                        streams.splice(streamIndex, 1);
                        streamIndex = streams.findIndex(streamInfo => streamInfo.from === this._userInfo[socketId]);
                    }

                    return streams;
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
     * @param time {string}
     */
    setCallTimeString(time) {
        this.callTimeString = time;
        $("#call-time-display").text(time);
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

            if (mediaStream.getAudioTracks().length > 0) {
                const audioPlayer = new Audio();
                audioPlayer.srcObject = mediaStream;
                audioPlayer.play();
                this.players[socketId] = audioPlayer;
            } else if (mediaStream.getVideoTracks().length > 0) {
                this.modifyStateField("videoStreams", currentValue => [
                    ...currentValue,
                    {from: this._userInfo[socketId], stream: mediaStream}
                ]);
            }
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

            this.setCallTimeString("");

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

                if (!RTCPeerConnection.prototype.onconnectionstatechange) {
                    this.changeVoiceChannelState(VoiceChannelState.Connected);
                }
            } else {
                this.changeVoiceChannelState(VoiceChannelState.Connected);
            }

            this.callTimerId = countdown(
                new Date(),
                timestamp => this.setCallTimeString(timestamp.toString()),
                countdown.YEARS|countdown.MONTHS|countdown.DAYS|countdown.HOURS|countdown.MINUTES|countdown.SECONDS
            );

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
        clearInterval(this.callTimerId);
        __messenger_playSoundEffect("vc_leave");
    }

    /**
     * @private
     * @returns {string[]}
     */
    _getVoiceChannelPanel() {
        switch (this.state.voiceChannelState) {
            case VoiceChannelState.Connecting:
                return [`
                    <div id="voice-channel-panel-users">
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
                `];
            case VoiceChannelState.Connected:
                return [
                    `
                        <div id="voice-channel-panel-users">
                    `,
                    ...[...this.state.usersInVoiceChannel].map(username => `
                            <div>
                                <img src="/api/pfp?uzytkownik=${escapeHtml(username)}" />
                                <br/>
                                <label>${escapeHtml(username)}</label>
                            </div>
                    `),
                    `
                        </div>
                        <div id="voice-channel-panel-toolbar">
                            <div>
                                <label style="font-family: 'Josefin Sans', sans-serif; font-weight: bold;">
                                    jesteś połączony już <span style="font: inherit;" id="call-time-display"></span>
                                </label>
                            </div>
                        </div>
                    `
                ];
            default:
                return [``];
        }
    }

    /**
     * @private
     * @returns {string}
     */
    _getVoiceChannelStateDisplay() {
        switch (this.state.voiceChannelState) {
            case null:
                return `<button id="join-voice-channel-btn" class="btn btn-primary">dołącz do kanału</button>`;
            case VoiceChannelState.Connecting:
                return `<button class="btn btn-primary" disabled>łączenie...</button>`;
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
                    </div>
                `;
        }
    }
}