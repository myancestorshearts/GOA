
import GoaApi from './api.jsx';
import toastr from 'toastr';
import Storage from './storage';

export default class GoaUser {

    constructor() {
        this.user = Storage.get('goa-user');

        this.callbacks = {};
        this.subscribeKeyIndex = 0;

        this.preferences = undefined;

        GoaApi.User.preferences({}, success => {
            this.preferences = success.data.preferences;
        }, failure => {
            toastr.error('Failed to load preferences - reload page or logout and back in: ' + failure.message)
        })
    }

    loadUser(failureCallback) {
        GoaApi.User.get({}, success => {
            this.user = success.data.user;
            this.triggerCallbacks();
        }, failure => {
            if (failureCallback) failureCallback();
        })
    }

    updateUser(args) {
        this.user = { ...this.user, ...args }
        Storage.set('goa-user', this.user);
        this.triggerCallbacks();
    }

    refresh() {
        this.user = undefined;
        this.loadUser();
    }

    triggerCallbacks() {
        Object.keys(this.callbacks).forEach(key => this.callbacks[key](this.user));
    }

    subscribe(callback) {
        let key = ++this.subscribeKeyIndex;
        this.callbacks[key] = callback;
        return key;
    }

    unsubscribe(key) {
        if (this.callbacks[key]) delete this.callbacks[key]
    }

    save(successCallback, failureCallback) {
        GoaApi.User.set(this.user, success => {
            if (successCallback) successCallback(success);
            this.triggerCallbacks();
        }, failure => {
            if (failureCallback) failureCallback(failure);
        })
    }

    getPreference(key, successCallback, defaultValue = {}) {
        if (!this.preferences) {
            setTimeout(() => this.getPreference(key, successCallback, defaultValue), 100);
            return;
        }
        else if (this.preferences[key]) successCallback(JSON.parse(this.preferences[key]))
        else successCallback(defaultValue);
    }

    setPreference(key, value, successCallback, failureCallback) {
        let jsonValue = JSON.stringify(value);
        this.preferences[key] = jsonValue;
        GoaApi.User.setPreference({
            key: key,
            value: jsonValue
        }, successCallback, failureCallback);
    }
}



