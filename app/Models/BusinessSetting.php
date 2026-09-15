<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'address',
    ];

    public function logoPath(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        $path = public_path('storage/'.$this->logo);

        return file_exists($path) ? $path : null;
    }
}
