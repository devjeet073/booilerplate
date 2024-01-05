<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller as Controller;

class ResponseController extends Controller
{
    public function _sendResponse($msg="",$data=[]){
        $response = [
            'code' => 200,
            'status' => true,
            'message' => $msg,
            'data' => $data,
            "timestamp" => time()
        ];
        return response()->json($response, 200);
    }
    public function _sendErrorResponse($msg="",$data=[],$errorCode=200,$status=false){
       $response = [
            'code' => $errorCode,
            'status' => $status,
            'message' => $msg,
            'data' => (Object)$data,
            "timestamp" => time()
        ];
        return response()->json($response, 200);
    }
}
