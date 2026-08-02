<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     return response()->json([
    //         'posts'         => [
    //                             'total'  => Post::count(),
    //                             'views'  => Post::sum('views'),
    //                         ],
    //         'categories'    => Category::count(),
    //         'tags'          => Tag::count(),
    //         'users'         => User::count(),
    //         'comments'      => [
    //                             'total'   => Comment::count(),
    //                             'pending' => Comment::where('status','Pending')->count(),
    //                         ],
    //         'contacts'      => [
    //                             'total' => ContactMessage::count(),
    //                         ],
    //     ]);
    // }
    public function index()
    {
        return response()->json([
            "stats"             => [
                                    "posts"=>[
                                        "total"=>Post::count(),
                                        "views"=>Post::sum('views')
                                ],
                "categories"    => Category::count(),
                "tags"          => Tag::count(),
                "users"         => User::count(),
                "comments"      => [
                                    "total"=>Comment::count(),
                                    "pending"=>Comment::where('status','Pending')->count(),
                                ],
                "contacts"      => [
                                    "total"=>ContactMessage::count(),
                                ]
            ],
            "recent_posts"      => Post::latest()
                                    ->select('id','title','status','created_at')
                                    ->take(5)
                                    ->get(),
            "recent_comments"   => Comment::with('user')
                                    ->latest()
                                    ->take(5)
                                    ->get(),
            "recent_contacts"   => ContactMessage::latest()
                                    ->take(5)
                                    ->get(),
        ]);
    }
}
