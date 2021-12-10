<?php

namespace App\Http\Controllers;

use App\Http\Resources\Collection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|max:255'
        ]);
        if($validator->fails()) {
            return (new Collection([]))->response(false, $validator->errors()->all());
        }

        $user = User::query()->where('email','=', $request->email)->first();

        if($user && Hash::check($request->password, $user->password)) {
            return (new Collection([
                ['token' => auth()->tokenById($user->id)]
            ]))->response(true, ['Successfully logged in!']);
        }

        return (new Collection([]))->response(false, ['Email or password is wrong!']);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50|regex:/(?!^\d+$)^.+$/', // regex : not all are numbers
            'surname' => 'required|string|min:3|max:75|regex:/(?!^\d+$)^.+$/',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6|max:32|regex:/(?!^\d+$)^.+$/'
        ]);
        if($validator->fails()) {
            return (new Collection([]))->response(false, $validator->errors()->all());
        }

        $user = new User();
        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->user_type = User::CUSTOMER;
        $user->save();

        return (new Collection([]))->response(true, ['Successfully registered!']);
    }

    public function getUserInfo() {
        return (new Collection([
            auth()->user()
        ]))->response(true, ['Successfully registered!']);
    }

    public function logout() {
        auth()->logout();
        return (new Collection([]))->response(true, ['Successfully logout!']);
    }
}
