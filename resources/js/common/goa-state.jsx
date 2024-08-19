export default class GoaState {

	static states = {};
	static statesChangeCallbacks = {}
	static subscribeKeyIndex = 0;


	static set(key, state) {
		if (!GoaState.states[key]) GoaState.states[key] = {};
		GoaState.states[key] = {...GoaState.states[key], ...state};
		if (GoaState.statesChangeCallbacks[key])
		{
			Object.keys(GoaState.statesChangeCallbacks[key]).forEach(x => GoaState.statesChangeCallbacks[key][x](GoaState.states[key]));
		}
	}

	static get(key) {
		return GoaState.states[key] ? GoaState.states[key] : undefined;
	}

	static empty(key) {
		GoaState.states[key] = {};
		this.set(key, {});
	}

	static clear(key) {
		delete GoaState.states[key];
		delete GoaState.statesChangeCallbacks[key];
	}

	static subscribe(key, callback) {
		let subscribeKey = GoaState.subscribeKeyIndex++;
		if (!GoaState.statesChangeCallbacks[key]) GoaState.statesChangeCallbacks[key] = {};
		GoaState.statesChangeCallbacks[key][subscribeKey] = callback;
		return subscribeKey;
	}

	static unsubscribe(key, subscribeKey) {
		if (GoaState.statesChangeCallbacks[key]) delete GoaState.statesChangeCallbacks[key][subscribeKey];
	}
}

