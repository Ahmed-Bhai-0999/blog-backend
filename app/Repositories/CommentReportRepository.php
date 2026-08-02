<?php

namespace App\Repositories;

use App\Models\CommentReport;

class CommentReportRepository
{
    public function report(array $data)
    {
        $query = CommentReport::where('comment_id',$data['comment_id']);

        if (!empty($data['user_id'])){
            $query->where('reported_by',$data['user_id']);
        }else{
            $query->where('guest_token',$data['guest_token']);
        }

        if($query->exists()){
            throw new \Exception('You have already reported this comment.');
        }

        return CommentReport::create([
            'comment_id'  => $data['comment_id'],
            'reason'      => $data['reason'],
            'reported_by' => $data['user_id'] ?? null,
            'guest_token' => $data['user_id'] ? null : ($data['guest_token'] ?? null),
            'status'      => 'Pending',
            'admin_notes' => null
        ]);
    }

    public function all($search = null)
    {
        return CommentReport::with(['comment', 'reporter', 'reviewer'])
                ->when($search,function($q) use($search){
                    $q->whereHas('comment',function($query) use($search){
                        $query->where('comment','LIKE',"%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10);
    }

    public function find($id)
    {
        return CommentReport::with(['comment', 'reporter'])->findOrFail($id);
    }

    public function update(CommentReport $report,array $data)
    {
        $report->update($data);

        return $report->fresh();
    }

    public function delete(CommentReport $report)
    {
        return $report->delete();
    }
}