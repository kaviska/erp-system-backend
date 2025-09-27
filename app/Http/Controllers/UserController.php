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
        }
        if ($request->has('email')) {
            $user = User::with('roles')->where('email', $request->email)->first();
        }

        $user = User::with('roles')->get();

        return Response::success($user, 'User fetched successfully');
    }
}
