/**
 * A simple jquery plugin to allow collapsing / expanding of elements containing lots of text.
 *
 * Copyright 2008, Debuggable, Ltd.
 * Hibiskusweg 26c
 * 13089 Berlin, Germany
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008, Debuggable, Ltd.
 * @version	1.0
 * @author Felix Geisendörfer <felix@debuggable.com>, Tim Koschützki <tim@debuggable.com>
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 */
$.fn.expandable = function(options) {
	options = $.extend({
		more: 'more »',
		less: '« hide',
		length: 20,
		greedy: true,
	}, options || {});

	return this.each(function() {
		var $this = $(this), text = $this.text(), truncated = text.substr(0, options.length), after = text.substr(options.length);
		if (!text) {
			return;
		}
		truncated = (options.greedy)
			? truncated + (after.match(/.*?[\s]/) || [''])[0]
			: truncated.match(/.*[\s]/)[0];
		truncated = truncated.substr(0, truncated.length-1);

		$this.data('expandable', {full: text, truncated: truncated});
		var $less = $('<a/>')
			.text(options.less)
			.attr('href', '#less')
			.click(function() {
				$(this).appendTo('body').hide();
				$this
					.text($this.data('expandable').truncated+' ')
					.append($more.show());
				return false;
			});
		var $more = $('<a/>')
			.text(options.more)
			.attr('href', '#more')
			.click(function() {
				$(this).appendTo('body').hide();
				$this
					.text($this.data('expandable').full+' ')
					.append($less.show());
				return false;
			});
		$less.click();
	});
};