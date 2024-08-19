export default class GoaEvent {

	static callbacks = {}
	static subscribeKeyIndex = 0;

	static trigger(key) {
		if (GoaEvent.callbacks[key])
		{
			Object.keys(GoaEvent.callbacks[key]).forEach(x => GoaEvent.callbacks[key][x]());
		}
	}


	static clear(key) {
		delete GoaEvent.callbacks[key];
	}

	static subscribe(key, callback) {
		let subscribeKey = GoaEvent.subscribeKeyIndex++;
		if (!GoaEvent.callbacks[key]) GoaEvent.callbacks[key] = {};
		GoaEvent.callbacks[key][subscribeKey] = callback;
		return subscribeKey;
	}

	static unsubscribe(key, subscribeKey) {
		if (GoaEvent.callbacks[key]) delete GoaEvent.callbacks[key][subscribeKey];
	}
}

