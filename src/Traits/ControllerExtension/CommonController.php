<?php

namespace Zirvu\Api\Traits\ControllerExtension;

use Illuminate\Http\Request;
use \Exception;

trait CommonController
{
    public $success = true;
    public $message = "Success!";
    public $pagination = [
        "take" => 10,
        "page" => 1,
        "totalPage" => 0,
        "total" => 0,
        "filter" => ""
    ];
    public $data = [];

    public function response($statusCode, $withPaginate = false)
    {
        $response = [
            "success" => $this->success,
            "message" => $this->message,
            "data" => $this->data,
        ];

        if($withPaginate){
            $response = [
                "success" => $this->success,
                "message" => $this->message,
                "pagination" => $this->pagination,
                "data" => $this->data,
            ];
        }

        return response()->json($response, $statusCode);
    }

    public function data(Request $request)
    {
        $statusCode = 200;

        try {
            $this->data = $this->baseService->get((object)$request->all());
            $this->pagination = $this->baseService->extra["pagination"] ?? [];
        } catch (Exception $e) {
            $statusCode = 500;
            $this->success = false;
            $this->message = $e->getMessage();
            $this->data = [];
        }
        return $this->response($statusCode, ($statusCode == 200));
    }

    public function one(Request $request, $id)
    {
        $this->data = $this->baseService->find($id);

        return $this->response(200);
    }

    public function first(Request $request)
    {
        $statusCode = 200;

        try {
            $this->data = $this->baseService->first((object)$request->all());
        } catch (Exception $e) {
            $statusCode = 500;
            $this->success = false;
            $this->message = $e->getMessage();
            $this->data = [];
        }

        return $this->response(200);
    }

    public function save(Request $request)
    {
        $statusCode = 200;

        try {
            $id = $request->id ?? null;
            $fields = $request->all();

            $data = $this->baseService->save($id, $fields);

            if ( !$data ) {
                $statusCode = 400;

                $this->success = false;
                $this->message = $this->baseService->message;
                $this->data = $this->baseService->data;

            } else {
                $this->data = $this->one($request, $data->id)->original["data"];
            }
            
        } catch (Exception $e) {
            $statusCode = 500;
            $this->success = false;
            $this->message = $e->getMessage();
            $this->data = $this->baseService->data;
        }

        return $this->response($statusCode);
    }

    public function update(Request $request)
    {
        $statusCode = 200;

        try {
            $id = $request->id ?? null;
            $fields = $request->all();

            $data = $this->baseService->update($id, $fields);

            if ( !$data ) {
                $statusCode = 400;

                $this->success = false;
                $this->message = $this->baseService->message;
                $this->data = $this->baseService->data;

            } else {
                $this->data = $this->one($request, $data->id)->original["data"];
            }
            
        } catch (Exception $e) {
            $statusCode = 500;
            $this->success = false;
            $this->message = $e->getMessage();
            $this->data = $this->baseService->data;
        }

        return $this->response($statusCode);
    }

    public function delete(Request $request)
    {
        $statusCode = 200;

        try {

            $this->success = $this->baseService->delete($request->data ?? []);

            if ( !$this->success ) {
                $statusCode = 400;
                $this->message = "Some data can't be delete";
            }
            
        } catch (Exception $e) {
            $statusCode = 500;
            $this->success = false;
            $this->message = $e->getMessage();
            $this->data = null;
        }

        return $this->response($statusCode);
    }
}