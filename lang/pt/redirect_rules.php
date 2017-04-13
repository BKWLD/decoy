<?php

return [

    'legend.new' => 'New',
    'legend.edit' => 'Edit',

    'form.from_help' => 'Um caminho de URL, começando depois "'.Request::root().'/". Isso pode conter curingas na forma de um <code>%</code>. Por exemplo, para coincidir com todos os URLs que começam com "blog /", use <code>blog/%</code>.',

    'form.from_regex_help' => 'Para jogos mais complexos, você também pode usar <a href="https://dev.mysql.com/doc/refman/5.1/en/regexp.html#operator_regexp" target="_blank">Expressões regulares do mysql</a> como <code>^blog/.+$</code>',

    'form.to_help' => 'Um caminho absoluto ( <code>/insight/example</code> ) ou url ( <code>http://domain.com/path?id=num</code> ).',
    'form.radio_help' => 'Como os navegadores devem tratar esse redirecionamento.',
    'form.label_help' => 'Um rótulo interno opcional usado para identificar esta <b>regra</b> no.',

];
