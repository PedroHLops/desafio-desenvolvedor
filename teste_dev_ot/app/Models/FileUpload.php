<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FileUpload extends Model
{
    protected $connection = 'mysql';
    protected $table = 'uploads';
    
    protected $fillable = [
        'file_name',
        'storage_name', 
        'file_path',
        'file_size',
        'extension',
        'status',
        'error_message',
        'uploaded_by',
        'report_date',
        'file_hash'
    ];
    
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'report_date' => 'date'
    ];
    
    protected $attributes = [
        'status' => 'pending'
    ];
    
    // Relacionamento com os instrumentos
    // public function instruments()
    // {
    //     return $this->hasMany(Instrument::class, 'file_upload_id', 'id');
    // }
    
    
    // Status possíveis
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    // Métodos auxiliares
    public function markAsProcessing()
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }
    
    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED
        ]);
    }
    
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage
        ]);
    }
    
    public function getFullPathAttribute()
    {
        return storage_path('app/' . $this->file_path . '/' . $this->storage_name);
    }
    
}