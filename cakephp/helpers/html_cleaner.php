<?php
/**
 * Created: Sat February 26 EDT 2008
 * 
 * A class to balance out html tags in texts. Makes your texts ready for excerpts with the TextHelper's truncate function.
 * 
 *
 * Copyright (c) Debuggable Ltd. <http://debuggable.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * Example:
 * $bogusHtml = $htmlCleaner->clean($bogusHtml);
 *
 *
 * @copyright		Copyright (c) 2008, Debuggable Ltd. <http://debuggable.com>
 * @link			http://www.debuggable.com
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class HtmlCleanerHelper extends Helper {
/**
 * An array of html tags that are singled ended: <img />
 *
 * @var string
 */
	var $singleTags = array('br', 'hr', 'img', 'input');
/**
 * An array of nestable html tags
 *
 * @var string
 */
	var $nestableTags = array('blockquote', 'div', 'span'); 
/**
 * Keeps track of which tags are in the text and thus need to be ended
 *
 * @var string
 */
	var $tagStack = array();
/**
 * String of closing tags that is appended to the text to close all unclosed open tags
 *
 * @var string
 */
	var $tagQueue = '';
/**
 * Resets stack and queue for a new processing
 *
 * @return void
 */
	function reset() {
		$this->tagStack = array();
		$this->tagQueue = '';
	}
/**
 * Main function of this helper: clean and balance out html tags for a given string
 *
 * @param string $txt 
 * @return void
 */
	function clean($txt) {
		$this->reset();
		$result = '';

		// just to cache the stack size and to prevent countless count()s
		$stackSize = 0;

		$txt = $this->takeOutComments($txt);
		$txt = $this->clearUnclosedTagsAtEnd($txt);

		while (preg_match("#<(/?\w*)\s*([^>]*)>#", $txt, $matches)) {
			$result .= $this->tagQueue;
			$this->tagQueue = '';

			$tagPos = strpos($txt, $matches[0]);
			$tagLength = strlen($matches[0]);
			$isStartingTag = $matches[1][0] != "/";

			if ($isStartingTag) {
				$tag = low($matches[1]);

				$isSingleTag = in_array($tag, $this->singleTags);
				$tagNotEmpty = (substr($matches[2], -1) != '/' && $tag != '');

				if ($tagNotEmpty && $isSingleTag) {
					$matches[2] .= '/';
				} else {
					$isNoNestableTag = !in_array($tag, $this->nestableTags);
					$isLastTag = ($stackSize > 0 && $this->tagStack[$stackSize - 1] == $tag);

					if ($isNoNestableTag && $isLastTag) {
						$this->tagQueue = '</'.array_pop($this->tagStack).'>';
					}
					$stackSize = array_push($this->tagStack, $tag);
				}

				$attributes = $matches[2];
				if ($attributes) {
					$attributes = ' ' . $attributes;
				}
				$tag = '<' . $tag . $attributes . '>';

				if (!empty($this->tagQueue)) {
					$this->tagQueue .= $tag;
					$tag = '';
				}
			} else {
				$tag = low(substr($matches[1], 1));

				if ($stackSize <= 0) {
					$tag = '';
				} elseif ($this->tagStack[$stackSize - 1] == $tag) {
					$tag = '</' . $tag . '>';
					array_pop($this->tagStack);
					$stackSize--;
				} else {
					for ($j = $stackSize - 1; $j >= 0; $j--) {
						if ($this->tagStack[$j] == $tag) {
							for ($k = $stackSize - 1; $k >= $j; $k--) {
								$this->tagQueue .= '</' . array_pop($this->tagStack) . '>';
								$stackSize--;
							}
							break;
						}
					}
					$tag = '';
				}
			}
			$result .= substr($txt, 0, $tagPos).$tag;
			$txt = substr($txt, $tagPos + $tagLength);
		}
		$result .= $this->tagQueue . $txt;

		$result = $this->addRemainingTags($result);
		$result = $this->reAddComments($result);

		return $result;
	}
/**
 * Closes all tags in the text that are in the tag stack
 *
 * @param string $txt 
 * @return void
 */
	function addRemainingTags($txt) {
		while ($x = array_pop($this->tagStack)) {
			$txt .= '</' . $x . '>';
		}
		return $txt;
	}
/**
 * Clean up shitty comments ;p No idea for a better name for this...
 * It basically replaces comments temporarily with something else, so comments are not balanced out.
 * Haha how would that work anyway?
 *
 * @param string $txt
 * @return void
 */
	function takeOutComments($txt) {
		$txt = r('< !--', '<    !--', $txt);
		$txt = preg_replace('#<([0-9]{1})#', '&lt;$1', $txt);
		return $txt;
	}
/**
 * Re-replace the comments. : )
 *
 * @param string $txt
 * @return void
 */
	function reAddComments($txt) {
		$txt = r('< !--', '<!--', $txt);
		$txt = r('<    !--', '< !--', $txt);
		return $txt;
	}
/**
 * Well cleans the '<div ' in <span>lala</span><div '. This is useful when you use CakePHP's truncate function
 * (from TextHelper) to build text excerpts, but use html in these texts. ; / Truncate could cut a html tag in two. ; /
 * 
 * @param string $txt
 * @return void
 */
	function clearUnclosedTagsAtEnd($txt) {
		$tags = array('a', 'form', 'input', 'textarea', 'select', 'option', 'optiongroup', 'img', 
			'table', 'th', 'tr', 'td', 'div', 'span', 'p', 'label', 'fieldset', 'legend', 
			'style', 'ul', 'ol', 'li'
		);

		foreach ($tags as $tag) {
			$length = strlen($tag);

			for ($i = 0; $i < $length - 1; $i++) {
				$current = substr($tag, 0, $i);
				$pattern = '/^(.*?)<'.$current.'\s*([^>]*)$/im';
				if (preg_match($pattern, $txt)) {
					$txt = preg_replace($pattern, '$1', $txt);
				}
			}
		}

		return $txt;
	}
}
?>