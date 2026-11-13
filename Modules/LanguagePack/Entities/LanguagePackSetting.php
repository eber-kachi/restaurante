<?php

namespace Modules\LanguagePack\Entities;

//use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class LanguagePackSetting extends Model
{
    protected $guarded = ['id'];

    const MODULE_NAME = 'languagepack';
}
