<?php

/**
 * @package "LimitGuestView" addon for ElkArte
 * @author Spuds
 * @copyright (c) 2014-2021 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 0.1
 *
 */

/**
 * Integration hook, integrate_general_mod_settings
 *
 * - Not a lot of settings for this addon so we add them under the predefined
 * Miscellaneous area of the forum
 *
 * @param array $config_vars
 */
function igm_limitGuestView(&$config_vars)
{
	global $txt;

	loadLanguage('limitGuestView');

	$config_vars = array_merge($config_vars, array(
		array('int', 'limitGuestView_count', 'subtext' => $txt['limitGuestView_count_desc']),
		array('check', 'limitGuestView_signature', 'subtext' => $txt['limitGuestView_signature_desc']),
		'',
	));
}

/**
 * irml_limitGuestView()
 *
 * - Recent Message Hook, integrate_recent_message_list, called from Recent.controller
 * - Used to interact with the recent message array before its sent to the template
 *
 * @param int[] $messages
 */
function irml_limitGuestView($messages)
{
	global $modSettings, $context, $scripturl, $txt;

	// Make sure we need to do anything
	if (!$context['user']['is_guest'] || empty($modSettings['limitGuestView_count']))
	{
		return;
	}

	// Set the login or register message
	loadLanguage('limitGuestView');
	$nag = sprintf($txt['limitGuestView_nag'], '<a href="' . $scripturl . '?action=login">', '<a href="' . $scripturl . '?action=register">');

	if (!empty($modSettings['limitGuestView_signature']))
	{
		$context['signature_enabled'] = false;
	}

	foreach ($messages as $msg_id)
	{
		if (Util::strlen($context['posts'][$msg_id]['body']) > $modSettings['limitGuestView_count'])
		{
			$context['posts'][$msg_id]['body'] = Util::shorten_html($context['posts'][$msg_id]['body'], $modSettings['limitGuestView_count']) . $nag;
		}
	}
}

/**
 * ipdc_limitGuestView_count()
 *
 * - Display Hook, integrate_prepare_display_context, called from Display.controller
 * - Used to interact with the message array before its sent to the template
 *
 * @param array $output
 * @param array $message
 */
function ipdc_limitGuestView(&$output, &$message)
{
	global $modSettings, $context, $scripturl, $txt;
	static $nag;

	// Make sure we need to do anything
	if (!$context['user']['is_guest'] || empty($modSettings['limitGuestView_count']))
	{
		return;
	}

	if (!empty($modSettings['limitGuestView_signature']))
	{
		$context['signature_enabled'] = false;
	}

	if (Util::strlen($output['body']) > $modSettings['limitGuestView_count'])
	{
		if (empty($nag))
		{
			loadLanguage('limitGuestView');
			$nag = sprintf($txt['limitGuestView_nag'], '<a href="' . $scripturl . '?action=login">', '<a href="' . $scripturl . '?action=register">');
		}

		$output['body'] = Util::shorten_html($output['body'], $modSettings['limitGuestView_count']) . $nag;
	}
}