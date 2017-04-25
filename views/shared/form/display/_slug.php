<?php

// Require a slug
if (empty($item->slug)) return;

// If no route is defined, hide the slug interface
$url = $item->getUriAttribute();
if (!$url) return;

// If the URL is to uploads dir, hide the slug interface
if (preg_match('#^/uploads#', $url)) return;

// If the URL is to an external domain, hide the slug inteface
if (preg_match('#^https?://#', $url) && strpos($url, Request::root()) === false) return;

// Form the prefix
$url_link = '<a href="'.$url.'" target="_blank">URI</a>';
$prepend = preg_replace('#/[\w-\.]+$#', '/', parse_url(rtrim($url,'/'), PHP_URL_PATH));

// Render the field
echo Former::text('slug', __('decoy::display.slug.label'))
    ->blockHelp(__('decoy::display.slug.help', ['url_link' => $url_link]))
    ->prepend($prepend);
