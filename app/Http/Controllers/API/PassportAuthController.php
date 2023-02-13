<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PassportAuthController extends Controller
{
    /**
     * Registration Req
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|min:4',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $token = $user->createToken('Laravel9PassportAuth')->accessToken;

        return response()->json(['token' => $token], 200);
    }

    /**
     * Login Req
     */
    public function login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken('Laravel9PassportAuth')->accessToken;
            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    public function userInfo(Request $request)
    {
        $cacheKey = 'user_info_'.$request->page;

        if (Cache::has($cacheKey)) {
            $user = Cache::get($cacheKey);
        } else {
            $user = User::paginate($request->per_page);
            Cache::put($cacheKey, $user, 60);
        }

     return response()->json(['user' => $user], 200);

    }

    public function createRole()
    {
        try{
            //$role = Role::create(['name' => 'admin']);
            $user = User::find(1)->roles()->pluck('name')->dd();
            // $role = Role::find(1);
            // $permission = Permission::find(1);
            // dd($role->givePermissionTo($permission));
            // $user->assignRole('admin');
            // return response()->json(['user' => $user], 200);

        }catch(Exception $e)
        {
            \Log::error($e->getMessage());
        }


    }
}
