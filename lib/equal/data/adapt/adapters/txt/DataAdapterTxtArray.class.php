<?php
/*
    This file is part of the eQual framework <https://github.com/equalframework/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2024
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace equal\data\adapt\adapters\txt;

use equal\data\adapt\DataAdapter;

class DataAdapterTxtArray implements DataAdapter {

    public function getType() {
        return 'txt/array';
    }

    public function castInType(): string {
        return 'array';
    }

    public function castOutType(): string {
        return '';
    }
    /**
     * Handles the conversion to the PHP type equivalent.
     * Adapts the input value from TXT type to PHP type (TXT -> PHP).
     *
     * @param mixed         $value      Value to be adapted.
     * @param string|Usage  $usage      The usage descriptor the adaptation is requested for.
     * @param string        $locale     The locale to be used for adaptation.
     * @return mixed
     */
	public function adaptIn($value, $usage, $locale='en') {
        $result = null;
        if(!is_null($result)) {
            $result = explode(',', $value);
        }
        return (array) $result;
    }

    /**
     * Handles the conversion to the type targeted by the DataAdapter.
     * Adapts the input value from PHP type to TXT type (PHP -> TXT).
     *
     * @param mixed         $value      Value to be adapted.
     * @param string|Usage  $usage      The usage descriptor the adaptation is requested for.
     * @param string        $locale     The locale to be used for adaptation.
     * @return mixed
     */
    public function adaptOut($value, $usage, $locale='en') {
        $result = $value;
        if(!is_array($result)) {
            $result = explode(',', $result);
        }
        return $result;
    }

}
