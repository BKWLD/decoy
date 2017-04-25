<?php

return [

    'controller.title' => 'Redirects',
    'controller.description' => 'Rules that redirect an internal URL path to another.',
    'controller.column.rule' => 'Rule',
    'controller.search.from' => 'from',
    'controller.search.to' => 'to',
    'controller.search.code' => 'code',
    'controller.search.label' => 'label',

    'model.301' => '301 - Permanent',
    'model.302' => '302 - Temporary',

    'legend.new' => 'New',
    'legend.edit' => 'Edit',

    'form.from' => 'From',
    'form.from_help' => 'A URL path, beginning after ":root/". This can contain wildcards in the form of a <code>%</code>. For instance, to match all URLs beginning with "blog/", use <code>blog/%</code>.',
    'form.from_regex_help' => 'For more complex matches, you may also use <a href="https://dev.mysql.com/doc/refman/5.1/en/regexp.html#operator_regexp" target="_blank">mysql regular expressions</a> like <code>^blog/.+$</code>',

    'form.to' => 'To',
    'form.to_help' => 'An absolute path ( <code>/insight/example</code> ) or url ( <code>http://domain.com/path?id=num</code> ).',

    'form.code' => 'Code',
    'form.radio_help' => 'How should browsers treat this redirect.',

    'form.label' => 'Label',
    'form.label_help' => 'An optional internal label used to identify this <b>Rule</b> in the Admin.',

];
