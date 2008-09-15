/**
 * jAc Browser Tab Completion
 *
 * A Jquery plugin that will allow you to tab-complete a set of strings found in your markup
 *
 * Copyright 2008, Debuggable, Ltd.
 * Hibiskusweg 26c
 * 13089 Berlin, Germany
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2008, Debuggable, Ltd.
 * @link			http://debuggable.com/open-source/jtab
 * @version			0.1
 * @author			Tim Koschuetzki <tim@debuggable.com>, Felix Geisendörfer <felix@debuggable.com>
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
$.fn.jac = function(action, options) {
	if (typeof action == 'string') {
		switch (action) {
			case 'suggest':
				var settings = this.data('jac.options'), val = this.val(), caret = this.caret(), context = {
					val: val,
					caret: caret,
					before: val.substr(0, caret.begin),
					after: val.substr(caret.end)
				};
				context.word = (context.before.match(settings.word) || [false])[1];

				if (!context.word) {
					return [];
				}
				settings.context = context;

				var suggest = settings.suggest || function(context, settings) {
					var suggestions = [], word = (settings.ignoreCase)
						? context.word.toLowerCase()
						: context.word;

					$.each(settings.items, function() {
						var item = settings.ignoreCase
							? this.toLowerCase()
							: item;

						if (item.indexOf(word) === 0) {
							var suggestion = (settings.alter)
							 	? settings.alter(String(this), context, settings)
								: String(this);
							suggestions.push(suggestion);
						}

						if (suggestions.length && !settings.findAll) {
							return false;
						}
					});

					return suggestions;
				};

				return suggest.call(this, context, settings);
			case 'inject':
				var settings = this.data('jac.options'), val = this.val(), caret = this.caret();
				if (settings.inject) {
					settings.inject.call(this, val, {caret: caret}, settings);
					return true;
				}

				val = val.substr(0, caret.begin - settings.context.word.length)
					+ options
					+ val.substr(caret.end, val.length);

				this.val(val);
				this.caret(caret.begin + options.length);

				return true;
			case 'complete':
				var suggestions = this.jac('suggest');
				if (!suggestions.length) {
					return;
				}
				this.jac('inject', suggestions[0]);

				return false;
		}
	}

	if (typeof action == 'object' && action.constructor === Array) {
		options = {items: action};
	} else if (typeof action == 'object') {
		options = action;
	}

	action = 'init';
	options = $.extend({
		items: '.author',
		ignoreCase: true,
		findAll: false,
		word: /([\w]+)$/i,
		alter: function(suggestion, context, settings) {
			var start = context.before == context.word, newLine = (context.before.substr(-(context.word.length+1), 1) == "\n");
			if (start || newLine) {
				return suggestion+': ';
			}
			return suggestion+' ';
		}
	}, options || {});

	if (typeof options.items == 'string') {
		var items = [];
		$(options.items).each(function() {
			items.push($(this).text().replace(/[\r\n\t]+/, ''));
		})
		options.items = items;
	}

	this.data('jac.options', options);
	var keypress = ($.browser.safari)
		? 'keydown'
		: 'keypress';

	return this.bind(keypress, function(e) {
		if (e.keyCode != 9) {
			return;
		}
		return $(this).jac('complete');
	});
};

if (!$.fn.caret) {
	// Copyright (c) 2007-2008 Josh Bush (digitalbush.com) (MIT licensed) -- Part of the masked input plugin
	// refactored by Felix Geisendörfer
	$.fn.caret = function(begin,end) {
		if (this.length == 0) {
			return;
		}

		if (typeof begin == 'number') {
	        end = (typeof end == 'number')
				? end
				: begin;  

			return this.each(function() {
				if (this.setSelectionRange){
					this.focus();
					this.setSelectionRange(begin,end);
				} else if (this.createTextRange) {
					var range = this.createTextRange();
					range.collapse(true);
					range.moveEnd('character', end);
					range.moveStart('character', begin);
					range.select();
				}
			});
		}

		if (this[0].setSelectionRange) {
			begin = this[0].selectionStart;
			end = this[0].selectionEnd;
		} else if (document.selection && document.selection.createRange) {
			var range = document.selection.createRange();
			begin = 0 - range.duplicate().moveStart('character', -100000);
			end = begin + range.text.length;
		}

		return {begin:begin, end:end};
	};
}