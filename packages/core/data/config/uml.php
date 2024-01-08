<?php

list($params, $providers) = eQual::announce([
    'description'   => "Attempts to create a new package using a given name.",
    'params'        => [
        'package'   => [
            'description'   => 'Name of the package of the new model',
            'type'          => 'string',
            'required'      => true
        ],
        'path' =>  [
            'decription'    => 'relative path to the file from packages/{pkg}/',
            'type'          => 'string',
            'required'      => true
        ],
        'type'      => [
            'description'   => 'Type of the UML data',
            'type'          => 'string',
            'required'      => true,
            'selection'     => [
                'or'
            ]
        ]
    ],
    'response'      => [
        'content-type'  => 'text',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['admin']
    ],
    'providers'     => ['context', 'orm', 'access']
]);

/**
 * @var \equal\php\Context              $context
 * @var \equal\orm\ObjectManager        $orm
 * @var \equal\access\AccessController  $ac
 */
list($context, $orm, $ac) = [$providers['context'], $providers['orm'], $providers['access']];

$response_code = 200;

$package = $params["package"];

// Checking if package exists
if(!file_exists(QN_BASEDIR."/packages/{$package}")) {
    throw new Exception('missing_package_dir', QN_ERROR_INVALID_PARAM);
}

// Checking if package exists
if(!file_exists(QN_BASEDIR."/packages/{$package}/uml")) {
    throw new Exception('malformed_package', QN_ERROR_INVALID_CONFIG);
}

$path_arr = explode('/',$params['path']);
$filename = array_pop($path_arr);
$path = implode("/",$path_arr);

$path = str_replace("..","",$path);

if(!endsWith($filename,".{$params["type"]}.equml")) {
    $filename = $filename.".{$params["type"]}.equml";
}

if(!file_exists(QN_BASEDIR."/packages/{$package}/uml/{$path}/{$filename}")) {
    throw new Exception('io_error', QN_ERROR_INVALID_CONFIG);
}

$context->httpResponse()
        ->body(file_get_contents(QN_BASEDIR."/packages/{$package}/uml/{$path}/{$filename}"))
        ->status(200)
        ->send(); 

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}