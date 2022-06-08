<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

use core\setting\Setting;

use core\User;

list($params, $providers) = announce([
    'description'   => "Returns a view populated with a collection of objects, and outputs it as an XLS spreadsheet.",
    'params'        => [
        'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to use (e.g. \'core\\User\').',
            'type'          => 'string', 
            'required'      => true
        ],
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string', 
            'default'       => 'list.default'
        ],
        'domain' =>  [
            'description'   => 'Domain for filtering objects to include in the export.',
            'type'          => 'array',
            'default'       => []
        ],
        'params' => [
            'description'   => 'Additional params to relay to the data controller.',
            'type'          => 'array',
            'default'       => []
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string', 
            'default'       => DEFAULT_LANG
        ]        
    ],
    'response'      => [
        'accept-origin' => '*'        
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// retrieve target entity
$entity = $orm->getModel($params['entity']);
if(!$entity) {
    throw new Exception("unknown_entity", QN_ERROR_INVALID_PARAM);
}

// get the complete schema of the object (including special fields)
$schema = $entity->getSchema();

// retrieve view schema
$json = run('get', 'model_view', [
    'entity'        => $params['entity'], 
    'view_id'       => $params['view_id']
]);

// decode json into an array
$data = json_decode($json, true);

// relay error if any
if(isset($data['errors'])) {
    foreach($data['errors'] as $name => $message) {
        throw new Exception($message, qn_error_code($name));
    }
}

if(!isset($data['layout']['items'])) {
    throw new Exception('invalid_view', QN_ERROR_INVALID_CONFIG);
}

$view_fields = [];

foreach($data['layout']['items'] as $item) {
    if(isset($item['type']) && isset($item['value']) && $item['type'] == 'field') {
        $view_fields[] = $item;
    }
}


/*
    Read targeted objects
*/

$fields_to_read = [];

// adapt fields to force retrieving name for m2o fields
foreach($view_fields as $item) {
    $field =  $item['value'];
    $descr = $schema[$field];
    if($descr['type'] == 'many2one') {
        $fields_to_read[$field] = ['id', 'name'];
    }
    else {
        $fields_to_read[] = $field;
    }
}

$limit = (isset($params['params']['limit']))?$params['params']['limit']:25;
$start = (isset($params['params']['start']))?$params['params']['start']:0;
$order = (isset($params['params']['order']))?$params['params']['order']:'id';
$sort = (isset($params['params']['sort']))?$params['params']['sort']:'asc';
$values = $params['entity']::search($params['domain'], ['sort' => [$order => $sort]])->shift($start)->limit($limit)->read($fields_to_read)->get();

/*
    Retrieve translation data, if any
*/

$json = run('get', 'config_i18n', [
    'entity'        => $params['entity'], 
    'lang'          => $params['lang']
]);

// decode json into an array
$data = json_decode($json, true);
$translations = [];
if(!isset($data['errors']) && isset($data['model'])) {
    foreach($data['model'] as $field => $descr) {
        $translations[$field] = $descr;
    }
}

// retrieve view title
$view_title = $view_schema['name'];
$view_legend = $view_schema['description'];
if(isset($i18n['view'][$params['view_id']])) {
    $view_title = $i18n['view'][$params['view_id']]['name'];
    $view_legend = $i18n['view'][$params['view_id']]['description'];
}


/*
    Fetch settings
*/

$settings = [
    'date_format'       => Setting::get_value('core', 'locale', 'date_format', 'm/d/Y'),
    'time_format'       => Setting::get_value('core', 'locale', 'time_format', 'H:i'),
    'format_currency'   => function($a) { return Setting::format_number_currency($a); }
];



$doc = new Spreadsheet();

$user = User::id($auth->userId())->read(['id', 'login'])->first();

$doc->getProperties()
      ->setCreator($user['login'])
      ->setTitle('Export')
      ->setDescription('Exported with eQual library');

$doc->setActiveSheetIndex(0);

$sheet = $doc->getActiveSheet(); 
$sheet->setTitle("export");


$column = 'A';
$row = 1;

// generate head row
foreach($view_fields as $item) {
    $field = $item['value'];
    $width = (isset($item['width']))?intval($item['width']):0;
    if($width <= 0) {
        continue;
    }
    $name = isset($translations[$field]['label'])?$translations[$field]['label']:$field;
    $sheet->setCellValue($column.$row, $name);
    $sheet->getColumnDimension($column)->setAutoSize(true);
    $sheet->getStyle($column.$row)->getFont()->setBold(true);
    ++$column;
}

foreach($values as $oid => $odata) {
    ++$row;
    $column = 'A';
    foreach($view_fields as $item) {
        $field = $item['value'];
        $width = (isset($item['width']))?intval($item['width']):0;
        if($width <= 0) {
            continue;
        }

        $value = $odata[$field];

        $type = $schema[$field]['type'];
        // #todo - handle 'alias'
        if($type == 'computed') {
            $type = $schema[$field]['result_type'];
        }

        $usage = (isset($schema[$field]['usage']))?$schema[$field]['usage']:'';
        $align = 'left';

        // for relational fields, we need to check if the Model has been fetched
        if(in_array($type, ['one2many', 'many2one', 'many2many'])) {
            // by convention, `name` subfield is always loaded for relational fields
            if($type == 'many2one' && isset($value['name'])) {
                $value = $value['name'];
            }
            else {
                $value = "...";
            }
            if(is_numeric($value)) {
                $align = 'right';
            }
        }
        else if($type == 'date') {
            // #todo - convert using PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel
            $align = 'center';
            $value = date($settings['date_format'], $value);
        }
        else if($type == 'time') {
            $align = 'center';
            $value = date($settings['time_format'], strtotime('today') + $value);
        }
        else if($type == 'datetime') {
            $align = 'center';
            $value = date($settings['date_format'].' '.$settings['time_format'], $value);
        }
        else {
            if($type == 'string') {
                $align = 'center';
            }
            else {
                if(strpos($usage, 'amount/money') === 0) {
                    $align = 'right';
                    $value = $settings['format_currency']($value);
                }
                if(is_numeric($value)) {
                    $align = 'right';
                }                
            }
        }

        // handle html content
        if($type == 'string' && strlen($value) && $usage == 'text/html') {
            $align = 'left';
            $$value = strip_tags(str_replace(['</p>', '<br />'], "\r\n", $value));            
        }
        else {
            // translate 'select' values
            if($type == 'string' && isset($schema[$field]['selection'])) {
                if(isset($translations[$field]) && isset($translations[$field]['selection'])) {
                    $value = $translations[$field]['selection'][$value];
                }
            }
            $value =  $value;
        }


        $sheet->setCellValue($column.$row, $value);
        ++$column;
    }    
}

$writer = IOFactory::createWriter($doc, "Xlsx");

ob_start();	
$writer->save('php://output');
$output = ob_get_clean();

$context->httpResponse()
        ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->header('Content-Disposition', 'inline; filename="export.xlsx"')
        ->body($output)
        ->send();