<?php

namespace App\Traits;

use Auth;
use Exception;
use Illuminate\Support\Facades\File;
use Validator;
use function public_path;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait JsonTrait
{

    public function updateJsonColumn($tableName, $columnName, $userId, $value)
    {
        // Retrieve the existing JSON data from the specified column
        $resData = DB::table($tableName)->first();

        if (!$resData) {
            return [
                'code' => 404,
                'status' => 'FAILED',
                'message' => "No data found in table $tableName"
            ];
        }

        // Decode the existing JSON data
        $activeData = json_decode($resData->$columnName, true);

        // Check if the decoding was successful and ensure it's an array
        if (!is_array($activeData)) {
            $activeData = [];
        }

        // Check if user_id already exists and update the value, otherwise add a new entry
        $found = false;
        foreach ($activeData as &$item) {
            if (isset($item[$userId])) {
                $item[$userId] = $value;
                $found = true;
                break;
            }
        }

        if (!$found) {
            // Append the new object to the active data array
            $activeData[] = [
                $userId => $value,
            ];
        }

        // Encode the merged array back to JSON
        $mergedArray = json_encode($activeData);

        // Update the specified column with the merged JSON data
        $updateResult = DB::table($tableName)->update([$columnName => $mergedArray]);

        if ($updateResult) {
            return [
                'code' => 200,
                'status' => 'SUCCESS',
                'message' => 'Data added/updated successfully'
            ];
        } else {
            return [
                'code' => 403,
                'status' => 'FAILURE',
                'message' => 'Data not added/updated successfully'
            ];
        }
    }

    // public static function addJsonObject($newArray, $modelName, $selectColumn, $searchColumn, $searchColumnValue){
    //     echo "inside add json object";
    //     $resData = $modelName::select($selectColumn)->where($searchColumn, $searchColumnValue)->first();
    //     // echo $resData;
    //     if(isset($resData) && $resData != null){
    //         $oldArray = json_decode($resData->$selectColumn, true);
    //         // echo json_encode($newArray);
    //         foreach ($newArray as $key => $value) {
    //             if (array_key_exists($key, $oldArray)) {
    //                 $oldArray[$key] = $value;
    //             }
    //         }
    //         $merged_array = $oldArray + $newArray;
    //         $merged_array = json_encode($merged_array);
    //         echo $merged_array;
    //         $addObjectRes = $resData->where($searchColumn, $searchColumnValue)->update([$selectColumn => $merged_array]);
    //         if($addObjectRes){
    //             echo "Data saved successfully"; // Debugging
    //         } else {
    //             echo "Data not saved"; // Debugging
    //         }
    //     }else{
    //         echo "data not saved";
    //     }
    // }
    public function getJsonValue($tableName, $columnName, $searchColumn, $searchValue, $userId)
    {
        // Retrieve the existing JSON data from the specified column for records matching the search criteria
        $resData = DB::table($tableName)->where($searchColumn, $searchValue)->get();
    
        if ($resData->isEmpty()) {
            return [
                'code' => 404,
                'status' => 'FAILED',
                'message' => "No data found in table $tableName where $searchColumn = $searchValue"
            ];
        }
    
        $userData = [];
    
        // Iterate through each record and filter out the data corresponding to the provided user ID
        foreach ($resData as $record) {
            // Decode the JSON data for the current record
            $activeData = json_decode($record->$columnName, true);
    
            // Check if the decoding was successful and ensure it's an array
            if (is_array($activeData)) {
                // Search for the user_id and add the data to the result array if found
                foreach ($activeData as $item) {
                    if (isset($item[$userId])) {
                        $userData[] = [
                            'user_id' => $userId,
                            'data' => $item[$userId]
                        ];
                        break; // Stop searching once the user_id is found
                    }
                }
            }
        }
    
        if (empty($userData)) {
            return [
                'code' => 404,
                'status' => 'FAILED',
                'message' => "No data found for user_id $userId where $searchColumn = $searchValue"
            ];
        }
    
        return [
            'code' => 200,
            'status' => 'SUCCESS',
            'message' => 'Data retrieved successfully',
            'data' => $userData
        ];
    }
    
    public function getData($assoc = false, $depth = 512)
    {
        return json_decode($this->data, $assoc, $depth);
    }


}




