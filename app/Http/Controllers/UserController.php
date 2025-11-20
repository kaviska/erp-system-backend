<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('id')) {
            $user = User::with('roles')->find($request->id);
        } elseif ($request->has('email')) {
            $user = User::with('roles')->where('email', $request->email)->first();
        } else {
            $user = User::with('roles')->get();
        }

        return Response::success($user, 'User fetched successfully');
    }

    public function update(Request $request){
        try {
            //code...
            $request->validate([
                'id' => 'required|integer|exists:users,id',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
               
                'role' => 'required|integer|exists:roles,id',
            ]);
            $user = User::find($request->id);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->assignRole($request->role);
            $user->save();
            return Response::success($user, 'User updated successfully', 200);
        } catch (\Throwable $th) {
            //throw $th;
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    public function delete($id){
        $user = User::find($id);
        if (!$user) {
            return Response::error('User not found', 'User not found', 404);
        }
        $user->delete();
        return Response::success(null, 'User deleted successfully');
    }
}
