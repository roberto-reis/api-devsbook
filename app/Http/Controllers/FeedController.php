<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\UserRelation;
use App\Models\User;

class FeedController extends Controller
{

    public function __construct() {
        $this->middleware('auth:api');
    }
    
    public function create(Request $request) {
        $array = ['error'=>''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');

        if($type) {

            switch ($type) {
                case 'text':
                    if(!$body) {
                        $array['error'] = 'Texto não enviado!';
                        return $array;
                    }
                break;
                case 'photo':
                    if($photo) {
                        if(in_array( $photo->getClientMimeType(), $allowedTypes) ) {
                            $filename = md5( time().rand(0, 9999) ).'.jpg';
                            $destPath = public_path('/media/uploads');
                            $img = Image::make( $photo->path() )
                            ->resize(800, null, function($constraint) {
                                $constraint->aspectRatio();
                            })
                            ->save($destPath.'/'.$filename);

                            $body = $filename;

                        } else {
                            $array['error'] = 'Arquivo não suportado.';
                            return $array; 
                        }
                        
                    } else {
                        $array['error'] = 'Arquivo não enviado.';
                        return $array;
                    }
                    break;
                default:
                    $array['error'] = 'Tipo de Postagem inexistente.';
                    return $array;
                break;
            }
            if($body) {
                $newPost = new Post();
                $newPost->id_user = Auth::user()->id;
                $newPost->type = $type;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->body = $body;
                $newPost->save();
            }

        } else {
            $array['error'] = 'Dados não inviados.';
            return $array;
        }

        return $array;
    }

    public function read(Request $request) {
        $array = ['error'=>''];
        $page = intval( $request->input('page') );
        $perPage = 2;

        // Pegar a lista de usuários que eu sigo (incluindo eu mesmo)
        $users = [];
        $userList = UserRelation::where('user_from', Auth::user()->id)->get();
        foreach($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }
        $users[] = Auth::user()->id;

        // Pegar os post ORDENADO PELA DATA
        $postList = Post::whereIn('id_user', $users)
        ->orderBy('created_at', 'desc')
        ->offset($page * $perPage)
        ->limit($perPage)
        ->get();

        $total = Post::whereIn('id_user', $users)->count();
        $pageCount = ceil($total / $perPage);

        // Preencher as informações adicionais
        $posts = $this->_postListToObject($postList, Auth::user()->id);
        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;
        

        return $array;
    }

    public function userFeed(Request $request, $id = false) {
        $array = ['error'=>''];

        if($id == false) {
            $id = Auth::user()->id;
        }

        $page = intval( $request->input('page') );
        $perPage = 2;

        // Pegar os posts do usuário ordenado pela dta
        $postList = Post::where('id_user', $id)
        ->orderBy('created_at', 'desc')
        ->offset($page * $perPage)
        ->limit($perPage)
        ->get();

        $total = Post::where('id_user', $id)->count();
        $pageCount = ceil($total / $perPage);

        // Preencher as informações adicionais
        $posts = $this->_postListToObject($postList, Auth::user()->id);
        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    private function _postListToObject($postList, $loggedId) {
        foreach($postList as $postkey => $postItem) {

            if($postItem['id_user'] == $loggedId) {
                $postList[$postkey]['mine'] = true;
            } else {
                $postList[$postkey]['mine'] = false;
            }

            // Preencher informações de usuário
            $userInfo = User::find( $postItem['id_user'] );
            $userInfo['avatar'] = url('media/avatars/'.$userInfo['avatar']);
            $userInfo['cover'] = url('media/covers/'.$userInfo['cover']);
            $postList[$postkey]['user'] = $userInfo;

            // Preencher informações de Like
            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postkey]['likeCount'] = $likes;

            $isLiked = PostLike::where('id_post', $postItem['id'])
            ->where('id_user', $loggedId)
            ->count();

            $postList[$postkey]['liked'] = ($isLiked > 0) ? true: false;

            // Preencher Informarções de COMMENTS
            $comments = PostComment::where('id_post', $postItem['id'])->get();
            foreach($comments as $commentKey => $comment) {
                $user = User::find($comment['id_user']);
                $user['avatar'] = url('media/avatars/'.$user['avatar']);
                $user['cover'] = url('media/covers/'.$user['cover']);
                $comments[$commentKey]['user'] = $user;
            }
            $postList[$postkey]['comments'] = $comments;

        }

        return $postList;
    }

}
