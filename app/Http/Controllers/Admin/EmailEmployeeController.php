<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmailEmployeeController extends Controller
{
    public function index()
    {
        $users = User::all();
        $technicians = Technician::all();

        return view('admin.email-employee', compact('users', 'technicians'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => ['required', 'string', Rule::in(User::staffRoles())],
            'password' => 'nullable|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'User updated successfully.');
    }

    public function destroyUser($id)
    {
        User::destroy($id);

        return back()->with('success', 'User deleted successfully.');
    }

    public function storeTechnician(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        Technician::create([
            'name' => $request->name,
            'position' => $request->position,
        ]);

        return back()->with('success', 'Technician created successfully.');
    }

    public function updateTechnician(Request $request, $id)
    {
        $tech = Technician::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        $tech->update([
            'name' => $request->name,
            'position' => $request->position,
        ]);

        return back()->with('success', 'Technician updated successfully.');
    }

    public function destroyTechnician($id)
    {
        Technician::destroy($id);

        return back()->with('success', 'Technician deleted successfully.');
    }
}
