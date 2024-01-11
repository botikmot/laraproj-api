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
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;

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
    Route::post('/remove-user-from-group-activity', [GroupController::class, 'removeUser'])->name('remove-user-group');

    Route::post('/group-message/{groupId}', [MessageController::class, 'sendMessageToGroup'])->name('group-message');
    Route::get('/group-messages/{groupId}', [MessageController::class, 'getGroupMessages'])->name('group-messages');

    Route::get('/all-users', [GroupController::class, 'getAllUsers'])->name('all-users');
    Route::post('/add-member/{groupId}', [GroupController::class, 'addMember'])->name('add-member');

    Route::post('/send-private-message/{recipientId}', [PrivateMessageController::class, 'sendPrivateMessage'])->name('send-private-message');
    Route::get('/get-private-messages', [PrivateMessageController::class, 'getPrivateMessages'])->name('get-private-messages');
    Route::get('/get-recipient-messages/{recipientId}', [PrivateMessageController::class, 'getRecipientMessages'])->name('get-recipient-messages');
    Route::get('/get-recipients', [PrivateMessageController::class, 'getRecipients'])->name('get-recipients');


    // Create private chat
    Route::post('/chats/private/{user1}/{user2}', [ChatController::class, 'createPrivateChat']);

    // Create group chat
    Route::post('/chats/group', [ChatController::class, 'createGroupChat']);

    // get User chats
    Route::get('/user/chats', [ChatController::class, 'getUserChats']);

    // Send a message
    Route::post('/chats/{chat}/send-message', [ChatController::class, 'sendMessage']);

    // get chat messages
    Route::get('/chats/{chat}/messages', [ChatController::class, 'getChatMessages']);

    // User auto seen a message
    Route::post('/chats/{chat}/{message}/seen-message', [ChatController::class, 'seenMessage']);

    //-------------------------- PROJECT ---------------------------------------//

    Route::post('/project', [ProjectController::class, 'store']);
    Route::get('/projects', [ProjectController::class, 'fetchUserProjects']);
    Route::post('/project-status/{projectId}', [ProjectController::class, 'addStatus']);
    Route::get('/project/{id}', [ProjectController::class, 'showProject']);
    Route::post('/project/{id}/task', [ProjectController::class, 'addTask']);
    Route::post('/project/{id}/member', [ProjectMemberController::class, 'addMember']);
    Route::delete('/project/{id}/status', [ProjectStatusController::class, 'removeStatus']);
    Route::put('/project/{id}/task', [TaskController::class, 'updateTask']);
    Route::delete('/project/task/{id}', [TaskController::class, 'removeTask']);
    Route::post('/task/{taskId}/comment', [TaskController::class, 'saveComment']);
    Route::post('/task/{taskId}/assigned', [TaskController::class, 'assignedUsers']);

});
