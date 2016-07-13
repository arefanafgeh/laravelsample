<?php
/**
 * Created by PhpStorm.
 * User: aref
 * Date: 6/11/16
 * Time: 10:17 AM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class AdCategory extends Model
{

    protected $table ='ad_category';
    protected $primaryKey = 'id';
    protected $fillable =[
        'name','parent'
    ];
    
    public function ads(){
        return $this->hasMany('App\Ad');
    }

    public function getChilds(){
        return $this->where('parent' ,'=' ,$this->id)->get();
    }
    public function parent(){
        return $this->where('id' ,'=' ,$this->parent)->get()->first();
    }
}
