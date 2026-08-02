<?php

namespace App\Http\Controllers\Contact;

use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = ContactMessage::with('user')
                    ->when($request->search,function($q) use($request){
                        $q->where(function($query) use($request){
                            $query->where('name','like',"%{$request->search}%")
                                ->orWhere('email','like',"%{$request->search}%")
                                ->orWhere('subject','like',"%{$request->search}%");
                        });
                    })
                    ->when($request->is_read !== null,function($q) use($request){
                        $q->where('is_read',$request->is_read);
                    })
                    ->latest()
                    ->paginate($request->per_page ?? 10);

        return ContactMessageResource::collection($messages);
    }

    public function store(Request $request)
    {
        $data = DB::transaction(function () use ($request){
            $request->validate([
                'name'      => 'required|string|max:255',
                'email'     => 'required|email|max:255',
                'phone'     => 'nullable|string|max:30',
                'subject'   => 'required|string|max:255',
                'message'   => 'required|string|max:5000',
            ]);

            $contactMessage = ContactMessage::create([
                'name'          => $request->name,
                'email'         => $request->email,
                'phone'         => $request->phone,
                'subject'       => $request->subject,
                'message'       => $request->message,
                'is_read'       => false,
                'user_id'       => Auth::check() ? Auth::id() : null,
            ]);   

            ActivityLogHelper::log('Contact Message', ActivityLogEnum::CREATE->value,
                                'Contact message created successfully.', $contactMessage);
            return $contactMessage;
        });

        return response()->json([
            'success' => true,
            'message' => 'Contact message created successfully.',
            'contactMessage' => new ContactMessageResource($data)
        ]);
    }

    public function edit($id)
    {
        return new ContactMessageResource(
            ContactMessage::with('user')->findOrFail($id)
        );
    }

    public function reply(Request $request, $id)
    {
        $data = DB::transaction(function () use ($request, $id){
            $request->validate([
                'reply' => 'required|string'
            ]);

            $message=ContactMessage::findOrFail($id);
            $message->update([
                'reply'     => $request->reply,
                'is_read'   => true,
                'replied_at'=> now(),
                'user_id'   => Auth::id()
            ]);

            ActivityLogHelper::log('Contact Message', ActivityLogEnum::UPDATE->value,
                                'Reply sent successfully.', $message);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully.',
            'data'    => $data
        ]);
    }

    public function markAsRead($id)
    {
        $message = ContactMessage::findOrFail($id);
        if ($message->is_read) {
            return response()->json([
                'success' => true,
                'message' => 'Contact message is already marked as read.',
                'data'    => new ContactMessageResource($message),
            ]);
        }

        $message->update([
            'is_read' => true,
            'user_id' => Auth::id(),
        ]);

        ActivityLogHelper::log('Contact Message', ActivityLogEnum::STATUS_CHANGE->value,
                            'Contact message marked as read.', $message);

        return response()->json([
            'success' => true,
            'message' => 'Contact message marked as read successfully.',
            'data'    => new ContactMessageResource($message),
        ]);
    }

    public function markAsUnread($id)
    {
        $message = ContactMessage::findOrFail($id);

        $message->update([
            'is_read' => false,
            'user_id' => Auth::id(),
        ]);

        ActivityLogHelper::log('Contact Message', ActivityLogEnum::STATUS_CHANGE->value,
                            'Contact message marked as unread.', $message);

        return response()->json([
            'success' => true,
            'message' => 'Marked as unread successfully.',
        ]);
    }

    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);

        ActivityLogHelper::log('Contact Message', ActivityLogEnum::DELETE->value,
                            'Contact message deleted successfully.', $message);

        $message->delete();

        return response()->json([
            'success'=>true,
            'message'=>'Contact message deleted successfully.'
        ]);
    }
}