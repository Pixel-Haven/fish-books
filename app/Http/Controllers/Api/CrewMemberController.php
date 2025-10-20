<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCrewMemberRequest;
use App\Http\Requests\UpdateCrewMemberRequest;
use App\Models\CrewMember;
use Illuminate\Http\Request;

class CrewMemberController extends Controller
{
    /**
     * Display a listing of crew members with optional filters
     */
    public function index(Request $request)
    {
        $query = CrewMember::query()->with('creator');

        // Filter by active status
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Search by name, local_name, or phone
        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate or get all
        if ($request->boolean('all')) {
            $crewMembers = $query->get();
            return response()->json($crewMembers);
        }

        $crewMembers = $query->paginate($request->input('per_page', 15));
        
        return response()->json($crewMembers);
    }

    /**
     * Store a newly created crew member
     */
    public function store(StoreCrewMemberRequest $request)
    {
        $crewMember = CrewMember::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Crew member created successfully.',
            'data' => $crewMember->load('creator'),
        ], 201);
    }

    /**
     * Display the specified crew member
     */
    public function show(CrewMember $crewMember)
    {
        $crewMember->load(['creator', 'tripAssignments.trip', 'weeklyPayouts.weeklySheet']);
        
        return response()->json($crewMember);
    }

    /**
     * Update the specified crew member
     */
    public function update(UpdateCrewMemberRequest $request, CrewMember $crewMember)
    {
        // Only OWNER can change active status if member has payouts
        if ($request->has('active') && $request->user()->role !== 'OWNER') {
            $hasPayouts = $crewMember->weeklyPayouts()->exists();
            if ($hasPayouts) {
                return response()->json([
                    'message' => 'Only owners can change active status for crew members with payout history.',
                ], 403);
            }
        }

        $crewMember->update($request->validated());

        return response()->json([
            'message' => 'Crew member updated successfully.',
            'data' => $crewMember->load('creator'),
        ]);
    }

    /**
     * Remove the specified crew member (soft delete)
     */
    public function destroy(CrewMember $crewMember, Request $request)
    {
        // Only OWNER can delete
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can delete crew members.',
            ], 403);
        }

        // Check if member has any payouts
        if ($crewMember->weeklyPayouts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete crew member with payout history. Consider marking as inactive instead.',
            ], 422);
        }

        $crewMember->delete();

        return response()->json([
            'message' => 'Crew member deleted successfully.',
        ]);
    }

    /**
     * Restore a soft-deleted crew member
     */
    public function restore($id, Request $request)
    {
        // Only OWNER can restore
        if ($request->user()->role !== 'OWNER') {
            return response()->json([
                'message' => 'Only owners can restore crew members.',
            ], 403);
        }

        $crewMember = CrewMember::withTrashed()->findOrFail($id);
        $crewMember->restore();

        return response()->json([
            'message' => 'Crew member restored successfully.',
            'data' => $crewMember,
        ]);
    }
}
