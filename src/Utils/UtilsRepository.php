<?php

namespace Zirvu\Api\Utils;

use Illuminate\Http\Request;

class UtilsRepository
{
    // id:>:0;code:contains:a||name:contains:b;id:>:0
    public function build($query, $field, $type, $value)
    {
        $temp = explode("||", $value);
        if(count($temp) == 1) return $this->default_build($query, $field, $type, $value);

        $list_filter = [];
        $list_filter[] = [
            "field" => $field,
            "type" => $type,
            "value" => $temp[0],
        ];

        foreach($temp as $key => $dt){
            if($key > 0){
                $proses = $this->prosesFilter($dt);

                if($proses->valid){
                    $list_filter[] = [
                        "field" => $proses->field,
                        "type" => $proses->type,
                        "value" => $proses->value,
                    ];
                }
            }
        }

        $query = $query->where(function($q) use($list_filter){
            foreach($list_filter as $index => $filter){
                $field = $filter['field'];
                $type = $filter['type'];
                $value = $filter['value'];

                $cek = explode(".", $field);

                if(count($cek) > 1){
                    $field = array_pop($cek);
                    $string = implode('.', $cek);

                    if($index == 0) $q = $this->default_build($q, $field, $type, $value);
                    else $q = $this->or_build($q, $field, $type, $value);
                    
                }else{
                    if($index == 0) $q = $this->default_build($q, $field, $type, $value);
                    else $q = $this->or_build($q, $field, $type, $value);
                }
            }
        });

        return $query;
    }

    public function default_build($query, $field, $type, $value)
    {
        if($type == "=" || $type == "equals"){
            if($value != "Null") $query = $query->where($field, $value);
            else $query = $query->whereNull($field);
        }
        else if($type == "!=" || $type == "notequals"){
            if($value != "Null") $query = $query->where($field, '!=', $value);
            else $query = $query->whereNotNull($field);
        }
        else if($type == ">" || $type == "gt") $query = $query->where($field, ">", $value);
        else if($type == "<" || $type == "lt") $query = $query->where($field, "<", $value);
        else if($type == ">=" || $type == "gte") $query = $query->where($field, ">=", $value);
        else if($type == "<=" || $type == "lte") $query = $query->where($field, "<=", $value);
        else if($type == '%$%' || $type == "contains") $query = $query->where($field, "LIKE", '%'.$value.'%');
        else if($type == '$%' || $type == "startswith") $query = $query->where($field, "LIKE", $value.'%');
        else if($type == '%$' || $type == "endswith") $query = $query->where($field, "LIKE", '%'.$value);
        else if($type == 'in'){
            if(is_array($value)) $query = $query->whereIn($field, $value);
            else $query = $query->whereIn($field, explode(",", $value));
        }
        else if($type == 'notin'){
            if(is_array($value)) $query = $query->whereNotIn($field, $value);
            else $query = $query->whereNotIn($field, explode(",", $value));
        }
        else if($type == 'between') $query = $query->whereBetween($field, explode(",", $value));

        return $query;
    }

    public function or_build($query, $field, $type, $value)
    {
        if($type == "=" || $type == "equals"){
            if($value != "Null") $query = $query->orWhere($field, $value);
            else $query = $query->orWhereNull($field);
        }
        if($type == "!=" || $type == "notequals"){
            if($value != "Null") $query = $query->orWhere($field, '!=', $value);
            else $query = $query->orWhereNotNull($field);
        }
        else if($type == ">" || $type == "gt") $query = $query->orWhere($field, ">", $value);
        else if($type == "<" || $type == "lt") $query = $query->orWhere($field, "<", $value);
        else if($type == ">=" || $type == "gte") $query = $query->orWhere($field, ">=", $value);
        else if($type == "<=" || $type == "lte") $query = $query->orWhere($field, "<=", $value);
        else if($type == '%$%' || $type == "contains") $query = $query->orWhere($field, "LIKE", '%'.$value.'%');
        else if($type == '$%' || $type == "startswith") $query = $query->orWhere($field, "LIKE", $value.'%');
        else if($type == '%$' || $type == "endswith") $query = $query->orWhere($field, "LIKE", '%'.$value);
        return $query;
    }

    public function prosesFilter($filter)
    {
        $valid = false;
        $field = "";
        $type = "";
        $value = "";

        $temp = explode(":", $filter);
        if(count($temp) >= 3)
        {
            $field = $temp[0];
            $type = $temp[1];
            $value = $temp[2];

            if($field != "" && $type != "" && $value != ""){
                $valid = true;
            }

            if(count($temp) >= 3){
                $value = "";
                for($i=2;$i<count($temp);$i++){
                    if($value != "") $value .= ":";
                    $value .= $temp[$i];
                }
            }
        }
        return (object)[
            "valid" => $valid,
            "field" => $field,
            "type" => $type,
            "value" => $value,
        ];
    }

    public function indexFilter($filters, $filter, $check = true)
    {
        $list_filter = explode(";", $filter);
        foreach($list_filter as $key => $string)
        {
            $proses = $this->prosesFilter($string);
            $loop = false;
            if($check){
                if($proses->valid){
                    $loop = true;
                }
            }else{
                $loop = true;
            }
            if($loop){
                foreach($filters as $index => $detail){
                    if($detail->field == $proses->field && $detail->type == $proses->type){
                        $filters[$index]->value = $proses->value;
                    }
                }
            }
        }
        return $filters;
    }

    public function order($builder, $order, $order_method = "ASC")
    {
        if($order != "") $builder->orderBy($order, $order_method);

        return $builder;
    }

	public function filter($builder, $filter, $order, $order_method = "ASC")
	{
        $builder = $this->order($builder, $order, $order_method);
        $helper = new $this;

        $list_filter = explode(";", $filter);

        foreach($list_filter as $key => $filter)
        {
            $proses = $this->prosesFilter($filter);
            if($proses->valid)
            {
                $field = $proses->field;
                $type = $proses->type;
                $value = $proses->value;
                $cek = explode('.', $field);

                if(count($cek) > 1){
                    $field = array_pop($cek);
                    $string = implode('.', $cek);

                    $builder = $builder->whereHas($string, function($query) use($field, $type, $value, $helper){
                        $query = $helper->build($query, $field, $type, $value);
                    });
                }else{
                    $builder = $helper->build($builder, $field, $type, $value);
                }
            }
        }

		return $builder;
	}

}