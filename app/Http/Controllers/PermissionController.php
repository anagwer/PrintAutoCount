<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Traits\SetReponses;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    use SetReponses;

    public function get(Request $request){
        if($request->has('page')){
          $permissions = Permission::search($request)
            ->paginate($request->perPage ?? 10);
          $permission = [];
          foreach ($permissions as $data) {
            $text = $data->name;
            $desc = $data->description;
            $pieces = explode(' - ', $text);

            $feature = $pieces[0];
            $access = $pieces[1];

            $permission[] = [
              'id' => $data->id,
              'name' => $text,
              'feature' => $feature,
              'access' => $access,
              'description' => $desc
            ];
          }
          $meta = array(
            'per_page' => $permissions->perPage(),
            'current_page' => $permissions->currentPage(),
            'prev' => $permissions->previousPageUrl() == null ? false : true,
            'next' => $permissions->hasMorePages(),
            'last' => $permissions->lastPage(),
            'total' => $permissions->total(),
            'from' => $permissions->firstItem(),
            'to' => $permissions->lastItem(),
            'timestamp' =>  date("Y-m-d H:i:s", time()),
          );
          $ret = collect($permission);

          return response()->json([
            'status' => true,
            'message' => 'Data retrieved successfully',
            'data' => $ret,
            'meta' => $meta
          ], 200);
        } else {
          $permissions = Permission::search($request)
            ->orderBy('name')->get();
          $permission = [];
          foreach ($permissions as $data) {
            $text = $data->name;
            $desc = $data->description;
            $pieces = explode(' - ', $text);

            $feature = $pieces[0];
            $access = $pieces[1];

            $permission[] = [
              'id' => $data->id,
              'name' => $text,
              'feature' => $feature,
              'access' => $access,
              'description' => $desc
            ];
          }
          $ret = collect($permission);
          return self::success($ret, false);

        }
    }

    public function store(Request $request){
        $input = $request->only(['name', 'description']);
        try {
          $validator = Validator::make($input,[
            'name' => 'required|max:190|unique:permissions,name'
          ] );

          if($validator->fails()){
            return self::error($validator->errors()->getMessages(), 500);
          }
          $permission = Permission::create([
            'name' => $request->name,
            'description' => $request->description,
            'guard_name' => 'web',
            'base_permission' => false
          ]);
          return self::crudSuccess($permission, 'inserted');
        } catch (Exception $e) {
          return self::error($e->getMessage(), 500);
        }
    }

    public function id ($id)
    {
        $permissions = Permission::where('id', $id)->get();
        return self::success($permissions, false);
    }

    public function update(Request $request, $id)
    {
        try {
            $permission = Permission::findOrFail($id);

            $validator = Validator::make($request->only(['name', 'description']), [
                'name' => 'required|max:60|unique:permissions,name',
            ]);

            if ($validator->fails()) {
                return self::error($validator->errors()->getMessages(), 500);
            }

            $permission->name = $request->name;
            $permission->description = $request->description;
            $permission->save();

            return self::crudSuccess($permission, 'updated');
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found', 404);
        } catch (Exception $e) {
            return self::error($e->getMessage(), 500);
        }
    }

    public function destroy($id){
      try {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return self::crudSuccess([], 'deleted');
      } catch (ModelNotFoundException $e) {
        return self::error($e->getMessage(), 500);
      } catch (Exception $e){
        return self::error($e->getMessage(), 500);
      }
    }

}
