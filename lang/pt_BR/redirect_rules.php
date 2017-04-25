<?php

return [

    'controller.title' => 'Redirecionamentos',
    'controller.description' => 'Regras que redirecionam um URL interno para outro URL.',
    'controller.column.rule' => 'Regra',
    'controller.search.from' => 'de',
    'controller.search.to' => 'para',
    'controller.search.code' => 'código',
    'controller.search.label' => 'etiqueta',

    'model.301' => '301 - Permanente',
    'model.302' => '302 - Temporário',

    'legend.new' => 'Nova',
    'legend.edit' => 'Editar',

    'form.from' => 'De',
    'form.from_help' => 'Um caminho de URL, começando depois ":root/". Isso pode conter curingas na forma de um <code>%</code>. Por exemplo, para combinar todos os URLs que começam com "blog/", use <code>blog/%</code>.',
    'form.from_regex_help' => 'Para combinações mais complexas, você também pode usar <a href="https://dev.mysql.com/doc/refman/5.1/en/regexp.html#operator_regexp" target="_blank">expressões regulares do mysql</a> como <code>^blog/.+$</code>',

    'form.to' => 'Para',
    'form.to_help' => 'Um caminho absoluto ( <code>/insight/example</code> ) ou um URL ( <code>http://domain.com/path?id=num</code> ).',

    'form.code' => 'Código',
    'form.radio_help' => 'Como os navegadores devem tratar esse redirecionamento.',

    'form.label' => 'Etiqueta',
    'form.label_help' => 'Uma etiqueta interna opcional usada para identificar esta <b>regra</b> no Admin.',

];
