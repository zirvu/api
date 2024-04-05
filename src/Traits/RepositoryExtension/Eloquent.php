<?php

namespace Zirvu\Api\Traits\RepositoryExtension;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

use Zirvu\Api\Utils\UtilsRepository;

trait Eloquent
{
    protected $baseModel;
    protected $utilsRepository;

    public function makeModel()
    {
        $this->baseModel = clone $this->model;
    }

    public function loadUtilsRepository()
    {
        $this->utilsRepository = app(UtilsRepository::class);
    }

    public function setPagination($status = true, $type="api")
    {
        if($status) {
            $this->pagination['total'] = $this->baseModel->count();
            $this->pagination['totalPage'] = ceil($this->pagination['total'] / $this->pagination['take']);

            if($type == "web") {
                $this->data = $this->baseModel->paginate($this->pagination['take']);
            } else {
                $this->data = $this->baseModel->take($this->pagination['take'])
                    ->skip(($this->pagination['page'] - 1) * $this->pagination['take'])
                    ->get();
            }
        } else {
            $this->data = $this->baseModel;
        }
                
        return $this->pagination;
    }

    public function getColumnsFromTable($extend = false)
    {
        $result = [];
        $exclude = [
            "created_at", "updated_at", "deleted_at", 
            "input_by", "edit_by", "id"
        ];
        $columns = Schema::getColumnListing($this->table_name);
        $result = [];

        foreach ($columns as $field) {
          $columnDetails = \DB::select('DESCRIBE ' . $this->table_name . ' `' . $field . '`')[0];
          $result[$field] = [
            "field" => $field,
            "type" => $columnDetails->Type,
            "isNullable" => $columnDetails->Null === 'YES',
            "default" => $columnDetails->Default,
          ];
        }

        if(!$extend) {
            foreach ($exclude as $key => $field) {
                unset($result[$field]);
            }
        }

        return $result;
    }

    public function getData($type="api")
    {
        $this->makeModel();
        $this->loadUtilsRepository();
        $this->baseModel = $this->utilsRepository->filter(
            $this->baseModel, $this->filter, 
            $this->order, $this->order_method
        );

        $this->setPagination(true, $type);
        return $this->data;
    }

    public function getOne($id)
    {
        $this->makeModel();
        $this->data = $this->baseModel->with($this->with)->find($id);
        return $this->data;
    }

    public function getFirst()
    {
        $this->makeModel();
        $this->loadUtilsRepository();
        $this->baseModel = $this->utilsRepository->filter(
            $this->baseModel, $this->filter, 
            $this->order, $this->order_method
        );
        $this->baseModel = $this->baseModel->with($this->with);
        $this->data = $this->baseModel->first();

        return $this->data;
    }

    public function generateBuilder($builder, $table_detail, $ignore_save, $fields)
    {
        foreach ($table_detail as $field => $detail) {
            if(!in_array($field, $ignore_save)) {
                $builder->$field = $fields[$field] ?? $detail["default"];
            }
        }
        return $builder;
    }

    public function beforeSave($id, array $fields)
    {
        $this->makeModel();
        if ( $id == null || $id == "" || $id == 0 ) $builder = new $this->baseModel;
        else $builder = $this->getOne($id);

        return $this->generateBuilder($builder, $this->table_detail, $this->ignore_save, $fields);
    }

}
