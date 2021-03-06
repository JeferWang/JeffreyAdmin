<?php

namespace App\Http\Controllers\Admin;

use App\Permission;
use App\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
	/**
	 * Method GET : 显示角色列表和添加角色表单
	 * Method POST : 执行角色添加并返回json数据
	 * @param Request $request 请求对象
	 * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function roleList(Request $request)
	{
		if ($request->isMethod('get')) {
			$roles = Role::all();
			return view('admin.role.role-list', ['roles' => $roles]);
		} else {
			$validator = validator($request->all(), [
				'name'         => ['required', Rule::unique('roles', 'name')],
				'display_name' => ['required'],
				'description'  => ['required'],
			]);
			if ($validator->fails()) {
				return ['status' => 'error', 'msg' => $validator->errors()->first()];
			} else {
				$createRole = Role::create($request->all());
				return $createRole ? ['status' => 'success', 'msg' => '添加角色成功 !'] : ['status' => 'error', 'msg' => '添加角色失败 !'];
			}
		}
	}
	
	/**
	 * Method POST : 传入参数rid,解除和用户/权限的关联,并删除角色
	 * @param Request $request 请求对象
	 * @return array Ajax接收的状态数据
	 */
	public function delRole(Request $request)
	{
		$role = Role::find($request->input('rid'));
		if (!$role) {
			return ['status' => 'error', 'msg' => '要删除的角色不存在 !'];
		} else {
			return $role->delRole() ? ['status' => 'success', 'msg' => '删除角色成功 , 并已经解除用户和权限与之的关联 !'] : ['status' => 'error', 'msg' => '删除角色失败 , 请开发者排查问题 !'];
		}
	}
	
	/**
	 * 编辑角色信息
	 * 1,修改三个基本信息
	 * 2,更改关联的权限
	 * @param Request $request
	 * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function editRole(Request $request)
	{
		$role = Role::find($request->route('rid'));
		if ($request->isMethod('get')) { // GET请求返回编辑的视图
			$permissions = Permission::all();
			return view('admin.role.edit-role', ['role' => $role, 'permissions' => $permissions]);
		} else {  // POST请求处理角色信息编辑
			$validator = validator($request->all(), [
				'name'         => ['required', Rule::unique('roles', 'name')->ignore($role->id)],
				'display_name' => ['required'],
				'description'  => ['required'],
			]);
			if ($validator->fails()) {
				return ['status' => 'error', 'msg' => $validator->errors()->first()];
			} else {
				// 更新角色的三个基本信息
				$role->fill($request->all());
				// 更新权限信息
				$newPermissionsIdArray = $request->input('permissionsId',[]);
				if ($role->save() && $role->updatePermissions($newPermissionsIdArray)) {
					return ['status' => 'success', 'msg' => '更新角色信息成功 !'];
				} else {
					return ['status' => 'success', 'msg' => '更新角色信息失败 , 请查看Log文件 !'];
				}
			}
		}
	}
}
