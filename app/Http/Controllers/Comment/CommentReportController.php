<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentReportResource;
use App\Repositories\CommentReportRepository;
use Illuminate\Http\Request;

class CommentReportController extends Controller
{
    protected CommentReportRepository $repository;

    public function __construct(CommentReportRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $reports = $this->repository->all(
            $request->search
        );

        return CommentReportResource::collection($reports);
    }

    public function show($id)
    {
        return new CommentReportResource(
            $this->repository->find($id)
        );
    }

    public function updateStatus(Request $request,$id)
    {
        $request->validate([
            'status'    => 'required|in:Pending,Reviewed,Actioned',
            'admin_notes' => 'nullable|string'

        ]);

        $report = $this->repository->find($id);
        $this->repository->update($report,[
            'status' => $request->status,
            'admin_notes' => $request->admin_notes
        ]);

        return response()->json([
            'success'=>true,
            'message'=>'Report updated.'
        ]);
    }

    public function destroy($id)
    {
        $report = $this->repository->find($id);
        $this->repository->delete($report);
        return response()->json([
            'success'=>true,
            'message'=>'Report deleted.'
        ]);
    }
}
