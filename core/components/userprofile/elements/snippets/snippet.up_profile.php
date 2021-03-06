<?php
/** @var array $scriptProperties */
/** @var userprofile $userprofile */
if (!$up = $modx->getService('userprofile', 'userprofile', $modx->getOption('userprofile_core_path', null, $modx->getOption('core_path') . 'components/userprofile/') . 'model/userprofile/', $scriptProperties)) {
	return 'Could not load userprofile class!';
}
//
$scriptProperties['user_id'] = $user_id = $modx->getPlaceholder('user_id');
$scriptProperties['active_section'] = $active_section = (is_null($modx->getPlaceholder('active_section'))) ? $defaultSection : $modx->getPlaceholder('active_section');
//
$up->initialize($modx->context->key, $scriptProperties);
$isAuthenticated = $modx->user->isAuthenticated($modx->context->key);
//
if(!$allowGuest && !$isAuthenticated && !empty($ReturnTo)) {
	$modx->sendRedirect($ReturnTo);
}
elseif (!$allowGuest && !$isAuthenticated) {
	return $modx->lexicon('up_allow_guest_err');
}
//
if(empty($defaultSection)) {$defaultSection = 'info';}
// default properties
$default = array(
	'fastMode' => false,
	'nestedChunkPrefix' => 'up_',
);
// Merge all properties
$up->pdoTools->setConfig(array_merge($default, $scriptProperties), false);
// get user fields
$row = $up->getUserFields($scriptProperties['user_id']);
$row = $up->prepareData($row);
// sections
$allowedSections = $up->getAllowedSections();
foreach ($allowedSections as $section) {
	$row['section'] = $section;
	$row['sectiontitle'] = $modx->lexicon('up_section_title_'.$section);
	$row['active'] = ($section == $active_section) ? 'active' : '';
	$row['rows'] .= empty($tplSectionRow)
		? $up->pdoTools->getChunk('', $row)
		: $up->pdoTools->getChunk($tplSectionRow, $row, $up->pdoTools->config['fastMode']);
}
$outer['section'] = empty($tplSectionOuter)
	? $up->pdoTools->getChunk('', $row)
	: $up->pdoTools->getChunk($tplSectionOuter, $row, $up->pdoTools->config['fastMode']);
// Preparing filters
$tmp = array();
$tmp_filters = array_map('trim', explode(',', $filters));
foreach($tmp_filters as $v) {
	if (empty($v)) {
		continue;
	}
	elseif(strpos($v, $active_section.$up->config['delimeterSection']) !== false) {@
	list($section, $action) = explode($up->config['delimeterSection'], $v);
	}
	$tmp = explode($up->config['delimeterAction'], $action);
}
if(empty($section)) {$section = $defaultSection;}
// content
$content = $up->getContent($tmp, $row, $scriptProperties);
$outer['content'] = empty($tplSectionContent)
	? $up->pdoTools->getChunk('', array('content' => $content))
	: $up->pdoTools->getChunk($tplSectionContent, array('content' => $content), $up->pdoTools->config['fastMode']);
// output
$outer = array_merge($row, $outer);
$output = empty($tplUserProfile)
	? $up->pdoTools->getChunk('', $outer)
	: $up->pdoTools->getChunk($tplUserProfile, $outer, $up->pdoTools->config['fastMode']);
if (!empty($tplWrapper) && (!empty($wrapIfEmpty) || !empty($output))) {
	$output = $up->pdoTools->getChunk($tplWrapper, array('output' => $output), $up->pdoTools->config['fastMode']);
}
if (!empty($toPlaceholder)) {
	$modx->setPlaceholder($toPlaceholder, $output);
} else {
	return $output;
}