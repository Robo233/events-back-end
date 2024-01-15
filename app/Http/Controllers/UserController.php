<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;


class UserController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        Log::info('$request->validate([');
        Log::info('Request Data: ', $request->all());

        $request->validate(
            [
                'avatar' => 'nullable|image|max:2048',
                'user_id' => 'required',
            ]
        );

        if ($request->hasFile('avatar')) {
            $imageName = 'avatar' . '.' . 'jpg';
            $imagePath = 'public/users/' . $request->user_id;
            $request->avatar->storeAs($imagePath, $imageName);
        }

        return response()->json(['message' => 'Avatar updated successfully'], 200);
    }

    public function updateName(Request $request)
    {
        $request->validate(
            [
                'name' => 'required',
                'user_id' => 'required|exists:users,id',
            ]
        );

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->name = $request->name;
        $user->save();

        return response()->json(['message' => 'User name updated successfully']);
    }
}
