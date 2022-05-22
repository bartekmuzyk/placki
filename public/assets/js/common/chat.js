class Chat {
    static _initialized = false;

    static get initialized() {
        return this._initialized;
    }

    static init() {
        // TODO Chat.init()
        this._initialized = true;
    }

    static open() {
        if (!this._initialized) {
            this.init();
        }

        Toast.show("funkcja czatu jeszcze nie jest dostępna", "alert", 2);
        // TODO Chat.open()
    }
}