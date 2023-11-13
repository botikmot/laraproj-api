<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use Embed\Embed;

class PostController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $posts = Post::orderBy('created_at', 'desc')
                ->with('attachments', 'user.profile')
                ->withCount('comments', 'likes')
                ->with(['likes' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->paginate(10);
        
                $posts->each(function ($post) {
                    $content = $post->content;
                    
                    // Use preg_match_all to find all links in the content
                    $linkMatches = [];
                    if (preg_match_all('/https?:\/\/\S+/', $content, $linkMatches)) {
                        $linkPreviews = []; // Create an array to store link previews
                        foreach ($linkMatches[0] as $link) {
                            $embed = new Embed();
                            $linkPreview = $embed->get($link);
                            
                            // Store the link preview data in an array
                            $linkData = [
                                'image' => $linkPreview->image,
                                'title' => $linkPreview->title,
                                'desc' => $linkPreview->description,
                                'url' => $linkPreview->url,
                            ];
                            
                            $linkPreviews[] = $linkData; // Add the link data to the array
                        }
                        $post->link_previews = $linkPreviews; // Assign the array of link previews to the post
                    }
                });
                
                

        return response()->json(['posts' => $posts], 201);
    }

    public function store(Request $request)
    {   
        $validator = $request->validate([
            'content' => 'required|max:255',
            //'privacy' => 'required',
            'attachments.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,mp4,mkv|max:5048'
        ]);

        $post = new Post($validator);
        $post->user_id = Auth::user()->id;
        $post->save();

        if ($request->hasFile('attachments')) {
            // store the attachments and attach them to the post
            $attachments = $request->file('attachments');
            foreach ($attachments as $attachment) {
                $path = $attachment->store('social_attachments', 'public');
                $post->attachments()->create(['path' => $path]);
            }
            $post->load('attachments');
        }
        $post->comments_count = 0;
        $post->load('user.profile');

        $content = $post->content;      
        // Use preg_match_all to find all links in the content
        $linkMatches = [];
        if (preg_match_all('/https?:\/\/\S+/', $content, $linkMatches)) {
            $linkPreviews = []; // Create an array to store link previews
            foreach ($linkMatches[0] as $link) {
                $embed = new Embed();
                $linkPreview = $embed->get($link);
                
                // Store the link preview data in an array
                $linkData = [
                    'image' => $linkPreview->image,
                    'title' => $linkPreview->title,
                    'desc' => $linkPreview->description,
                    'url' => $linkPreview->url,
                ];
                
                $linkPreviews[] = $linkData; // Add the link data to the array
            }
            $post->link_previews = $linkPreviews; // Assign the array of link previews to the post
        }



        return response()->json(['success' => true, 'post' => $post], 201);

    }

    public function likePost($id)
    {
        $post = Post::find($id);
        $like = $post->likes()->where('user_id', Auth::id())->first();
        if($like){
            $like->delete();
            return response()->json(['success' => true, 'message' => 'unliked'], 201);
        }else{
            $post->likes()->create(['user_id' => Auth::id()]);
            return response()->json(['success' => true, 'message' => 'liked'], 201);
        }
    }
}
