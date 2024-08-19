import React from 'react';
import ReactDOM from 'react-dom';


export default class Functions {


	/**purpose
	 *   check if a string is a float
	 * args
	 *   string
	 * returns
	 *   (true / false)
	 */
	static isFloat(val) {
		var floatRegex = /^-?\d+(?:[.,]\d*?)?$/;
		if (!floatRegex.test(val))
			return false;

		val = parseFloat(val);
		if (isNaN(val))
			return false;
		return true;
	}

	static formatAddress(address) {
		let formatted = address.street_1;
		if (!this.isEmpty(address.street_2)) formatted += ' ' + address.street_2;
		formatted += ', ' + address.city + ', ' + address.state + ' ' + address.postal;
		return formatted;
	}
	static isEmpty(input) {
		if (input === undefined) return true;
		else if (input === null) return true;
		else if (typeof (input) === typeof ('string') && input.trim() === '') return true;
		else if (input === 'null') return true;
		else if (input === "0000-00-00 00:00:00") return true;
		else return false;
	}

	static convertToMoney(newNumb, freeValue) {

		if (freeValue && Number(newNumb) == 0) return freeValue;

		if (!Number.prototype.format) Number.prototype.format = function (n, x, s, c) {
			let re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
				num = this.toFixed(Math.max(0, ~~n));

			return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
		};
		return `$${(Number(newNumb)).format(2)}`;
	}

	static deepGetFromString(obj, key, defaultValue = undefined, delimiter = ',') {
		return this.deepGetFromArray(obj, key.split(','), defaultValue);
	}

	static deepGetFromArray(obj, array, defaultValue = undefined) {
		let model = obj;
		while (array.length > 0) {
			let key = array.shift();
			if (model[key] === undefined || model[key] === null) return defaultValue;
			else if (array.length > 0) model = model[key]
			else return model[key];
		}
	}

	static deepSetFromString(obj, key, newValue) {
		return this.deepSetFromArray(obj, key.split(","), newValue);
	}

	static deepSetFromArray(obj, array, newValue) {
		let model = obj;
		while (array.length > 0) {
			let key = array.shift();
			if (array.length > 0) {
				if (model[key] === undefined || model[key] === null || Array.isArray(model[key])) {
					delete model[key];
					model[key] = {};
				}
				model = model[key];
			}
			else {
				model[key] = newValue;
			}
		}
	}

	static convertMysqlToDateRaw(mysql_date, defaultValue = new Date()) {
		if (this.isEmpty(mysql_date)) return defaultValue;
		var dateConvertedString = mysql_date.substring(5, 7) + "/" + mysql_date.substring(8, 10) + "/" + mysql_date.substring(0, 4) + " " + mysql_date.substring(11, 13) + ":" + mysql_date.substring(14, 16) + ":" + mysql_date.substring(17, 19);
		return new Date(dateConvertedString);
	}

	static convertMysqlToDate(mysql_date, defaultValue = new Date()) {
		if (this.isEmpty(mysql_date)) return defaultValue;
		var dateConvertedString = mysql_date.substring(5, 7) + "/" + mysql_date.substring(8, 10) + "/" + mysql_date.substring(0, 4) + " " + mysql_date.substring(11, 13) + ":" + mysql_date.substring(14, 16) + ":" + mysql_date.substring(17, 19) + " UTC";

		return new Date(dateConvertedString);
	}

	static convertDateToMysql(date) {
		if (!date) {
			return;
		}
		return date.getUTCFullYear() + '-' + ('00' + (date.getUTCMonth() + 1)).slice(-2) + '-' + ('00' + date.getUTCDate()).slice(-2) + ' ' + ('00' + date.getUTCHours()).slice(-2) + ':' + ('00' + date.getUTCMinutes()).slice(-2) + ':' + ('00' + date.getUTCSeconds()).slice(-2);
	}

	static convertDateToMysqlRaw(date) {
		return date.getFullYear() + '-' + ('00' + (date.getMonth() + 1)).slice(-2) + '-' + ('00' + date.getDate()).slice(-2) + ' ' + ('00' + date.getHours()).slice(-2) + ':' + ('00' + date.getMinutes()).slice(-2) + ':' + ('00' + date.getSeconds()).slice(-2);
	}

	static inputToBool(input) {
		if (input === 'true') return true;
		else if (input === true) return true;
		else if (input === '1') return true;
		else if (input === 1) return true;
		else return false;
	}

	static getQueryParams() {
		// This function is anonymous, is executed immediately and
		// the return value is assigned to QueryString!
		var query_string = {};
		var query = window.location.search.substring(1);
		if (query.trim() == '') return {};
		var vars = query.split("&");
		for (var i = 0; i < vars.length; i++) {
			var pair = vars[i].split("=");
			// If first entry with this name
			if (typeof query_string[pair[0]] === "undefined") {
				query_string[pair[0]] = decodeURIComponent(pair[1]);
				// If second entry with this name
			} else if (typeof query_string[pair[0]] === "string") {
				var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
				query_string[pair[0]] = arr;
				// If third or later entry with this name
			} else {
				query_string[pair[0]].push(decodeURIComponent(pair[1]));
			}
		}
		return query_string;
	}

	static array_move(arr, old_index, new_index) {
		if (new_index >= arr.length) {
			var k = new_index - arr.length + 1;
			while (k--) {
				arr.push(undefined);
			}
		}
		arr.splice(new_index, 0, arr.splice(old_index, 1)[0]);
		return arr; // for testing
	}

	static floor(number, precision = 0) {

		number = (number * (Math.pow(10, precision)));
		number = Math.floor(number);
		number = (number / (Math.pow(10, precision)));

		return number;
	}


	static ceil(number, precision = 0) {

		number = (number * (Math.pow(10, precision)));
		number = Math.ceil(number);
		number = (number / (Math.pow(10, precision)));

		return number;
	}


	static round(number, precision = 0) {

		number = (number * (Math.pow(10, precision)));
		number = Math.round(number);
		number = (number / (Math.pow(10, precision)));

		return number;
	}

	static getRandomId() {
		return Math.random().toString(36).slice(2);
	}
	static isObject(item) {
		return (item && typeof item === 'object' && !Array.isArray(item));
	}

	static mergeDeep(target, ...sources) {
		if (!sources.length) return target;
		const source = sources.shift();

		if (this.isObject(target) && this.isObject(source)) {
			for (const key in source) {
				if (this.isObject(source[key])) {
					if (!target[key]) Object.assign(target, { [key]: {} });
					this.mergeDeep(target[key], source[key]);
				} else {
					Object.assign(target, { [key]: source[key] });
				}
			}
		}

		return this.mergeDeep(target, ...sources);
	}

	static convertToAPIargs(obj) {
		obj = this.mergeDeep({}, obj);
		if (obj !== undefined && obj !== null && Object.getPrototypeOf(obj).toString() === '[object Object]') {
			var newObj = this.mergeDeep({}, obj);
			for (var key in newObj) {
				if (newObj.hasOwnProperty(key)) {
					if (newObj[key] === null) delete newObj[key];
					else if (newObj[key] === undefined || Object.getPrototypeOf(newObj[key]).toString() === '[object Object]' || Array.isArray(newObj[key])) {
						newObj[key] = (function () { return })()
					}
				}
			}
			return newObj;
		}
		return obj;
	}

	//Check to see if on mobile ios device using safari
	static iOSMobileSafariChecker() {
		let ua = window.navigator.userAgent;
		let iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
		let webkit = !!ua.match(/WebKit/i);
		let iOSSafari = iOS && webkit && !ua.match(/CriOS/i); //IF THIS IS TRUE THEN YES IT IS A STUPID APPLE DEVICE
		return (iOSSafari) //Return bool
	}

	static copyToClipboard(string, successCallback, failureCallback) {

		let textArea = document.createElement("textarea");
		textArea.value = string;

		document.body.appendChild(textArea);
		textArea.select();
		try {

			// for IOS (You may think this code is not necessary but seems to be the only way ios would work)
			if (this.iOSMobileSafariChecker()) {
				let el = textArea;
				let editable = el.contentEditable;
				let readOnly = el.readOnly;
				el.contentEditable = true;
				el.readOnly = false;
				let range = document.createRange();
				range.selectNodeContents(el);
				let sel = window.getSelection();
				sel.removeAllRanges();
				sel.addRange(range);
				el.setSelectionRange(0, 999999);
				el.contentEditable = editable;
				el.readOnly = readOnly;
			}
			// End for IOS

			document.execCommand('copy');
			if (successCallback) successCallback()
		} catch (err) {
			if (failureCallback) failureCallback()
		}
		document.body.removeChild(textArea);
	}

	static getKeyByValue(object, value) {
		return Object.keys(object).find(key => object[key] === value);
	}


	static ADDRESS_STATES = {
		"AL": "Alabama",
		"AK": "Alaska",
		"AS": "American Samoa",
		"AZ": "Arizona",
		"AR": "Arkansas",
		"CA": "California",
		"CO": "Colorado",
		"CT": "Connecticut",
		"DE": "Delaware",
		"DC": "District Of Columbia",
		"FL": "Florida",
		"FM": "Federal States of Micronesia",
		"GA": "Georgia",
		"GU": "Guam",
		"HI": "Hawaii",
		"ID": "Idaho",
		"IL": "Illinois",
		"IN": "Indiana",
		"IA": "Iowa",
		"KS": "Kansas",
		"KY": "Kentucky",
		"LA": "Louisiana",
		"ME": "Maine",
		"MH": "Marhsall Islands",
		"MD": "Maryland",
		"MA": "Massachusetts",
		"MI": "Michigan",
		"MN": "Minnesota",
		"UM": "Minor Outlying Islands",
		"MS": "Mississippi",
		"MO": "Missouri",
		"MT": "Montana",
		"NE": "Nebraska",
		"NV": "Nevada",
		"NH": "New Hampshire",
		"NJ": "New Jersey",
		"NM": "New Mexico",
		"NY": "New York",
		"NC": "North Carolina",
		"ND": "North Dakota",
		"MP": "Northern Mariana Islands",
		"OH": "Ohio",
		"OK": "Oklahoma",
		"OR": "Oregon",
		"PA": "Pennsylvania",
		"PR": "Puerto Rico",
		"RI": "Rhode Island",
		"SC": "South Carolina",
		"SD": "South Dakota",
		"TN": "Tennessee",
		"TX": "Texas",
		"UT": "Utah",
		"VT": "Vermont",
		"VI": "Virgin Islands",
		"VA": "Virginia",
		"WA": "Washington",
		"WV": "West Virginia",
		"WI": "Wisconsin",
		"WY": "Wyoming"
	}


	static printElement(areaElement) {

		document.body.childNodes.forEach(element => element.className += ' doNotPrintMe');
		document.body.prepend(areaElement);

		document.body.childNodes[0].className += ' printMe';

		window.print();
		setTimeout(
			() => {
				areaElement.remove();
				document.body.childNodes.forEach(element => element.className = element.className.replace(' doNotPrintMe', ''));
			},
			3000
		)
	}

	static setPrintSize(width, height) {

		let id = 'PrintStyles';
		let style = document.getElementById(id);
		if (!style) {
			style = document.createElement('style');
			style.id = id;
			document.head.appendChild(style);
		}
		style.textContent = `
			@media print{
				@page{
					size:${width}px ${height}px;
				}
			}
		`;
	}

	static reactRendorToObject(react_html) {
		let key = Math.random().toString(36).slice(2);

		let react_object_creator = document.createElement('div');
		react_object_creator.id = key;

		document.body.append(react_object_creator);
		ReactDOM.render(
			react_html,
			document.getElementById(key)
		);

		let returnElement = react_object_creator;
		react_object_creator.remove();
		return returnElement;
	}
}