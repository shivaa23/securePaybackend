<?php
namespace App\Modules\Notification\Models;
use Illuminate\Database\Eloquent\Model;
use Validator;

class NotificationValidation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
	//
    ];
    
  

    public static function createNotification($request)
    {
        return Validator::make($request, [
            'user_id'    => 'required', 
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'type'       => 'nullable|string|in:info,alert,system',
        ]);
    }

 
    public static function updateNotification($request)
    {
        return Validator::make($request, [
            'id'         => 'required|integer|exists:notifications,id',
            'title'      => 'sometimes|string|max:255',
            'message'    => 'sometimes|string',
            'type'       => 'sometimes|string|in:info,alert,system',
            'is_read'    => 'sometimes|boolean',
        ]);
    }
public static function notificationSchema()
    {
        return [
            "formName" => "Create Notification",
            "fields" => [
                [
                    "name" => "user_id",
                    "label" => "User ID",
                    "type" => "number",
                    "placeholder" => "Enter user ID",
                    "required" => true,
                    "validation" => ["type" => "integer", "exists" => "users,id"]
                ],
                [
                    "name" => "title",
                    "label" => "Title",
                    "type" => "text",
                    "placeholder" => "Enter title",
                    "required" => true,
                    "validation" => ["type" => "string", "maxLength" => 255]
                ],
                [
                    "name" => "message",
                    "label" => "Message",
                    "type" => "textarea",
                    "placeholder" => "Enter message",
                    "required" => true,
                    "validation" => ["type" => "string"]
                ],
                [
                    "name" => "type",
                    "label" => "Notification Type",
                    "type" => "dropdown",
                    "placeholder" => "Select type (optional)",
                    "required" => false,
                    "options" => [
                        ["label" => "Info", "value" => "info"],
                        ["label" => "Alert", "value" => "alert"],
                        ["label" => "System", "value" => "system"],
                    ],
                    "validation" => ["nullable" => true, "in" => ["info","alert","system"]]
                ]
            ]
        ];
    }



    public static function deleteNotification($request)
    {
        return Validator::make($request, [
            'id' => 'required|integer|exists:notifications,id',
        ]);
    }
}
