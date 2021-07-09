<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

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
                    $array['error'] = 'E-mail já existe!';
                    return $array;
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
                $array['error'] = 'As senhas não são iguais.';
                return $array;
            }
        }


        $user->save();

        return $array;
    }

    public function updateAvatar(Request $request) {
        $array = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if($image) {
            if( in_array($image->getClientMimeType(), $allowedTypes) ) {
                $filename = md5( time().rand(0, 9999) ).'.jpg';
                $destPath = public_path('/media/avatars');

                $img = Image::make( $image->path() )
                ->fit(200, 200)
                ->save($destPath.'/'.$filename);

                $user = User::find(Auth::user()->id);
                $user->avatar = $filename;
                $user->save();

                $array['url'] = url('/media/avatars/'.$filename);

                return $array;

            } else {
                $array['error'] = 'Arquivo não suportado.';
                return $array;
            }
        } else {
            $array['error'] = 'Arquivo não enviado.';
            return $array;
        }

        return $array;
    }

    public function updateCover(Request $request) {
        $array = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');

        if($image) {
            if( in_array($image->getClientMimeType(), $allowedTypes) ) {
                $filename = md5( time().rand(0, 9999) ).'.jpg';
                $destPath = public_path('/media/covers');

                $img = Image::make( $image->path() )
                ->fit(850, 310)
                ->save($destPath.'/'.$filename);

                $user = User::find(Auth::user()->id);
                $user->cover = $filename;
                $user->save();

                $array['url'] = url('/media/covers/'.$filename);

                return $array;

            } else {
                $array['error'] = 'Arquivo não suportado.';
                return $array;
            }
        } else {
            $array['error'] = 'Arquivo não enviado.';
            return $array;
        }

        return $array;
    }

    public function read($id = false) {
        $array = ['error' => ''];

        if($id) {
            $info = User::find($id);
            if(!$info) {
                $array['error'] = 'Usuário inexistente!';
                return $array;
            }
        } else {
            $info = Auth::user();
        }

        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $info['cover'] = url('media/covers/'.$info['cover']);

        $info['me'] = ($info['id'] == Auth::user()->id) ? true : false;

        $dateFrom = new \DateTime($info['birthdate']);
        $dateTo = new \DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y;

        $info['followers'] = UserRelation::where('user_to', $info['id'])->count();

        $info['following'] = UserRelation::where('user_from', $info['id'])->count();

        $info['photoCount'] = Post::where('id_user', $info['id'])
        ->where('type', 'photo')
        ->count();

        $hasRelation = UserRelation::where('user_from', Auth::user()->id)
        ->where('user_to', $info['id'])
        ->count();

        $info['isFollowing'] = ($hasRelation > 0) ? true: false;

        $array['data'] = $info;

        return $array;
    }

    public function follow($id) {
        $array = ['error' => ''];

        if($id == Auth::user()->id) {
            $array['error'] = 'Você não pode seguir a se mesmo.';
            return $array;
        }

        $userExists = User::find($id);
        if($userExists) {

            $relation = UserRelation::where('user_from', Auth::user()->id)
            ->where('user_to', $id)
            ->first();

            if($relation) {
                $relation->delete();
            } else {
                $newRelation = new UserRelation();
                $newRelation->user_from = Auth::user()->id;
                $newRelation->user_to = $id;
                $newRelation->save();
            }

        } else {
            $array['error'] = 'Usuário inexistente!';
            return $array;
        }

        return $array;
    }

    public function followers($id) {
        $array = ['error' => ''];

        $userExists = User::find($id);
        if($userExists) {
            $followers = UserRelation::where('user_to', $id)->get();
            $following = UserRelation::where('user_from', $id)->get();
            
            $array['followers'] = [];
            $array['following'] = [];

            foreach($followers as $item) {
                $user = User::find($item['user_from']);
                $array['followers'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => url('media/avatars/'.$user['avatar'])
                ];
            }

            foreach($following as $item) {
                $user = User::find($item['user_from']);
                $array['following'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => url('media/avatars/'.$user['avatar'])
                ];
            }

        } else {
            $array['error'] = 'Usuário inexistente!';
            return $array;
        }


        return $array;
    }
    
    
}
