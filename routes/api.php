<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\UserProfile\UserProfileController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PrivateMessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth'])->group(function () {

    Route::post('/user-heartbeat', [UserProfileController::class, 'userHeartbeat'])->name('user-heartbeat');
    Route::post('/update-profile', [UserProfileController::class, 'store'])->name('profile-update');
    
    Route::post('/presentation', [PresentationController::class, 'store'])->name('save-presentation');
    Route::get('/presentations', [PresentationController::class, 'index'])->name('get-presentations');
    Route::post('/save-image', [PresentationController::class, 'saveImageFromUrl'])->name('save-image');
    
    Route::post('/create-post', [PostController::class, 'store'])->name('create-post');
    Route::get('/posts', [PostController::class, 'index'])->name('posts');
    Route::post('/create-comment/{postId}', [PostCommentController::class, 'store'])->name('create-comment');
    Route::get('/comments/{postId}', [PostCommentController::class, 'index'])->name('comments');
    Route::post('/post/{id}/like', [PostController::class, 'likePost'])->name('like-post');

    Route::post('/create-group', [GroupController::class, 'createGroup'])->name('create-group');
    Route::get('/groups', [GroupController::class, 'getUserGroups'])->name('get-user-group');

    Route::post('/group-message/{groupId}', [MessageController::class, 'sendMessageToGroup'])->name('group-message');
    Route::get('/group-messages/{groupId}', [MessageController::class, 'getGroupMessages'])->name('group-messages');

    Route::get('/all-users', [GroupController::class, 'getAllUsers'])->name('all-users');
    Route::post('/add-member/{groupId}', [GroupController::class, 'addMember'])->name('add-member');

    Route::post('/send-private-message/{recipientId}', [PrivateMessageController::class, 'sendPrivateMessage'])->name('send-private-message');
    Route::get('/get-private-messages', [PrivateMessageController::class, 'getPrivateMessages'])->name('get-private-messages');

});




