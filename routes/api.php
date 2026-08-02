<?php

use App\Enums\ActiveInactiveEnum;
use App\Enums\CommentStatusEnum;
use App\Enums\PostStatusEnum;
use App\Http\Controllers\Activity\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Comment\CommentController;
use App\Http\Controllers\Comment\CommentNotificationController;
use App\Http\Controllers\Comment\CommentReportController;
use App\Http\Controllers\Contact\ContactMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Page\PageController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Setting\SeoSettingController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Slider\SliderController;
use App\Http\Controllers\Subscribe\NewsLetterSubscriberController;
use App\Http\Controllers\Tag\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard-list', [DashboardController::class, 'index']);
});

// ============================Start Public Route =========================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/page-list', [PageController::class, 'index']);
Route::get('/post-list', [PostController::class, 'index']);
Route::get('/contact-list', [ContactMessageController::class, 'index']);
Route::get('/menu-list', [MenuController::class, 'index']);
Route::get('/notification-list', [NotificationController::class, 'index']);
Route::get('/comment-list', [CommentController::class, 'index']);
Route::get('/seo-setting-list', [SeoSettingController::class, 'index']);
Route::get('/newsletter-list', [NewsLetterSubscriberController::class, 'index']);
Route::get('/slider-list', [SliderController::class, 'index']);
Route::get('/category-list', [CategoryController::class, 'index']);
Route::get('/tag-list', [TagController::class, 'index']);
Route::get('/setting-list', [SettingController::class, 'index']);

    Route::post('/add-contact-message', [ContactMessageController::class, 'store'])->name('AddContactMessage');
    Route::post('/add-newsletter', [NewsLetterSubscriberController::class, 'store'])->name('AddNewsletter');
    Route::post('/add-comment',[CommentController::class, 'store'])->name('AddComment');
    
    // Dynamic Comment System Public Endpoints
    Route::get('/comments/tree', [CommentController::class, 'tree']);
    Route::post('/comments', [CommentController::class, 'store']);
    Route::post('/comments/reply', [CommentController::class, 'reply']);
    Route::post('/comments/{id}/reaction', [CommentController::class, 'react']);
    Route::post('/comments/{id}/report', [CommentController::class, 'report']);
    
    Route::get('/blog/{slug}', [PostController::class, 'incrementView'])->name('IncrementView');
    Route::get('/post-navigation/{id}', [PostController::class, 'postNavigation']);
    Route::get('/related-posts/{category}/{post}', [PostController::class, 'relatedPosts']);
// ============================ End Public Route =========================
 
//  User Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user-list', [UserController::class, 'index']);
    Route::get('/users/trash', [UserController::class, 'deletedUsersList'])->name('DeletedUserList');

    Route::post('/create-user', [UserController::class, 'store'])->name('StoreUser');
    Route::get('/users/{id}', [UserController::class, 'editUser'])->name('EditUser');
    Route::post('/users/{id}/update', [UserController::class, 'update'])->name('UpdateUser');
    Route::post('/users/{id}/status', [UserController::class, 'changeStatus']);
    Route::delete('/users/{id}/trash', [UserController::class, 'destroy'])->name('DestroyUser');
    Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('RestoreUser');
    Route::delete('/users/{id}/force', [UserController::class, 'forceDelete'])->name('ForceDeleteUser');

    Route::get('/user-statuses', function () {
        return array_column(ActiveInactiveEnum::cases(),'value');
    });
    Route::get('/roles', function () {
        return Role::select('id','name')->get();
    });
});

// Category Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/categories/trash', [CategoryController::class, 'deletedCategoryList'])->name('DeletedCategoryList');
    Route::get('/category/{id}', [CategoryController::class, 'edit'])->name('EditCategory');
    Route::post('/store-category', [CategoryController::class, 'store'])->name('StoreCategory');
    Route::post('/category/{id}', [CategoryController::class, 'update'])->name('UpdateCategory');
    Route::post('/category/{id}/status', [CategoryController::class, 'changeStatus']);
    Route::delete('/category/{id}/trash', [CategoryController::class, 'destroy'])->name('DeleteCategory');
    Route::post('/category/{id}/restore', [CategoryController::class, 'restore'])->name('RestoreCategory');
    Route::delete('/delete/{id}/force', [CategoryController::class, 'forceDelete'])->name('ForceDeleteCategory');

    Route::get('/category-statuses', function () {
        return array_column(ActiveInactiveEnum::cases(),'value');
    });
});

// Post Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/posts/trash', [PostController::class, 'deletedPostList'])->name('DeletedPostList');
    Route::post('/add-post', [PostController::class, 'store'])->name('AddPost');
    Route::get('/posts/{id}', [PostController::class, 'edit'])->name('EditPost');
    Route::post('/posts/{id}', [PostController::class, 'update'])->name('UpdatePost');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('SoftDeletePost');
    Route::post('/posts/{id}/status', [PostController::class, 'changeStatus'])->name('ChangeStatusPost');
    Route::post('/posts/{id}/restore', [PostController::class, 'restore'])->name('RestorePost');
    Route::delete('/posts/{id}/force', [PostController::class, 'forceDelete'])->name('ForceDeletePost');

    Route::get('/post-statuses', function () { 
        return array_column(PostStatusEnum::cases(),'value');
    });
});

// Comment Routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/comments/trash',[CommentController::class,'deletedCommentList'])->name('DeletedCommentList');

    Route::get('/comments/{id}',[CommentController::class, 'edit'])->name('EditComment');
    Route::put('/comments/{id}',[CommentController::class, 'update'])->name('UpdateComment');
    Route::delete('/comments/{id}/trash',[CommentController::class, 'destroy'])->name('SoftDeleteComment');
    Route::patch('/comments/{id}/status',[CommentController::class,'changeStatus'])->name('ChangeStatusComment');
    Route::patch('/comments/{id}/restore',[CommentController::class,'restore']);
    Route::delete('/comments/{id}/force',[CommentController::class,'forceDelete']);

    Route::get('/comments/{id}/history', [CommentController::class,'history']);
    Route::get('/comment-statuses', function () {
        return array_column(CommentStatusEnum::cases(),'value');
    });

            //  Comment Report Route
    Route::get('/comment-reports', [CommentReportController::class,'index']);
    Route::get('/comment-reports/{id}', [CommentReportController::class,'show']);
    Route::patch('/comment-reports/{id}/status',[CommentReportController::class,'updateStatus']);
    Route::delete('/comment-reports/{id}',[CommentReportController::class,'destroy']);

            //  Comment notification Route
    Route::get('/notifications', [CommentNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [CommentNotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [CommentNotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all', [CommentNotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [CommentNotificationController::class, 'destroy']);

});

// Slider Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/add-slider', [SliderController::class, 'store'])->name('AddSlider');
    Route::get('/edit-slider/{id}', [SliderController::class, 'edit'])->name('EditSlider');
    Route::post('/update-slider/{id}', [SliderController::class, 'update'])->name('UpdateSlider');
    Route::delete('/delete-slider/{id}', [SliderController::class, 'destroy'])->name('DeleteSlider');
    Route::post('/status-slider/{id}', [SliderController::class, 'changeStatus']);
    Route::get('/delete-slider-list', [SliderController::class, 'deletedSliderList'])->name('DeletedSliderList');
    Route::post('/restore-slider/{id}', [SliderController::class, 'restore'])->name('RestoreSlider');
    Route::delete('/force-delete-slider/{id}', [SliderController::class, 'forceDelete'])->name('ForceDeleteSlider');

    Route::get('/slider-statuses', function () {
        return array_column(ActiveInactiveEnum::cases(),'value');
    });
});

// Tag Routess
Route::middleware(['auth:sanctum'])->group(function () {
   Route::get('/tags/trash', [TagController::class, 'deletedTagList'])->name('SoftDeleteList');

   Route::post('/add-tag', [TagController::class, 'store'])->name('StoreTag');
   Route::get('/tags/{id}', [TagController::class, 'edit'])->name('EditTag');
   Route::post('/tags/{id}', [TagController::class, 'update'])->name('UpdateTag');
   Route::post('/tags/{id}/status', [TagController::class, 'changeStatus'])->name('ChangeStatusTag');
   Route::delete('/tags/{id}/trash', [TagController::class, 'destroy'])->name('SoftDeleteTag');
   Route::post('/tags/{id}/restore', [TagController::class, 'restore'])->name('RestoreTag');
   Route::delete('/tags/{id}/force', [TagController::class, 'forceDelete'])->name('ForceDeleteTag');

   Route::get('/tag-statuses', function () {
        return array_column(ActiveInactiveEnum::cases(),'value');
    });
});

// Activity Log Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/activity-list', [ActivityLogController::class, 'index']);
    Route::get('/edit-activity/{id}', [ActivityLogController::class, 'edit'])->name('EditActivity');
    Route::delete('/delete-activity/{id}', [ActivityLogController::class, 'destroy'])->name('DeleteActivity');
});

// Setting Routes
Route::middleware(['auth:sanctum'])->group(function () {
   Route::post('/settings', [SettingController::class, 'store'])->name('AddSetting');
   Route::get('/settings/{id}', [SettingController::class, 'edit'])->name('EditSetting');
   Route::post('/settings/{id}/update', [SettingController::class, 'update'])->name('UpdateSetting');

   Route::get('/setting-statuses', function () {
        return array_column(PostStatusEnum::cases(),'value');
    });
});

//  Pages Routes
Route::middleware(['auth:sanctum'])->group(function () {
   Route::post('/add-page', [PageController::class, 'Store'])->name('AddPage');
   Route::get('/pages/{id}', [PageController::class, 'edit'])->name('EditPage');
   Route::post('/pages/{id}/update', [PageController::class, 'update'])->name('UpdatePage');
   Route::patch('/pages/{id}/status', [PageController::class, 'changeStatus'])->name('ChangeStatusPage');
   Route::delete('/pages/{id}/trash', [PageController::class, 'destroy'])->name('SoftDeletePage');
   Route::get('/pages/trash', [PageController::class, 'deletedPageList'])->name('SoftDeletePageList');
   Route::patch('/pages/{id}/restore', [PageController::class, 'restore'])->name('RestorePage');
   Route::delete('/pages/{id}/force', [PageController::class, 'forceDelete'])->name('ForceDeletePage');

    Route::get('/page-statuses', function () {
        return array_column(PostStatusEnum::cases(),'value');
    });
});

// Contact Messages Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/contact-message/{id}', [ContactMessageController::class, 'edit'])->name('EditContactMessage');
    Route::patch('/contact-message/{id}/reply', [ContactMessageController::class, 'reply'])->name('ReplyContactMessage');
    Route::patch('/contact-message/{id}/read', [ContactMessageController::class, 'markAsRead'])->name('MarkAsReadContactMessage');
    Route::patch('/contact-message/{id}/unread', [ContactMessageController::class, 'markAsUnread'])->name('MarkAsUnreadContactMessage');
    Route::delete('/contact-message/{id}/delete', [ContactMessageController::class, 'destroy'])->name('DeleteContactMessage');
});

// Menu Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/menus/trash', [MenuController::class, 'deletedMenuList'])->name('SoftDeleteMenuList');

    Route::post('/add-menu', [MenuController::class, 'store'])->name('StoreMenu');
    Route::get('/menus/{id}', [MenuController::class, 'edit'])->name('EditMenu');
    Route::put('/menus/{id}/item/{itemId}', [MenuController::class, 'update'])->name('UpdateMenu');
    Route::patch('/menus/{id}/status', [MenuController::class, 'changeStatus'])->name('ChangeStatusMenu');
    Route::delete('/menus/{id}/trash', [MenuController::class, 'destroy'])->name('SoftDeleteMenu');
    Route::patch('/menus/{id}/restore', [MenuController::class, 'restore'])->name('RestoreMenu');
    Route::delete('/menus/{id}/force', [MenuController::class, 'forceDelete'])->name('ForceDeleteMenu');
});


//========================= pending =============================

// Notification Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/add-notification', [NotificationController::class, 'store'])->name('AddNotification');
    Route::get('/notifications/{id}', [NotificationController::class, 'edit'])->name('EditNotification');
    Route::put('/notifications/{id}/update', [NotificationController::class, 'update'])->name('UpdateNotification');
    Route::delete('/notifications/{id}/delete', [NotificationController::class, 'destroy'])->name('DeleteNotification');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('MarkAsReadNotification');
    Route::get('/notifications/allRead', [NotificationController::class, 'markAllAsRead'])->name('MarkAllAsReadNotification');
    Route::get('/notifications/unreadCounter', [NotificationController::class, 'unreadCount'])->name('UnreadCountNotification');
    Route::delete('/notifications/clear', [NotificationController::class,'clearAll']);
    Route::patch('/notifications/unread', [NotificationController::class,'markAsUnread']);

});

// SEO Setting Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/seo-settings', [SeoSettingController::class, 'store'])->name('StoreSeoSetting');
    Route::get('/seo-settings/{id}', [SeoSettingController::class, 'edit'])->name('EditSeoSetting');
    Route::post('/seo-settings/{id}', [SeoSettingController::class, 'update'])->name('UpdateSeoSetting');
    Route::delete('/seo-settings/{id}/delete', [SeoSettingController::class, 'destroy'])->name('DeleteSeoSetting');
});

// Newsletter Subscriber Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/newsletters/trash', [NewsLetterSubscriberController::class, 'deletedSubscriberList'])->name('DeletedNewsletterList');

    Route::get('/newsletters/{id}', [NewsLetterSubscriberController::class, 'edit'])->name('EditNewsletter');
    Route::put('/newsletters/{id}', [NewsLetterSubscriberController::class, 'update'])->name('UpdateNewsletter');
    Route::delete('/newsletters/{id}/trash', [NewsLetterSubscriberController::class, 'destroy'])->name('SoftDeleteNewsletter');
    Route::patch('/newsletterS/{id}/status', [NewsLetterSubscriberController::class, 'changeStatus'])->name('ChangeStatusNewsletter');
    Route::patch('/newsletters/{id}/restore', [NewsLetterSubscriberController::class, 'restore'])->name('RestoreNewsletter');
    Route::delete('/newsletters/{id}/force', [NewsLetterSubscriberController::class, 'forceDelete'])->name('ForceDeleteNewsletter');
});