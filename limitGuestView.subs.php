<?php

/**
 * @package "LimitGuestView" addon for ElkArte
 * @author Spuds
 * @copyright (c) 2014 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 0.1
 *
 */
if (!defined('ELK'))
	die('No access...');

/**
 * Integration hook, integrate_general_mod_settings
 *
 * - Not a lot of settings for this addon so we add them under the predefined
 * Miscellaneous area of the forum
 *
 * @param mixed[] $config_vars
 */
function igm_limitGuestView(&$config_vars)
{
	global $txt;

	loadLanguage('limitGuestView');

	$config_vars = array_merge($config_vars, array(
		'',
		array('int', 'limitGuestView_count', 'subtext' => $txt['limitGuestView_count_desc']),
	));
}

/**
 * ipdc_limitGuestView_count()
 *
 * - Display Hook, integrate_prepare_display_context, called from Display.controller
 * - Used to interact with the message array before its sent to the template
 *
 * @param mixed[] $output
 * @param mixed[] $message
 */
function ipdc_limitGuestView(&$output, &$message)
{
	global $modSettings, $context, $scripturl, $txt;
	static $nag;

	// Make sure we need to do anything
	if (!$context['user']['is_guest'] || empty($modSettings['limitGuestView_count']))
		return;

	if (Util::strlen($output['body']) > $modSettings['limitGuestView_count'])
	{
		if (empty($nag))
		{
			loadLanguage('limitGuestView');
			$nag = sprintf($txt['limitGuestView_nag'], '<a href="' . $scripturl . '?action=login">', '<a href="' . $scripturl . '?action=register">');
		}

		$output['body'] = lgv_shorten_html($output['body'], $modSettings['limitGuestView_count'], $nag, false);
	}
}

/**
 * Truncate a string up to a number of characters while preserving whole words and HTML tags
 *
 * This function is an adaption of the cake php function truncate in utility string.php (MIT)
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string
 * @param string|bool $ellipsis characters to add at the end of cut string, like ...
 * @param boolean $exact If to account for $ellipsis length in returned $length
 *
 * @return string Trimmed string.
 */
function lgv_shorten_html($string, $length = 384, $ellipsis = '...', $exact = true)
{
	// If its shorter than the maximum length, while accounting for html tags, simply return
	if (Util::strlen(preg_replace('~<.*?>~', '', $string)) <= $length)
		return $string;

	// Start off empty
	$total_length = $exact ? Util::strlen($ellipsis) : 0;
	$open_tags = array();
	$truncate = '';

	// Group all html open and closing tags, [1] full tag with <> [2] basic tag name [3] tag content
	preg_match_all('~(<\/?([\w+]+)[^>]*>)?([^<>]*)~', $string, $tags, PREG_SET_ORDER);

	// Walk down the stack of tags
	foreach ($tags as $tag)
	{
		// If this tag has content
		if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2]))
		{
			// Opening tag add the closing tag to the top of the stack
			if (preg_match('~<[\w]+[^>]*>~s', $tag[0]))
				array_unshift($open_tags, $tag[2]);
			// Closing tab
			elseif (preg_match('~<\/([\w]+)[^>]*>~s', $tag[0], $close_tag))
			{
				// Remove its starting tag
				$pos = array_search($close_tag[1], $open_tags);
				if ($pos !== false)
					array_splice($open_tags, $pos, 1);
			}
		}

		// Add this (opening or closing) tag to $truncate
		$truncate .= $tag[1];

		// Calculate the length of the actual tag content, accounts for html entities as a single characters
		$content_length = Util::strlen($tag[3]);

		// Have we exceeded the allowed length limit, only add in what we are allowed
		if ($content_length + $total_length > $length)
		{
			// The number of characters which we can still return
			$remaining = $length - $total_length;
			$truncate .= Util::substr($tag[3], 0, $remaining);
			break;
		}
		// Still room to go so add the tag content and continue
		else
		{
			$truncate .= $tag[3];
			$total_length += $content_length;
		}

		// Are we there yet?
		if ($total_length >= $length)
			break;
	}

	// Our truncated string up to the last space
	$space_pos = Util::strpos($truncate, ' ', 0, true);
	$truncate_check = Util::substr($truncate, 0, $space_pos);

	// Make sure this would not cut in the middle of a tag
	$lastOpenTag = Util::strpos($truncate_check, '<', 0, true);
	$lastCloseTag = Util::strpos($truncate_check, '>', 0, true);
	if ($lastOpenTag > $lastCloseTag)
	{
		// Find the last full open tag in our truncated string
		preg_match_all('~<[\w]+[^>]*>~s', $truncate, $lastTagMatches);
		$last_tag = array_pop($lastTagMatches[0]);

		// Set the space to just after the last tag
		$space_pos = Util::strpos($truncate, $last_tag, 0, true) + Util::strlen($last_tag);
	}

	// Look at what we are going to cut off the end of our truncated string
	$bits = Util::substr($truncate, $space_pos);

	// Does it cut a tag off, if so we need to know so it can be added back at the cut point
	preg_match_all('~<\/([a-z]+)>~', $bits, $dropped_tags, PREG_SET_ORDER);
	if (!empty($dropped_tags))
	{
		if (!empty($open_tags))
		{
			foreach ($dropped_tags as $closing_tag)
			{
				if (!in_array($closing_tag[1], $open_tags))
					array_unshift($open_tags, $closing_tag[1]);
			}
		}
		else
		{
			foreach ($dropped_tags as $closing_tag)
				$open_tags[] = $closing_tag[1];
		}
	}

	// Cut it
	$truncate = Util::substr($truncate, 0, $space_pos);

	// dot dot dot
	$truncate .= $ellipsis;

	// Finally close any html tags that were left open
	foreach ($open_tags as $tag)
		$truncate .= '</' . $tag . '>';

	return $truncate;
}