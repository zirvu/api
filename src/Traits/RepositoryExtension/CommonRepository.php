<?php

namespace Zirvu\Api\Traits\RepositoryExtension;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\RelationNotFoundException;

use \DB;
use \Exception;

trait CommonRepository
{
    public $pagination = [
        "take" => 10,
        "page" => 1,
        "totalPage" => 0,
        "total" => 0
    ];
    public $filter = "";
    public $order = "";
    public $order_method = "";
    public $data;

    public function get(object $object, string $type)
    {
        $this->pagination["take"] = $object->take ?? 10;
        $this->pagination["page"] = $object->page ?? 1;
        $this->filter = $object->filter ?? "";
        $this->order = $object->order ?? "";
        $this->order_method = $object->order_method ?? "";

        $data = null;
        try {

            $this->model = $this->model->with($object->with ?? []);
            $data = $this->getData($type);
            
        } catch (RelationNotFoundException $e) {
            throw new Exception($e->getMessage(), 1);
        } catch (QueryException $e) {
            throw new Exception($e->getMessage(), 1);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $data;
    }

    public function find($id)
    {
        return $this->getOne($id);
    }

    public function first(object $object)
    {
        $data = null;
        try {

            $this->filter = $object->filter ?? "";
            $this->order = $object->order ?? "";
            $this->order_method = $object->order_method ?? "";
            $data = $this->getFirst();
            
        } catch (RelationNotFoundException $e) {
            throw new Exception($e->getMessage(), 1);
        } catch (QueryException $e) {
            throw new Exception($e->getMessage(), 1);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }

        return $data;
    }

    public function save($id, array $fields)
    {
        $data = null;

        try {

            $data = $this->beforeSave($id, $fields);
            $data->save();
            
        } catch (QueryException $e) {
            throw new Exception($e->getMessage(), 1);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }

        return $data;
    }

    public function delete($id)
    {
        $result = true;

        try {

            $data = $this->find($id);

            if ( $data != null ) {

                $cek = $data->checkDelete();

                if ( $cek ) {
                    $result = $data->delete();
                } else {
                    return false;
                }

            }
            
        } catch (QueryException $e) {
            throw new Exception($e->getMessage(), 1);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 1);
        }
        return $result;
    }
}