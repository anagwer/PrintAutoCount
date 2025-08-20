<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramRecipient extends BaseModel
{
    use HasUuids, HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected function searchableFields(): array
    {
        return ['nik', 'chat_id'];
    }

    protected function sortableFields(): array
    {
        return ['nik', 'chat_id', 'status', 'created_at'];
    }

    protected function validatedFields($id = null): array
    {
        return [
            'nik' => 'required|string|max:30|unique:telegram_recipients,nik,' . $id,
            'chat_id' => 'required',
            'status' => 'sometimes|in:0,1,2',
            // 'file_path' => 'nullable|file',
        ];
    }
}
