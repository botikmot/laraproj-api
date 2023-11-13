<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;

class PostCommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);
        $comments = $post->comments()
            ->with('user.profile')
            ->orderBy('created_at', 'desc')
            ->paginate(5);
        return response()->json(['success' => true, 'comments' =>$comments], 201);
    }

    public function store(Request $request, $postId)
    {
        $request->validate([
            'body' => 'required',
            'attachment' => 'nullable|file|max:1024',
        ]);

        // find the post
        $post = Post::findOrFail($postId);

        //get the authenticated user
        $user = auth()->user();

        // create a new comment
        $comment = new PostComment;
        $comment->body = $request->body;
        $comment->user()->associate($user);
        $comment->post()->associate($post);
        
        if ($request->hasFile('attachment')) {
            $attachments = $request->file('attachment');
            foreach ($attachments as $attachment) {
                $path = $attachment->store('comment_attachments', 'public');
                $comment->attachment = $path;
            }
        }
        
        $comment->save();

        $user->load('profile');
        $comment->user = $user;

        return response()->json(['success' => true, 'message' => 'comment successfully saved.', 'comment' => $comment], 201);
    }


}
