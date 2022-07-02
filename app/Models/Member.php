<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable   = ['id_member', 'name', 'alamat', 'jenis_kelamin', 'tlp', 'created_at', 'updated_at'];
    protected $hidden     = ['created_at', 'updated_at'];
    protected $table      = "member";
    protected $primaryKey = 'id_member';
}
