<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
list($params, $providers) = announce([
    'description'	=>	"Returns values map of the specified fields for object matching given class and identifier.",
    'params' 		=>	[
        'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to look into (e.g. \'core\\User\').',
            'type'          => 'string', 
            'required'      => true
        ],
        'ids' =>  [
            'description'   => 'List of unique identifiers of the objects to read.',
            'type'          => 'array',
            'required'      => true
        ],
        'fields' =>  [
            'description'   => 'Requested fields. If not specified, only \'id\' and \'name\' fields are returned.',
            'type'          => 'array', 
            'default'       => ['id', 'name']
        ],
        'lang' =>  [
            'description'   => 'Language in which multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string', 
            'default'       => DEFAULT_LANG
        ],
        'order' => [
            'description'   => 'Column to use for sorting results.',
            'type'          => 'string',
            'default'       => 'id'
        ],
        'sort' => [
            'description'   => 'The direction  (i.e. \'asc\' or \'desc\').',
            'type'          => 'string',
            'default'       => 'asc'
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context' ]  
]);

list($context) = [ $providers['context'] ];


if(!class_exists($params['entity'])) {
    throw new Exception("unknown_entity", QN_ERROR_UNKNOWN_OBJECT);
}

// adapt received fields names for dot notation support
$fields = [];
foreach($params['fields'] as $field) {
    // dot notation
    if(strpos($field, '.')) {
        $parts = explode('.', $field);
        if(!isset($fields[$parts[0]])) {
            $fields[$parts[0]] = [];
        }
        $fields[$parts[0]][] = $parts[1];
    }
    // regular field name
    else {
        $fields[] = $field;
    }
}

// get the sorted collection
$collection = $params['entity']::search(['id', 'in', $params['ids']], [ 'sort' => [ $params['order'] => $params['sort'] ] ]);

$result = $collection
          ->read($fields, $params['lang'])
          ->adapt('txt')
          // return result as an array (JSON objects handled by ES2015+ might have their keys order altered)
          ->get(true);

$context->httpResponse()
        ->body($result)
        ->send();