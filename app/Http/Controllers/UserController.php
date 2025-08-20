<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Models\users;
use App\Traits\SetReponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class UserController extends BaseController
{
    protected $model = User::class;
    use SetReponses;
    public function get(Request $request)
    {
        $user = User::search($request);

        if ($request->has('page')) {
            $data = $user->paginate($request->perPage ?? 10);

            // Tambahkan roles ke setiap item
            $data->getCollection()->transform(function ($item) {
                $item->roles = $item->getRoleNames();
                return $item;
            });

            return self::success($data, true);
        }

        $data = $user->get()->map(function ($item) {
            $item->roles = $item->getRoleNames();
            return $item;
        });

        return self::success($data, false);
    }

    public function store(Request $request)
    {
        $request->merge([
            'password' => Hash::make($request->password),
            'remember_token' => bin2hex(random_bytes(16)),
        ]);

        return parent::store($request);
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(6)->mixedCase()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return self::error('Password lama salah', 422);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return self::error('Password baru dan password lama tidak boleh sama', 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return self::crudSuccess([], 'changed password');
    }

   public function resetPassword(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = $request->user()->load('employee');

            if (!$user || !$user->employee || !$user->employee->email) {
                return self::error('Email tidak ditemukan untuk user ini.', 404);
            }

            $defaultPassword = 'Lbg@1080*';
            $user->password = Hash::make($defaultPassword);
            $user->updated_at = now();
            $user->save();

            // Kirim email
            Mail::to($user->employee->email)->send(new ResetPasswordMail($user, $defaultPassword));

            DB::commit();
            return self::crudSuccess([], 'Password berhasil direset dan dikirim ke email.');
        } catch (\Exception $e) {
            DB::rollback();
            return self::error('Terjadi kesalahan saat mereset password.', 500);
        }
    }
    public function setRole(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);
            if (!$user) {
                return self::error('Data not found', 500);
            }
            $input = $request->only('username', 'role');
            $validator = Validator::make($input, [
                'username' => 'required',
                'role'     => 'required'
            ]);
            if($validator->fails()){
                return self::error($validator->errors()->getMessages(), 500);
            }
            $user->syncRoles([]);
            $user->assignRole($request->role);
            DB::commit();
            return self::crudSuccess($user, 'updated');
            } catch (Exception $e) {
            DB::rollBack();
            return self::error($e->getMessage(), 500);
        }
      }
}
