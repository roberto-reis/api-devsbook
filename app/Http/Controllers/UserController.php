<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function __construct() {
        $this->middleware('auth:api');        
    }

    public function update(Request $request) {
        $array = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user = User::find(Auth::user()->id);

        if($name) {
            $user->name = $name;
        }

        if($email) {
            if($email != $user->emal) {
                $emailExists = User::where('email', $email)->count();
                if($emailExists === 0) {
                    $user->email = $email;
                } else {
                    return $array['error'] = 'E-mail já existe!';
                }
            }
        }

        if($birthdate) {
            if(strtotime($birthdate) === false) {
                return $array['error'] = 'Data inválida';
            }
            $user->birthdate = $birthdate;
        }

        if($city) {
            $user->city = $city;
        }

        if($work) {
            $user->work = $work;
        }

        if($password && $password_confirm) {
            if($password === $password_confirm) {
                $user->password = password_hash($password, PASSWORD_DEFAULT);
            } else {
                return $array['error'] = 'As senhas não são iguais.';
            }
        }


        $user->save();

        return $array;
    }

    public function updateAvatar() {
        $array = ['error' => ''];

        return $array;
    }
    
    
}
