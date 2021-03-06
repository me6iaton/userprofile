<?php

$snippets = array();

$tmp = array(
	'upUsers' => array(
		'file' => 'up_users',
		'description' => '',
	),

	'upProfile' => array(
		'file' => 'up_profile',
		'description' => '',
	),
	'upUserInfo' => array(
		'file' => 'up_info',
		'description' => '',
	),

	'upUserComments' => array(
		'file' => 'up_comments',
		'description' => '',
	),

/*	'upUserFavorites' => array(
		'file' => 'up_favorites',
		'description' => '',
	),*/

	'upUserTotal' => array(
		'file' => 'up_total',
		'description' => '',
	),

	'upProfileEdit' => array(
		'file' => 'up_profile_edit',
		'description' => '',
	),

);

foreach ($tmp as $k => $v) {
	/* @avr modSnippet $snippet */
	$snippet = $modx->newObject('modSnippet');
	$snippet->fromArray(array(
		'id' => 0,
		'name' => $k,
		'description' => @$v['description'],
		'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/snippet.' . $v['file'] . '.php'),
		'static' => BUILD_SNIPPET_STATIC,
		'source' => 1,
		'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/snippets/snippet.' . $v['file'] . '.php',
	), '', true, true);

	$properties = include $sources['build'] . 'properties/properties.' . $v['file'] . '.php';
	$snippet->setProperties($properties);

	$snippets[] = $snippet;
}

unset($tmp, $properties);
return $snippets;