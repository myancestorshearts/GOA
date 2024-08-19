const PAD_ZEROS = (string, number_of_zeros) =>
{
    string = String(string);
    while(string.length < number_of_zeros) string = '0' + string;
    return string;
}

const InitializePrototypes = () => {
	String.prototype.insert = function (index, string) {
		if (index > 0)
		return this.substring(0, index) + string + this.substring(index, this.length);
		else
		return string + this;
	};

	if (!String.prototype.startsWith) {
		String.prototype.startsWith = function(search, pos) {
			return this.substr(!pos || pos < 0 ? 0 : +pos, search.length) === search;
		};
	}

	if ( typeof Object.getPrototypeOf !== "function" ) {
		if ( typeof "test".__proto__ === "object" ) {
			Object.getPrototypeOf = function(object){
			return object.__proto__;
			};
		} else {
			Object.getPrototypeOf = function(object){
			// May break if the constructor has been tampered with
			return object.constructor.prototype;
			};
		}
	}

	String.prototype.capitalize = function() {
	  return this.charAt(0).toUpperCase() + this.slice(1)
	}

	Array.prototype.move = function (old_index, new_index) {
		if (new_index >= this.length) {
			var k = new_index - this.length;
			while ((k--) + 1) {
				this.push(undefined);
			}
		}
		this.splice(new_index, 0, this.splice(old_index, 1)[0]);
	};

	if (!Element.prototype.matches) Element.prototype.matches =Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;

	if (!Element.prototype.closest) Element.prototype.closest = function(s) {
		var el = this;
		if (!document.documentElement.contains(el)) return null;
		do {
			if (el.matches(s)) return el;
			el = el.parentElement || el.parentNode;
		} while (el !== null && el.nodeType === 1);
		return null;
	};

	Date.prototype.getWeekDay = function() {
		var weekday = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
		return weekday[this.getDay()];
	};


	Date.prototype.getWeekDayShort = function() {
		var weekday = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
		return weekday[this.getDay()];
	};


	Date.prototype.getWeekOfYear = function() {
		var onejan = new Date(this.getFullYear(),0,1);
		return Math.ceil((((this - onejan) / 86400000) + onejan.getDay()+1)/7);
	}


	Date.prototype.getMonthName = function() {
		var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
		return months[this.getMonth()];
	};

	Date.prototype.getStartOfHour = function()
	{
		var newDate = new Date(this);
		newDate.setHours(this.getHours(),0,0,0);
		return newDate;
	};

	Date.prototype.getEndOfHour = function()
	{
		var newDate = new Date(this);
		newDate.setHours(this.getHours(),59,59,999);
		return newDate;
	};

	Date.prototype.getStartOfMinute = function()
	{
		var newDate = new Date(this);
		newDate.setHours(this.getHours(),this.getMinutes(),0,0);
		return newDate;
	};

	Date.prototype.getEndOfMinute = function()
	{
		var newDate = new Date(this);
		newDate.setHours(this.getHours(),this.getMinutes(),59,999);
		return newDate;
	};

	Date.prototype.getStartOfDay = function()
	{
		var newDate = new Date(this);
		newDate.setHours(0,0,0,0);
		return newDate;
	};

	Date.prototype.getEndOfDay = function()
	{
		var newDate = new Date(this);
		newDate.setHours(23,59,59,999);
		return newDate;
	};


	Date.prototype.getStartOfMonth = function()
	{
		var newDate = new Date(this.getFullYear(), this.getMonth() + 0, 1);
		newDate.setHours(0,0,0,0);
		return newDate;
	};


	Date.prototype.getEndOfMonth = function()
	{
		var newDate = new Date(this.getFullYear(), this.getMonth() + 1, 0);
		newDate.setHours(23,59,59,999);
		return newDate;
	};
	Date.prototype.getStartOfMonthUTC = function()
	{
		var newDate = new Date(this.getUTCFullYear(), this.getUTCMonth() + 0, 1);
		newDate.setHours(0,0,0,0);
		return newDate;
	};

	Date.prototype.getEndOfMonthUTC = function()
	{
		var newDate = new Date(this.getUTCFullYear(), this.getUTCMonth() + 1, 0);
		newDate.setHours(23,59,59,999);
		return newDate;
	};


	Date.prototype.getStartOfYear = function()
	{
		var newDate = new Date(this.getFullYear(), 0, 1);
		newDate.setHours(0,0,0,0);
		return newDate;
	};

	Date.prototype.getEndOfYear = function()
	{
		var newDate = new Date(this.getFullYear(), 11, 31);
		newDate.setHours(23,59,59,999);
		return newDate;
	};

	Date.prototype.getStartOfYearUTC = function()
	{
		var newDate = new Date(this.getUTCFullYear(), 0, 1);
		newDate.setHours(0,0,0,0);
		return newDate;
	};

	Date.prototype.getEndOfYearUTC = function()
	{
		var newDate = new Date(this.getUTCFullYear(), 11, 31);
		newDate.setHours(23,59,59,999);
		return newDate;
	};

	Date.prototype.getStartOfWeek = function()
	{
		var currentWeekDay = this.getDay();
		var lessDays = currentWeekDay == 0 ? 6 : currentWeekDay - 1
		var newDate = new Date(new Date(this).setDate(this.getDate()- lessDays));
		newDate.setHours(0,0,0,0);
		return newDate;
	}

	Date.prototype.getEndOfWeek = function()
	{
		var currentWeekDay = this.getDay();
		var lessDays = currentWeekDay == 0 ? 6 : currentWeekDay - 1
		var startOfWeek = this.getStartOfWeek();
		var newDate = new Date(new Date(startOfWeek).setDate(startOfWeek.getDate()+6));
		newDate.setHours(23,59,59,999);
		return newDate;
	}

	Date.prototype.getDateCountDown = function()
	{
		var currentDate = new Date();
		var day_diff = 0;
		var hour_diff = 0;
		var minute_diff = 0;
		var second_diff = 0;
		if (currentDate > this)
		{
			day_diff = Math.floor((currentDate - this) / (1000 * 60 * 60 * 24));
			second_diff = currentDate.getSeconds() - this.getSeconds();
			minute_diff = currentDate.getMinutes() - this.getMinutes();
			hour_diff = currentDate.getHours() - this.getHours();
			if (minute_diff < 0)
			{
				minute_diff += 60;
				hour_diff -= 1;
			}
			if (second_diff < 0)
			{
				second_diff += 60;
				minute_diff -= 1;
			}
			if (hour_diff < 0)
			{
				hour_diff += 24;
			}
		}
		else
		{
			day_diff =  Math.floor((this - currentDate) / (1000 * 60 * 60 * 24));
			hour_diff = this.getHours() - currentDate.getHours();
			second_diff = this.getSeconds() - currentDate.getSeconds();
			minute_diff = this.getMinutes() - currentDate.getMinutes();
			if (second_diff < 0)
			{
				second_diff += 60;
				minute_diff -= 1;
			}
			if (minute_diff < 0)
			{
				minute_diff += 60;
				hour_diff -= 1;
			}
			if (hour_diff < 0)
			{
				hour_diff += 24;
			}
		}
		return PAD_ZEROS(day_diff, 2) + ":" + PAD_ZEROS(hour_diff, 2) + ":" + PAD_ZEROS(minute_diff, 2) + ":" + PAD_ZEROS(second_diff, 2);
	};

	Date.prototype.addTimeFrame = function(quantity, unit)
	{
		if (unit == 'DAY') this.setDate(this.getDate() + quantity);
		else if (unit == 'WEEK') this.setDate(this.getDate() + (quantity * 7));
		else if (unit == 'MONTH') this.setMonth(this.getMonth() + quantity);
		else if (unit == 'YEAR') this.setYear(this.getYear() + quantity);
	}

	Date.prototype.formatTickSize = function(tickSize)
	{
		if (tickSize == 'ALL') return 'ALL';
		else if (tickSize == 'YEAR') return this.formatDate('Y');
		else if (tickSize == 'MONTH') return this.formatDate('Y-n');
		else if (tickSize == 'DAY') return this.formatDate('Y-n-d');		
		else if (tickSize == 'WEEK') return this.formatDate('Y-W');
		else if (tickSize == 'HOUR') return this.formatDate('Y-n-d i');
		return '';
	}

	Date.prototype.formatTickSizeLabel = function(tickSize)
	{
		if (tickSize == 'ALL') return 'ALL';
		else if (tickSize == 'YEAR') return this.formatDate('Y');
		else if (tickSize == 'MONTH') return this.formatDate('n/Y');
		else if (tickSize == 'DAY') return this.formatDate('n/d/Y');
		else if (tickSize == 'WEEK') return this.formatDate('n/d/Y');
		else if (tickSize == 'HOUR') return this.formatDate('n/d/Y HA');
		return 'd/n/y HA';
	}

	Date.prototype.getWeek = function() {
		var onejan = new Date(this.getFullYear(),0,1);
		var millisecsInDay = 86400000;
		return Math.ceil((((this - onejan) /millisecsInDay) + onejan.getDay()+1)/7);
	};


	// format time
	// format string
	//     ('l' is the full day [Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday])
	//     ('k' is the Abbr. full day [Mon, Tue, Wed, Thu, Fri, Sat, Sun])
	//     ('M' is the full month [January, February, March, April, May, June, July, August, September, October, November, December]);
	//     ('n' is the month number;
	//     ('d' is the day of month)
	//     ('h' is the hour of the day military time)
	//     ('m' is the minute of the hour)
	//     ('s' is the second of the minute)
	//     ('H' is the hour of the day standard time)	
	//     ('A' is the AM or PM of the time)
	//     ('Y' is the full year)
	//     ('W' is the full week)
	Date.prototype.formatDate = function(format_string)
	{
		var completeString = '';
		for (var i = 0; i < format_string.length; i++)
		{
			var c = format_string[i];
			if (c == 'l') completeString += this.getWeekDay();
			else if (c == 'W') completeString += this.getWeek();
			else if (c == 'k') completeString += this.getWeekDayShort();
			else if (c == 'W') completeString += this.getWeekOfYear();
			else if (c == 'M') completeString += this.getMonthName();
			else if (c == 'n') completeString += PAD_ZEROS((this.getMonth() + 1), 2);
			else if (c == 'd') completeString += PAD_ZEROS(this.getDate(), 2);
			else if (c == 'h') completeString += this.getHours();
			else if (c == 'i') completeString += PAD_ZEROS(this.getHours(), 2);
			else if (c == 'm') completeString += PAD_ZEROS(this.getMinutes(), 2);
			else if (c == 's') completeString += PAD_ZEROS(this.getSeconds(), 2);
			else if (c == 'H') completeString += this.getHours() > 12 ? this.getHours() - 12 : this.getHours() == 0 ? 12 : this.getHours();
			else if (c == 'A') completeString += this.getHours() >= 12 && this.getHours() <= 23 ? 'PM' : 'AM';
			else if (c == 'y') completeString += this.getFullYear().toString().substr(-2);
			else if (c == 'Y') completeString += this.getFullYear();
			else completeString += c;
		}
		return completeString;
	};


	Date.prototype.formatAgo = function()
	{
		let now = new Date()

		let secondsInBetween = (now.getTime() - this.getTime()) / 1000;

		// seconds
		if (secondsInBetween < 10) return 'just now';
		if (secondsInBetween < 60) return 'a few seconds ago';
		// minutes
		if (secondsInBetween < 60 * 2) return '1 minute ago';
		if (secondsInBetween < 60 * 60) return Number(secondsInBetween / 60).toFixed(0) + ' minutes ago';
		// hours
		if (secondsInBetween < 60 * 60 * 2) return '1 hour ago';
		if (secondsInBetween < 60 * 60 * 24) return Number(secondsInBetween / (60 * 60)).toFixed(0) + ' hours ago';
		// days
		if (secondsInBetween < 60 * 60 * 24 * 2) return '1 day ago';
		if (secondsInBetween < 60 * 60 * 24 * 7) return Number(secondsInBetween / (60 * 60 * 24)).toFixed(0) + ' days ago';
		// weeks
		if (secondsInBetween < 60 * 60 * 24 * 7 * 2) return '1 week ago';
		if (secondsInBetween < 60 * 60 * 24 * 28) return Number(secondsInBetween / (60 * 60 * 24 * 7)).toFixed(0) + ' weeks ago';
		// months
		if (secondsInBetween < 60 * 60 * 24 * 30 * 2) return '1 month';
		if (secondsInBetween < 60 * 60 * 24 * 365) return Number(secondsInBetween / (60 * 60 * 24 * 30)).toFixed(0) + ' months ago';
		// years
		if (secondsInBetween < 60 * 60 * 24 * 365 * 2) return '1 year ago';
		return Number(secondsInBetween / (60 * 60 * 24 * 365)).toFixed(0) + ' years ago';
	}
}

export default InitializePrototypes;