<?php
// app/Models/WorkHistory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkHistory extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'position',
        'start_date',
        'end_date',
        'is_current',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
