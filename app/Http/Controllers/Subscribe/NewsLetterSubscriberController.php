<?php

namespace App\Http\Controllers\Subscribe;

use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class NewsLetterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $newsletters = NewsletterSubscriber::with('user')
                        ->when($request->search,function($q) use($request){
                            $q->where('email','like',"%{$request->search}%");
                        })
                        ->when($request->status,function($q) use($request){
                            $q->where('status',$request->status);
                        })
                        ->when($request->sort=='oldest',
                            fn($q)=>$q->oldest(),
                            fn($q)=>$q->latest()
                        )
                        ->paginate($request->per_page  ?? 10);

        return NewsletterSubscriberResource::collection($newsletters);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'  => 'required|email|unique:newsletter_subscribers,email',
            'status' => 'required|in:Subscribed,Unsubscribed',
        ]);

        try {
            $data = DB::transaction(function () use ($request) {
                $newsletter = NewsletterSubscriber::create([
                    'email'         => $request->email,
                    'status'        => $request->status,
                    'subscribed_at' => now(),
                    'user_id'       => Auth::id() ?? 1,
                ]);

                ActivityLogHelper::log('Newsletter', ActivityLogEnum::CREATE->value,
                                    'Newsletter subscriber created successfully.', $newsletter);
                return $newsletter;
            });

            return response()->json([
                'success' => true,
                'message' => 'Newsletter subscriber created successfully.',
                'page'    => new NewsletterSubscriberResource($data)
            ]);

        } catch(Throwable $e){
            return response()->json([
                'success'=>false,
                'message'=>$e->getMessage()
            ],500);
        }
    }

    public function edit($id)
    {
        $newsletter = NewsletterSubscriber::with('user')->findOrFail($id);

        return new NewsletterSubscriberResource($newsletter);
    }

    public function update(Request $request, $id)
    {
        $newsLetter = NewsletterSubscriber::findOrFail($id);
        $request->validate([
            'email'  => ['required','email',
                        Rule::unique('newsletter_subscribers')->ignore($newsLetter->id)
                    ],
            'status'=>'required|in:Subscribed,Unsubscribed',
        ]);

        $newsLetter->update([
            'email'             => $request->email,
            'status'            => $request->status,
            'subscribed_at'     => $request->status == 'Subscribed' ? now() : $newsLetter->subscribed_at,
            'unsubscribed_at'   => $request->status == 'Unsubscribed' ? now() : null,
            'user_id'           => Auth::id(),
        ]);

        ActivityLogHelper::log('Newsletter', ActivityLogEnum::UPDATE->value,
                            'Newsletter subscribed updated successfully.', $newsLetter);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscribed updated successfully.',
            'newsletter' => new NewsletterSubscriberResource($newsLetter)
        ]);    
    }

    public function destroy($id)
    {
        $newsLetter = NewsletterSubscriber::findOrFail($id);
        ActivityLogHelper::log('Newsletter', ActivityLogEnum::DELETE->value,
                            'Newsletter subscriber deleted successfully.',$newsLetter);

        $newsLetter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscribed deleted successfully.',
        ]);
    }

    public function changeStatus($id)
    {
        $newsletter = NewsletterSubscriber::findOrFail($id);

        if($newsletter->status == 'Subscribed'){
            $newsletter->status = 'Unsubscribed';
            $newsletter->unsubscribed_at = now();
        }else{
            $newsletter->status = 'Subscribed';
            $newsletter->subscribed_at = now();
            $newsletter->unsubscribed_at = null;
        }

        ActivityLogHelper::log('Newsletter', ActivityLogEnum::STATUS_CHANGE->value,
                            'Newsletter subscriber status updated successfully.',$newsletter);

        $newsletter->user_id = Auth::id();
        $newsletter->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status'  => $newsletter->status
        ]);
    }

    public function deletedSubscriberList()
    {
        $newsletter = NewsletterSubscriber::onlyTrashed()->latest()->paginate(10);

        return response()->json([
            'success'    => true,
            'newsletter' => $newsletter
        ]);
    }

    public function restore($id)
    {
        $newsletter = NewsletterSubscriber::onlyTrashed()->findOrFail($id);
        $newsletter->restore();

        ActivityLogHelper::log('Newsletter', ActivityLogEnum::RESTORE->value,
                            'Newsletter subscriber restored successfully.', $newsletter);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscriber restored successfully',
            'newsletter' => $newsletter
        ]);
    }

    public function forceDelete($id)
    {
        $newsletter = NewsletterSubscriber::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Newsletter', ActivityLogEnum::FORCE_DELETE->value,
                            'Newsletter subscriber permanently deleted.', $newsletter);

        $newsletter->forceDelete();

        return response()->json([
            'success'=>true,
            'message'=>'Newsletter subscriber permanently deleted.'
        ]);
    }
}
