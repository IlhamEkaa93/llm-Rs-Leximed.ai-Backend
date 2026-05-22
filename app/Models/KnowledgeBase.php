<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * * @var array<int, string>
     */
    protected $fillable = [
        'title', 
        'category', 
        'file_path', 
        'version', 
        'description', 
        'status',
        'content' // <--- Sangat penting: Ini adalah kolom untuk menyimpan teks hasil parsing PDF
    ];
}