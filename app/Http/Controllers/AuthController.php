<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\SetReponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use SetReponses;
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'target_db' => 'nullable|in:mysql,sqlsrv',
        ]);

        // Login tetap pakai koneksi utama (mysql)
        if (!Auth::attempt($request->only('username', 'password'))) {
            return Self::error('Check your credential!', 401);
        }

        $user = User::where('username', $request->username)->first();

        $token = $user->createToken('API Token', ['target_db:' . ($request->target_db ?? 'mysql')])->plainTextToken;

        $meta = [
            'token' => $token,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'target_db' => $request->target_db ?? 'mysql',
        ];

        $user->roles = $user->getRoleNames();
        $user->ability = Self::ability();

        return response()->json([
            'status' => true,
            'message' => 'Logged successfully',
            'data' => $user,
            'meta' => $meta,
        ], 200);
    }

    public static function ability()
    {
        $ability = array();
        $user = auth()->user();
        $permissions = $user->getAllPermissions();
        foreach ($permissions as $key => $value) {
            $ability[] = array(
                'subject' => explode(' - ', $value->name)[0],
                'action' => explode(' - ', $value->name)[1]
            );
        }

        $meta = array(
            'count' => count($ability),
            'timestamp' => Carbon::now()
        );
        return $ability;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,username',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return self::error($validator->errors(), 422);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'employee_id' => $request->employee_id ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            'data' => $user
        ], 201);
    }
}
