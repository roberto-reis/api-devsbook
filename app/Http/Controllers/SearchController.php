<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{

    public function __construct() {
        $this->middleware('auth:api');
    }


    public function search(Request $request) {
        $array = ['error' => '', 'users' => []];

        $txt = $request->input('txt');

        if($txt) {
            
            $userList = User::where('name', 'like', '%'.$txt.'%')->get();
            foreach($userList as $userItem) {
                $array['users'][] = [
                    'id' => $userItem['id'],
                    'name' => $userItem['name'],
                    'avatar' => url('media/avatars/'.$userItem['avatar'])
                ];
            }

        } else {
            $array['error'] = 'Digite alguma coisa para buscar';
            return $array;
        }


        return $array;
    }
    
}
