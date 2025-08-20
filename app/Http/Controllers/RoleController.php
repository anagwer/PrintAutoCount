<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Traits\SetReponses;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    use SetReponses;

    public function get(Request $request){
        $role = Role::with('permissions')
          ->search($request);

        if($request->has('page')){
          return self::success(
            $role->paginate($request->perPage ?? 10), true
          );
        }
        return self::success($role->get(), false);
      }

      public function store(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
          'name' => 'required|max:60|unique:roles,name,'
        ]);
        if($validator->fails()){
          return self::error($validator->errors(), 500);
        }
        try {
          $role = Role::create($input);
          return self::crudSuccess($role, 'inserted');
        } catch (Exception $e) {
          return self::error($e->getMessage(), 500);
        }
    }

    public function assignPermissionToRole(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'role' => 'required',
            'permissions' => 'required'
        ]);
        if ($validator->fails()) {
            return self::error($validator->errors()->messages(), 500);
        }

        $role = Role::find($request->role);
        if ($role->name == 'administrator') {
            $role->syncPermissions(Permission::all());
            return self::crudSuccess($role, 'updated');
        } else {
            try {
                $role->syncPermissions($request->permissions);
                return $role;
            } catch (\Throwable $th) {
                return self::error($th->getMessage(), 500);
            }
        }
    }

    public function assignRoleToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required'
        ]);

        if ($validator->fails()) {
            return self::error($validator->errors()->messages(), 422);
        }

        try {
            $user = User::findOrFail($request->user_id);

            // Ketika tambah role baru, role lama tidak terhapus
            // $user->assignRole($request->role);

            // Ketika tambah role baru, role lama terhapus
            $user->syncRoles([$request->role]);

            return self::crudSuccess([], true);
        } catch (\Throwable $th) {
            return self::error($th->getMessage(), 500);
        }
    }

    public function id($id)
    {
        $role = Role::with('permissions')->where('id', '=', $id)->get();

        return self::success($role, false);
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);
            if (!$role) {
                return self::error('Data not found', 404);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:60|unique:roles,name,' . $id,
            ]);

            if ($validator->fails()) {
                return self::error($validator->errors(), 500);
            }

            if ($role->name == 'administrator') {
                return self::error('You cannot edit this role', 500);
            } else {
                $role->name = $request->name;
                $role->save();
            }

            return self::crudSuccess($role, 'updated');
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found', 404);
        } catch (Exception $e) {
            return self::error($e->getMessage(), 500);
        }
    }

    public function destroy($id){
        try {
          $role = Role::find($id);
          if(!$role){
            return self::error('Data not found!', 500);
          }
          if($role->name == 'administrator'){
            return self::error('Administrator can not be deleted!', 500);
          } else {
            $permissions = $role->getAllPermissions();
            foreach ($permissions as $value) {
              $role->revokePermissionTo($value);
            }
            $role->delete();
            return self::crudSuccess([], 'deleted');
          }
        } catch (ModelNotFoundException $e) {
          return self::error($e->getMessage(), 500);
        } catch (Exception $e){
          return self::error($e->getMessage(), 500);
        }
      }

}
