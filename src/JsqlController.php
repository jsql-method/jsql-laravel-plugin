<?php

namespace Jsql\LaravelPlugin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;


class JsqlController extends Controller
{

    public static function callJsqlApi($data, $method, $endpoint) {
        $apiKey = Config::get('jsql.apiKey');
        $memberKey = Config::get('jsql.memberKey');
        $apiUrl = Config::get('jsql.apiUrl');
        $headers = [
            'Content-Type' => 'application/json',
            'apiKey' => $apiKey,
            'memberKey' => $memberKey,
        ];

        $client = new Client();
        $res = $client->request(
            $method,
            $apiUrl . $endpoint ,
            [
                'headers' => $headers,
                'body' => json_encode($data),
            ]
        );

        return json_decode((string)$res->getBody(), true);
    }

    public function select(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'params' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => $validator->getMessageBag()->first(),
            ], 400);
        }
        $params = null !== $request->input('params') ?  $request->input('params') : [];

        try {
            if(is_array($request->input('token'))) {
                $result = $this->callJsqlApi($request->input('token'), 'POST', 'queries/grouped');
            } else {
                $result = $this->callJsqlApi([$request->input('token')], 'POST', 'queries');
            }
        }catch(BadResponseException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json([
                'message' => $response['message'],
            ], 400);

        }
        if(!starts_with(strtoupper(ltrim($result[0]['query'])), 'SELECT')) {
            return response()->json([
                'message' => 'Only SELECT queries allowed.',
            ], 400);
        }
        try {
            $dbResult = DB::select($result[0]['query'], $params);
        }catch(\PDOException $e) {
            if(in_array($e->getCode(), ['HY000', '08P01'])) {
                preg_match_all('/(:[a-zA-Z0-9\-\_]*)+/', $result[0]['query'], $matches);
                return response()->json([
                    'message' => sprintf(' You have to include these params in the request: %s', implode(', ', $matches[0])),
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
            return response()->json([
                'code' => 400,
                'message' => (isset($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage(),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json($dbResult, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function insert(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'params' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => $validator->getMessageBag()->first(),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }
        $params = null !== $request->input('params') ?  $request->input('params') : [];
        try {
            if(is_array($request->input('token'))) {
                $result = $this->callJsqlApi($request->input('token'), 'POST', 'queries/grouped');
            } else {
                $result = $this->callJsqlApi([$request->input('token')], 'POST', 'queries');
            }
        }catch(BadResponseException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json([
                'message' => $response['message'],
            ], 400, [], JSON_UNESCAPED_UNICODE);

        }
        if(!starts_with(strtoupper(ltrim($result[0]['query'])), 'INSERT')) {
            return response()->json([
                'message' => 'Only INSERT queries allowed.',
            ], 400);
        }
        try {
            $dbResult = DB::insert($result[0]['query'], $params);
            if($dbResult) {
                $dbResult = DB::getPdo()->lastInsertId();
            }
        }catch(\PDOException $e) {
            if($e->getCode() === 'HY000') {
                preg_match_all('/(:[a-zA-Z0-9\-\_]*)+/', $result[0]['query'], $matches);
                return response()->json([
                    'message' => sprintf(' You have to include these params in the request: %s', implode(', ', $matches[0])),
                ], 400);
            }
            return response()->json([
                'code' => 400,
                'message' => (isset($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage(),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json($dbResult, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'params' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => $validator->getMessageBag()->first(),
            ], 400);
        }
        $params = null !== $request->input('params') ?  $request->input('params') : [];
        try {
            if(is_array($request->input('token'))) {
                $result = $this->callJsqlApi($request->input('token'), 'POST', 'queries/grouped');
            } else {
                $result = $this->callJsqlApi([$request->input('token')], 'POST', 'queries');
            }
        }catch(BadResponseException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json([
                'message' => $response['message'],
            ], 400);

        }
        if(!starts_with(strtoupper(ltrim($result[0]['query'])), 'UPDATE')) {
            return response()->json([
                'message' => 'Only UPDATE queries allowed.',
            ], 400);
        }
        try {
            $dbResult = DB::update($result[0]['query'], $params);
            $result = [
                'status' => ($dbResult > 0) ? 'OK' : 'NO CHANGES',
            ];
        }catch(\PDOException $e) {
            if($e->getCode() === 'HY000') {
                preg_match_all('/(:[a-zA-Z0-9\-\_]*)+/', $result[0]['query'], $matches);
                return response()->json([
                    'message' => sprintf(' You have to include these params in the request: %s', implode(', ', $matches[0])),
                ], 400);
            }
            return response()->json([
                'code' => 400,
                'message' => (isset($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage(),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function delete(Request $request) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'params' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'code' => 400,
               'message' => $validator->getMessageBag()->first(),
            ], 400);
        }
        $params = null !== $request->input('params') ?  $request->input('params') : [];
        try {
            if(is_array($request->input('token'))) {
                $result = $this->callJsqlApi($request->input('token'), 'POST', 'queries/grouped');
            } else {
                $result = $this->callJsqlApi([$request->input('token')], 'POST', 'queries');
            }
        }catch(BadResponseException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json([
                'message' => $response['message'],
            ], 400, [], JSON_UNESCAPED_UNICODE);

        }
        if(!starts_with(strtoupper(ltrim($result[0]['query'])), 'DELETE')) {
            return response()->json([
                'message' => 'Only DELETE queries allowed.',
            ], 400);
        }
        try {
            $dbResult = DB::delete($result[0]['query'], $params);
            $result = [
                'status' => ($dbResult > 0) ? 'OK' : 'NO CHANGES',
            ];
        }catch(\PDOException $e) {
            if($e->getCode() === 'HY000') {
                preg_match_all('/(:[a-zA-Z0-9\-\_]*)+/', $result[0]['query'], $matches);
                return response()->json([
                    'message' => sprintf(' You have to include these params in the request: %s', implode(', ', $matches[0])),
                ], 400);
            }
            return response()->json([
                'code' => 400,
                'message' => (isset($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage(),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
