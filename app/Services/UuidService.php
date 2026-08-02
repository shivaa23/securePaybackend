<?php

namespace App\Services;

use App\Modules\Misc\Models\Uuid;
use Illuminate\Support\Str;

class UuidService
{
    public function generate()
    {
        return Uuid::create([
            'uuid' => Str::uuid()->toString(),
            'status' => 1,
        ]);
    }

    public function check($uuid)
    {
        $record = Uuid::where('uuid', $uuid)->first();

        if (!$record) {
            return ["code" => 403, "message" => "UUID not found"];
        }

        if ($record->status != 1) {
            return ["code" => 403, "message" => "UUID already used or inactive"];
        }

        $record->update(['status' => 0]);

        return ["code" => 200, "message" => "UUID validated and updated"];
    }
}
