<?php

namespace Zirvu\Api\Traits\ServiceExtension;

use \DB;
use \Exception;

trait CommonService
{
    protected $baseRepository;

    public $message;
    public $id;
    public $data;

    public $rules = [];
    public $rule_new = [];
    public $rule_edit = [];
    public $rule_public = [];

    public function get(object $object)
    {
        try {
            $this->loadUtilsService();

            $object = $this->utilsService->generateGetObject($object);
            $this->baseRepository->get($object, $this->type);
            $this->extra["pagination"] = $this->baseRepository->pagination;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $this->baseRepository->data;
    }

    public function find($id)
    {
        return $this->baseRepository->find($id);
    }

    public function first(object $object)
    {
        $data = null;

        try {
            $this->loadUtilsService();

            $object = $this->utilsService->generateGetObject($object);
            $data = $this->baseRepository->first($object);

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $data;
    }
    
    public function save($id, array $fields)
    {
        $this->loadUtilsService();
        $this->buildValidation($fields);
        $validator = $this->utilsService->validation($this->rules, $fields);
        
        if ($validator->fails()) {
            $this->message = $validator->errors()->first();
            $this->data = $validator->errors();
            throw new Exception($this->message, 1);
        }

        DB::beginTransaction();

        try {
            $data = $this->baseRepository->save($id, $fields);

            DB::commit();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $data;
    }
    
    public function update($id, array $fields)
    {
        try {
            $data = $this->baseRepository->find($id);
            if ( !$data ) {
                throw new Exception("Data not found", 1);
            }
            $fieldsData = $data->toArray();

            foreach ($fields as $key => $value) {
                $fieldsData[$key] = $value;
            }

            // $this->save($id, $fieldsData);
            $data = $this->baseRepository->save($id, $fieldsData);

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $data;
    }

    public function delete(array $ids)
    {
        DB::beginTransaction();

        $result = false;

        try {
            foreach ($ids as $key => $id) {
                $result = $this->baseRepository->delete($id);

                if ( !$result ) {
                    return false;
                }
            }

            DB::commit();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $result;
    }

}