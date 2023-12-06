<?php

namespace Zirvu\Api\Utils;

use Illuminate\Support\Facades\Validator;

class UtilsService
{
    
    public function getWith($list = [])
    {
        $result = [];

        foreach ($list as $key => $value) {
            $cek_with = explode("with_", $key);

            if( count($cek_with) == 2 && $value == 1 ) {
                $result[] = "data_".$cek_with[1];
            }
        }
        return $result;
    }

    public function generateGetObject(object $object)
    {
        return (object)[
            "take" => $object->take ?? 10,
            "page" => $object->page ?? 1,
            "filter" => $object->filter ?? "",
            "order" => $object->order ?? "",
            "order_method" => $object->order_method ?? "",
            "with" => $this->getWith($object)
        ];
    }

    public function validation($rules, $data)
    {
        extract($rules);
        $rule = $rule_public ?? [];

        if(!isset($data["id"])) $rule = array_merge($rule, $rule_new ?? []);
        else $rule = array_merge($rule, $rule_edit ?? []);

        $validate = Validator::make($data, $rule);

        return $validate;
    }

}