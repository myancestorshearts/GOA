import Storage from './storage';

export default class WebClient {

	static basicDownload(parameters, callUrl, filename = 'export.csv') {
		WebClient.basicCall('Download', parameters, callUrl, () => { }, () => { }, undefined, filename)
	}

	static basicGet(parameters, callUrl, callbackSuccess, callbackFailure) {
		WebClient.basicCall('Get', parameters, callUrl, callbackSuccess, callbackFailure)
	}

	static basicPost(parameters, callUrl, callbackSuccess, callbackFailure, headers) {
		WebClient.basicCall('Post', parameters, callUrl, callbackSuccess, callbackFailure, headers)
	}

	static basicCall(method, parameters, callUrl, callbackSuccess, callbackFailure, headers, filename) {
		let url = callUrl;
		let args = {
			method: (method == 'Download') ? 'Get' : method,
			headers: {}
		}

		if (method == 'Post') {
			args.body = JSON.stringify({ ...parameters });
			args.headers = {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			}
		}
		else if (method == 'Get' || method == 'Download') {
			url = callUrl + "?" + new URLSearchParams(parameters);
		}

		let tokens = Storage.get('goa-tokens');
		if (Storage.has('goa-loginasuser-tokens')) tokens = Storage.get('goa-loginasuser-tokens');
		if (tokens) {
			args.headers.Authorization = 'Bearer ' + tokens.access.token;
		}

		if (headers) args.headers = { ...args.headers, ...headers };

		fetch(url, args).then((response) => {

			if (method == 'Download') {
				response.blob().then(data => {
					data.text().then(streamData => {
						let blob = new Blob([streamData]);
						if (window.navigator.msSaveOrOpenBlob) {
							window.navigator.msSaveBlob(blob, filename);
						}
						else {
							let elem = window.document.createElement('a');
							elem.href = window.URL.createObjectURL(blob);
							elem.download = filename;
							document.body.appendChild(elem);
							elem.click();
							document.body.removeChild(elem);
						}
					})
				});
			}
			else {
				response.json().then((data) => {
					if (data.result == 'success') {
						if (callbackSuccess !== undefined) {
							try {
								callbackSuccess(data);
							}
							catch (exception) {
								console.log(
									'Success call back failed on api call: ' + callUrl,
									exception
								);
							}
						}
					}
					else if (callbackFailure !== undefined) callbackFailure(data);
				}).catch(() => {
					if (callbackFailure !== undefined) callbackFailure({ result: 'failure', error_message: 'Whoops looks like something went wrong' });
				});
			}
		}).catch((response) => {
			if (callbackFailure !== undefined) callbackFailure({ result: 'failure', error_message: 'Whoops looks like something went wrong' });
		});

	}
};