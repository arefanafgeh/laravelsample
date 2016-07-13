<?php
/**
 * Created by PhpStorm.
 * User: aref
 * Date: 6/11/16
 * Time: 10:02 AM
 */

namespace App;
use Illuminate\Database\Eloquent\Model;
use App\AdImage;
use App\Comment;
class Ad extends  Model
{
    protected $primaryKey = "id";
    protected $fillable =[
      'name' , 'company','model','description' , 'price' ,'cat_id' ,'user_id'
    ];

    public function user(){
        return $this->belongsTo('App\User' ,'user_id');
        
    }

    public function ad_category(){
        return $this->belongsTo('App\AdCategory' ,'cat_id');
    }
    public function ad_images(){
        return $this->hasMany('App\AdImage');
    }

    public function bids(){
        return $this->hasMany('App\Bid');
    }

    public function saveImagedata($data = ''){
        $array = explode(',',$data );
        $adImageModel = new AdImage();
        foreach ($array as $id){
            $id = trim($id);
            if(empty($id))
                continue;
            $imageRow = $adImageModel::find($id);
            $imageRow->ad_id = $this->id;
            $imageRow->save();
        }
    }

    public function getFirstImageName(){
        $image = $this->ad_images()->first();
        if(!$image || is_null($image))
            return 'musigatedef2.png';
        return $image->filename;
    }

    public function isBidable(){
        $acceptedBid = Bid::where('ad_id','=' ,$this->id)
            ->where('accepted','=' ,'ACCEPTED')->get();
        if(sizeof($acceptedBid)>0)
            return false;
        else
            return true;
    }

    public function getComments(){

        $model =new Comment();
        $comments = $model->getComments($this->id ,'AD');
        return $comments;
    }


}
