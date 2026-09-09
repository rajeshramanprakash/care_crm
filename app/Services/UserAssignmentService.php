<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UserAssignmentService
{
    public function getAssigningUser($role_id, $location_id = null, $lead_type = null)
    {
        DB::beginTransaction();
        try {
            // First try to find users with the specified role and is_active = 1
            $query = User::whereRaw("FIND_IN_SET(?, role_id)", [$role_id])
                        ->where('is_active', 1);

            // For IVR leads, also check is_break = 0
            if ($lead_type === 'ivr') {
                $query->where('is_break', 0);
            }

            if ($lead_type) {
                $query->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
            }

            if ($location_id) {
                $query->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
            }

            $firstUserWithIsNext = $query->where('is_next', 1)
                ->orderBy('id', 'asc')
                ->first();

            if ($firstUserWithIsNext) {
                $updateQuery = User::whereRaw("FIND_IN_SET(?, role_id)", [$role_id])
                                  ->where('is_active', 1);
                
                // For IVR leads, also check is_break = 0
                if ($lead_type === 'ivr') {
                    $updateQuery->where('is_break', 0);
                }
                
                if ($lead_type) {
                    $updateQuery->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                }
                if ($location_id) {
                    $updateQuery->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                }
                $updateQuery->where('id', '!=', $firstUserWithIsNext->id)
                    ->update(['is_next' => 0]);
            }

            if (!$firstUserWithIsNext) {
                $query = User::whereRaw("FIND_IN_SET(?, role_id)", [$role_id])
                            ->where('is_active', 1);
                
                // For IVR leads, also check is_break = 0
                if ($lead_type === 'ivr') {
                    $query->where('is_break', 0);
                }
                
                if ($lead_type) {
                    $query->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                }
                if ($location_id) {
                    $query->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                }
                $firstUserWithIsNext = $query->orderBy('id', 'asc')
                    ->first();

                if (!$firstUserWithIsNext) {
                    // If no users found with specified role and is_active = 1, try with role_id 1
                    $fallbackQuery = User::whereRaw("FIND_IN_SET(?, role_id)", [1])
                                        ->where('is_active', 1);

                    // For IVR leads, also check is_break = 0
                    if ($lead_type === 'ivr') {
                        $fallbackQuery->where('is_break', 0);
                    }

                    if ($lead_type) {
                        $fallbackQuery->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                    }
                    if ($location_id) {
                        $fallbackQuery->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                    }

                    $firstUserWithIsNext = $fallbackQuery->where('is_next', 1)
                        ->orderBy('id', 'asc')
                        ->first();

                    if ($firstUserWithIsNext) {
                        $updateQuery = User::whereRaw("FIND_IN_SET(?, role_id)", [1])
                                          ->where('is_active', 1);
                        
                        // For IVR leads, also check is_break = 0
                        if ($lead_type === 'ivr') {
                            $updateQuery->where('is_break', 0);
                        }
                        
                        if ($lead_type) {
                            $updateQuery->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                        }
                        if ($location_id) {
                            $updateQuery->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                        }
                        $updateQuery->where('id', '!=', $firstUserWithIsNext->id)
                            ->update(['is_next' => 0]);
                    }

                    if (!$firstUserWithIsNext) {
                        $fallbackQuery = User::whereRaw("FIND_IN_SET(?, role_id)", [1])
                                            ->where('is_active', 1);
                        
                        // For IVR leads, also check is_break = 0
                        if ($lead_type === 'ivr') {
                            $fallbackQuery->where('is_break', 0);
                        }
                        
                        if ($lead_type) {
                            $fallbackQuery->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                        }
                        if ($location_id) {
                            $fallbackQuery->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                        }
                        $firstUserWithIsNext = $fallbackQuery->orderBy('id', 'asc')
                            ->first();

                        if (!$firstUserWithIsNext) {
                            throw new Exception('No active User available for the specified role or role_id 1');
                        }

                        $firstUserWithIsNext->is_next = 1;
                        $firstUserWithIsNext->save();
                        DB::commit();
                        return $firstUserWithIsNext;
                    } else {
                        $firstUserWithIsNext->is_next = 0;
                        $firstUserWithIsNext->save();

                        $fallbackQuery = User::whereRaw("FIND_IN_SET(?, role_id)", [1])
                                            ->where('is_active', 1);
                        
                        // For IVR leads, also check is_break = 0
                        if ($lead_type === 'ivr') {
                            $fallbackQuery->where('is_break', 0);
                        }
                        
                        if ($lead_type) {
                            $fallbackQuery->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                        }
                        if ($location_id) {
                            $fallbackQuery->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                        }
                        $nextUser = $fallbackQuery->where('id', '>', $firstUserWithIsNext->id)
                            ->orderBy('id', 'asc')
                            ->first();

                        if (!$nextUser) {
                            $fallbackQuery = User::whereRaw("FIND_IN_SET(?, role_id)", [1])
                                                ->where('is_active', 1);
                            
                            // For IVR leads, also check is_break = 0
                            if ($lead_type === 'ivr') {
                                $fallbackQuery->where('is_break', 0);
                            }
                            
                            if ($lead_type) {
                                $fallbackQuery->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                            }
                            if ($location_id) {
                                $fallbackQuery->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                            }
                            $nextUser = $fallbackQuery->orderBy('id', 'asc')
                                ->first();
                        }

                        $nextUser->is_next = 1;
                        $nextUser->save();
                    }
                } else {
                    $firstUserWithIsNext->is_next = 1;
                    $firstUserWithIsNext->save();
                    DB::commit();
                    return $firstUserWithIsNext;
                }
            } else {
                $firstUserWithIsNext->is_next = 0;
                $firstUserWithIsNext->save();

                $query = User::whereRaw("FIND_IN_SET(?, role_id)", [$role_id])
                            ->where('is_active', 1);
                
                // For IVR leads, also check is_break = 0
                if ($lead_type === 'ivr') {
                    $query->where('is_break', 0);
                }
                
                if ($lead_type) {
                    $query->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                }
                if ($location_id) {
                    $query->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                }
                $nextUser = $query->where('id', '>', $firstUserWithIsNext->id)
                    ->orderBy('id', 'asc')
                    ->first();

                if (!$nextUser) {
                    $query = User::whereRaw("FIND_IN_SET(?, role_id)", [$role_id])
                                ->where('is_active', 1);
                    
                    // For IVR leads, also check is_break = 0
                    if ($lead_type === 'ivr') {
                        $query->where('is_break', 0);
                    }
                    
                    if ($lead_type) {
                        $query->whereRaw("FIND_IN_SET(?, lead_type)", [$lead_type]);
                    }
                    if ($location_id) {
                        $query->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
                    }
                    $nextUser = $query->orderBy('id', 'asc')
                        ->first();
                }

                $nextUser->is_next = 1;
                $nextUser->save();
            }

            DB::commit();
            return $firstUserWithIsNext;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
            throw $e;
        }
    }
}