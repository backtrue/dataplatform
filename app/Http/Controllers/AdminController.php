<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    
    public function dashboard()
    {
        $users = User::where('role', 'user')->get();
        return view('admin.dashboard', compact('users'));
    }
    
    public function createUser()
    {
        return view('admin.users.create');
    }
    
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'google_analytics_view_id' => 'nullable|string',
            'google_ads_customer_id' => 'nullable|string',
            'google_search_console_site_url' => 'nullable|string',
        ]);
        
        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->role = 'user';
        $user->google_analytics_view_id = $validated['google_analytics_view_id'];
        $user->google_ads_customer_id = $validated['google_ads_customer_id'];
        $user->google_search_console_site_url = $validated['google_search_console_site_url'];
        $user->save();
        
        return redirect()->route('admin.users.index')->with('success', '用戶已創建');
    }
    
    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }
    
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'google_analytics_view_id' => 'nullable|string',
            'google_ads_customer_id' => 'nullable|string',
            'google_search_console_site_url' => 'nullable|string',
        ]);
        
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->google_analytics_view_id = $validated['google_analytics_view_id'];
        $user->google_ads_customer_id = $validated['google_ads_customer_id'];
        $user->google_search_console_site_url = $validated['google_search_console_site_url'];
        $user->save();
        
        return redirect()->route('admin.users.index')->with('success', '用戶已更新');
    }
    
    public function deleteUser(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', '用戶已刪除');
    }
}