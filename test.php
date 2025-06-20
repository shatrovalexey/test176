<?php
$output = &$modx->resource->_output;

if ($modx->event->name != 'OnWebPagePrerender') return $output;

$sgh = new SchemaGenerator($modx->makeUrl($modx->resource->id), $modx->getOption('site_name'));
$ldJsonContent = $sgh->execute($output);
$ldJsonContent = json_encode($ldJsonContent, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
$output = preg_replace('{(</body>)}uis', "<script type='application/ld+json'>{$ldJsonContent}</script>$1", $output);

return $output;