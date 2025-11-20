<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManger extends Controller
{
    //
    public function createRole(Request $request)
    {
        try {
            //validate request
            $request->validate([
                'name' => 'required|string|unique:roles,name',
                 'permissions' => 'required|array',
                'permissions.*' => 'string',

            ]);

            //create role
            $role = Role::create(['name' => $request->name]);
             foreach ($request->permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            //assign permissions to role
            $role->givePermissionTo($request->permissions);
            return Response::success($role, 'Role created successfully', 201);

        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    public function assignPermissionToRole(Request $request)
    {
        try {
            //validate request
            $request->validate([
                'role' => 'required|integer|exists:roles,id',
                'permissions' => 'required|array',
                'permissions.*' => 'string',
            ]);

            //find role
            $role = Role::find($request->role);

            //create permissions if not exists
            foreach ($request->permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            //assign permissions to role
            $role->givePermissionTo($request->permissions);

            return Response::success($role, 'Permissions assigned to role successfully', 200);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    public function getRoleWithPermissions(Request $request)
    {
        try {
            if ($request->has('id')) {
                $request->validate([
                    'id' => 'required|integer|exists:roles,id'
                ]);
                $role = Role::with('permissions')->find($request->id);
                return Response::success($role, 'Role with permissions fetched successfully', 200);
            }
            $roles = Role::with('permissions')->get();
            return Response::success($roles, 'Roles with permissions fetched successfully', 200);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    public function update(Request $request){
        try {
            //code...
            $request->validate([
                'id' => 'required|integer|exists:roles,id',
                'name' => 'required|string|unique:roles,name,'.$request->id,
                'permissions' => 'required|array',
                'permissions.*' => 'string',
            ]);
            $role = Role::findById($request->id);
            $role->name = $request->name;
            foreach ($request->permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            $role->syncPermissions($request->permissions);
            $role->save();
            return Response::success($role, 'Role updated successfully', 200);
        } catch (\Throwable $th) {
            //throw $th;
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    public function delete($id){
        try {
            $role = Role::findById($id);
            if(!$role){
                return Response::error('', 'Role not found', 404);
            }
            $role->delete();
            return Response::success('', 'Role deleted successfully', 200);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
