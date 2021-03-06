<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class PostController extends Controller
{

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function like($id) {
        $array = ['error' => ''];

        $postExists = Post::find($id);

        if($postExists) {

            $isLiked = PostLike::where('id_post', $id)
            ->where('id_user', Auth::user()->id)
            ->count();

            if($isLiked > 0) {
                $pl = PostLike::where('id_post', $id)
                ->where('id_user', Auth::user()->id)
                ->first();
                $pl->delete();

                $array['isLiked'] = false;
            } else {

                $newPostLike = new PostLike();
                $newPostLike->id_post = $id;
                $newPostLike->id_user = Auth::user()->id;
                $newPostLike->created_at = date('Y-m-d H:i:s');
                $newPostLike->save();

                $array['isLiked'] = true;
                
            }

            $likeCount = PostLike::where('id_post', $id)->count();            
            $array['likeCount'] = $likeCount;

        } else {
            $array['error'] = ['Post não existe.'];
            return $array;
        }


        return $array;
    }

    public function comment(Request $request, $id) {
        $array = ['error' => ''];

        $txt = $request->input('txt');

        $postExists = Post::find($id);
        if($postExists) {

            if($txt) {
                $newComment = new PostComment();
                $newComment->id_post = $id;
                $newComment->id_user = Auth::user()->id;
                $newComment->created_at = date('Y-m-d H:i:s');
                $newComment->body = $txt;
                $newComment->save();

            }else {
                $array['error'] = ['Não enviou mensagem.'];
            return $array;
            }

        } else {
            $array['error'] = ['Post não existe.'];
            return $array;
        }


        return $array;
    }
    
    
}
