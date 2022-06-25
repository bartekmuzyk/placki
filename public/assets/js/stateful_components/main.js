/**
 * @typedef {Object<string, any>} Props
 */

class StatefulComponent {
    _state;
    /** @type {JQuery<HTMLElement>} */ _placeholder;

    /** @type {Props} */ props;

    /** @type {() => any} */
    postRender = () => undefined;

    /**
     * @param placeholderId {string}
     * @param props {Props}
     * @param initialState {any}
     */
    constructor(placeholderId, props = {}, initialState = null) {
        this._state = initialState;
        this.props = props;
        this._placeholder = $(`#${placeholderId}`);
    }

    set state(value) {
        this._state = value;
        this.reRender();
    }

    /**
     * @param modifier {(currentState: any) => void}
     */
    modifyState(modifier) {
        this.state = modifier(this.state);
    }

    get state() {
        return this._state;
    }

    /**
     * @returns {string|string[]} rendered component HTML or array of HTML strings, which will be concatenated
     */
    render() {
        throw new Error("render method not implemented");
    }

    reRender() {
        const rendered = this.render();
        this._placeholder.html(Array.isArray(rendered) ? rendered.join("") : rendered);
        this.postRender();
    }
}