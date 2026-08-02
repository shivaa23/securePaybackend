<?php

namespace App\Modules\Notification\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Modules\Notification\Models\Notification;
use App\Modules\Notification\Models\NotificationValidation; 
use App\Helper\ResponseHelper;
use App\Modules\User\Models\User;
use Auth;

class NotificationController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => []]);
         $this->middleware('check.active', ['except' => []]);
    }
    public function getNotification()
    {
        $user = Auth::user();
    
      
        if(!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthenticated'
            ], 401);
        }
    
    if (in_array($user->role, ['adm', 'sadm'])) {
      
        $notifications = Notification::orderBy('created_at', 'desc')->paginate(10);
    } else {
      
        $notifications = Notification::where('user_id', $user->id)
                                     ->orderBy('created_at', 'desc')
                                     ->paginate(10);
    }
    
    
    
        if ($notifications->count() > 0) {
            return ResponseHelper::success( 'Notifications fetched',$notifications, 200);
        } else {
            return ResponseHelper::success('No Data Found', [], 200);
        }
    }
    public function getNotificationSchema()
    {
        $schema = NotificationValidation::notificationSchema(); 
        return ResponseHelper::success('Notifications fetched', $schema, 200);
    }
    public function createNotification(Request $request)
    {
        $validator = NotificationValidation::createNotification($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        if ($request->has('user_id') && $request->user_id == "all") {
            $users = User::all('id');
            $sentNotifications = [];
    
            foreach ($users as $user) {
                $notification = new Notification();
                $notification->user_id = $user->id;
                $notification->title   = $request->title;
                $notification->message = $request->message;
                $notification->type    = $request->type ?? 'info';
                $notification->is_read = 0;
    
                if ($notification->save()) {
                    $sentNotifications[] = $notification;
                }
            }
    
            return ResponseHelper::success(
                'Notification sent to all users successfully',
                $sentNotifications,
                201
            );
        }
    
        // Otherwise, send to single user
        $notification = new Notification();
        $notification->user_id = $request->user_id;
        $notification->title   = $request->title;
        $notification->message = $request->message;
        $notification->type    = $request->type ?? 'info';
        $notification->is_read = 0;
    
        if ($notification->save()) {
            return ResponseHelper::success(
                'Notification created successfully',
                $notification,
                201
            );
        }
    
        return ResponseHelper::error('Something went wrong', [], 500);
    }


public function updateNotification(Request $request)
{
    $user = Auth::user();

    // 🔹 Case 1: No ID → bulk mark all as read
    if (!$request->has('id')) {
        Notification::where('user_id', $user->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json([
            'status'  => 'success',
            'message' => 'All notifications marked as read'
        ], 200);
    }

    // 🔹 Case 2: Single notification update
    $validator = NotificationValidation::updateNotification($request->all());
    if ($validator->fails()) {
        return response()->json([
            'status'  => 'failed',
            'errors'  => $validator->errors()
        ], 422);
    }

    $notification = Notification::where('id', $request->id)
        ->where('user_id', $user->id)
        ->first();

    if (!$notification) {
        return response()->json([
            'status'  => 'failed',
            'message' => 'Notification not found'
        ], 404);
    }

    // Update fields if provided
    $notification->title   = $request->title   ?? $notification->title;
    $notification->message = $request->message ?? $notification->message;
    $notification->type    = $request->type    ?? $notification->type;
    $notification->is_read = $request->is_read ?? $notification->is_read ?? 1; // default: mark as read

    if ($notification->save()) {
        return response()->json([
            'status'  => 'success',
            'message' => 'Notification updated successfully',
            'data'    => $notification
        ], 200);
    }

    return response()->json([
        'status'  => 'failed',
        'message' => 'Something went wrong'
    ], 500);
}


    public function deleteNotification(Request $request)
    {
        $validator = NotificationValidation::deleteNotification($request->all());
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $notification = Notification::find($request->id);
        if (!$notification) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Notification deleted successfully'
        ], 200);
    }
}