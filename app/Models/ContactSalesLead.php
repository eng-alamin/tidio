<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSalesLead extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'company', 'phone', 'message', 'source_page', 'status'];

    protected $casts = ['status' => LeadStatus::class];
}
