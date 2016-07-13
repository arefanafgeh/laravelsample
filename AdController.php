<?php

namespace App\Http\Controllers;

use App\AdImage;
use Validator;
use Illuminate\Http\Request;
use App\AdCategory;
use App\Ad;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
class AdController extends Controller
{
    //

    public  function __construct()
    {
//        $this->middleware('auth');
        $this->middleware('web');
    }

    public function index(Request $request){
        $breadCrumb = [
            0=>['title'=>'آگهی ها']
        ];
        $catid = $request->catid;
        $orderby = $request->orderby;
        $ordertype =$request->ordertype;
        if(empty($orderby))
            $orderby ="created_at";
        if(empty($ordertype))
            $ordertype ="desc";
        $search = $request->search;
        $adModel = new Ad();
        $query = $adModel->query();
        if(!is_null($catid)){
            $query->where('cat_id' ,'=' ,$catid);
        }
        if(!empty($search)){
            $query->Where('name' ,'LIKE' ,'%'.$search.'%');
            $query->orWhere('description' ,'LIKE' ,'%'.$search.'%');
            $query->orWhere('model' ,'LIKE' ,'%'.$search.'%');
            $query->orWhere('company' ,'LIKE' ,'%'.$search.'%');
//            $query->orWhere('' ,'LIKE' ,'%'.$search.'%')
        }

        $query->orderBy($orderby, $ordertype);
        $ads = $query->paginate(15);
        $cats = AdCategory::where('parent' ,'=' ,0)->get();
        $selectedCat = AdCategory::find($catid);
        if($selectedCat){
            $breadCrumb[0]['link'] ='/ads';
            $breadCrumb[1]['title'] =(!is_null($selectedCat->parent()))?
                $selectedCat->parent()->name.'/'.$selectedCat->name:$selectedCat->name;
        }
        return view('ad.index')->with('ads' ,$ads)
            ->with('cats' ,$cats)
            ->with('selectedcat' ,$selectedCat)
            ->with('searchterm' ,$search)
            ->with('breadcrumb' ,$breadCrumb);
    }

    protected function view($id){
        $breadCrumb = [
            0=>['title'=>'آگهی ها' ,'link'=>'/ads']
        ];
        if(empty($id))
            return redirect('ads');
        $ad = Ad::find($id);
        if(!$ad)
            return redirect('ads');
        $user =$ad->user;
        if($ad->ad_category)
            $breadCrumb[] =['title'=>$ad->ad_category->name ,'link'=>'/ads?catid='.$ad->ad_category->id];
        $breadCrumb[] =['title'=>$ad->name];
        $adImages = $ad->ad_images;
        return view('ad.view')->with('ad' ,$ad)
            ->with('user' ,$user)
            ->with('images' ,$adImages)
            ->with('breadcrumb' ,$breadCrumb);
    }
    public function newadform(){
        $breadCrumb = [
            0=>['title'=>'پروفایل' ,'link'=>'/profile'],
            1=>['title'=>'ثبت آگهی جدید']
        ];
        $catsModel = new AdCategory();
        $this->middleware('auth');
        return view('ad.add')->with('cats' ,$catsModel->all())
            ->with('breadcrumb' ,$breadCrumb);
    }

    public function editadform($id){
        $breadCrumb = [
            0=>['title'=>'پروفایل' ,'link'=>'/profile'],
            1=>['title'=>'ویرایش آگهی']
        ];
        if(empty($id))
            return redirect('profile');
        $ad = Ad::find($id);
        if(!$ad)
            return redirect('profile');
        $catsModel = new AdCategory();
        $this->middleware('auth');
        $images = AdImage::where('ad_id','=' ,$id)->get();
        return view('ad.edit')->with('cats' ,$catsModel->all())
            ->with('ad' ,$ad)
            ->with('adImages' ,$images)->with('breadcrumb' ,$breadCrumb);

    }
    public function newad(Request $request){
        $messages = [
            'name.required' => 'پر کردن نام الزامی است',
            'description.required' => 'پر کردن توضیحات الزامی است',
            'price.required' => 'پر کردن قیمت الزامی است',
            'email' => 'ایمیل وارد شده معتبر نمیباشد',
            'unique' => 'این داده در سامانه قیلا به ثبت رسیده است.',
            'max' => 'حداکثر ورودی مجاز :max حرف است',
            'min' => 'حداقل تعداد حروف باید :min باشد.',
            'confirmed'=>'دو مقدار وارد شده با هم برابر نیستند.'
        ];
        $this->validate($request,[
            'name'=>'required|min:5',
            'model'=>'min:3',
            'company'=>'min:5',
            'description'=>'required|min:5',
            'price'=>'required|min:5',
            'cat_id'=>'numeric',
        ],$messages);
        $user = Auth::user();
        $data = $request->all();
        $data['user_id'] =$user->id;
        $adModel =  new Ad();
        $obj =$adModel->create($data);
        echo $obj->saveImagedata($data['images_id']);
        session()->flash('flash-status' ,'SUCCESS');
        session()->flash('flash-msg' ,'آگهی با موفقیت ثبت شد.پس از تایید ادمین شما ایمیلی دریافت خواهید کرد و آگهی شما فعال خواهد شد');
        return redirect('profile');
    }

    public function updatead(Request $request ,$id){
        if(empty($id))
            return redirect('profile');
        $ad = Ad::find($id);
        if(!$ad)
            return redirect('profile');
        $this->middleware('auth');

        $this->validate($request,[
            'name'=>'required|min:5',
            'model'=>'min:3',
            'company'=>'min:5',
            'description'=>'required|min:5',
            'price'=>'required|min:5',
            'cat_id'=>'numeric',
        ]);
        $user = Auth::user();
        $data = $request->all();
        $data['user_id'] =$user->id;
        $ad->update($data);
        echo $ad->saveImagedata($data['images_id']);
        session()->flash('flash-status' ,'SUCCESS');
        session()->flash('flash-msg' ,'آگهی با موفقیت ویرایش شد و پس از تایید ادمین سامانه برای دیگر کاربران نمایش داده میشود.');
        return redirect('profile');
    }
    public function newimage(){
        $adImageModel =new AdImage();
        $file = array('image' => Input::file('upl'));
        $rules =array('image' => 'required|Image|Mimes:jpg,gif,png,jpeg');

        $validatpr = Validator::make($file, $rules);
        if ($validatpr->fails()) {
            return response()->json(['status' => 'error', 'msg' => 'Data Not Valid']);
        }
        if(Input::file('upl')->isValid()){
                $destPath ='uploads/ads';
                $extenstion =Input::file('upl')->getClientOriginalExtension();
                $filename = uniqid().'.'.$extenstion;
                Input::file('upl')->move($destPath,$filename);
                $adImageModel->filename =$filename;
                $adImageModel->save();
                return response()->json(['status' => 'success', 'id' => $adImageModel->id]);
        }else{
            return response()->json(['status' => 'error', 'msg' => 'Data Not Valid']);
        }
    }

    public function removeimage($id){
        if(!$id)
            return response()->json(['status' => 'error', 'msg' => 'Data Not Valid']);
//        $adImageModel = new AdImage();
        $adImage = AdImage::find($id);
        $user =Auth::user();
        $ad = $adImage->ad;
        if($ad->user_id!==$user->id){
            return response()->json(['status' => 'error', 'msg' => 'You dont have permission']);
        }
        if(!$adImage)
            return response()->json(['status' => 'error', 'msg' => 'Data Not Valid']);
        unlink(PUBLIC_DIR.'/uploads/ads/'.$adImage->filename);
        AdImage::destroy($id);
        return response()->json(['status' => 'success', 'msg' =>'image destroyed succesfuly']);
    }
    
    protected function delete($id){
//        $id =$request->id;

        if(!$id) {
            session()->flash('flash-status' ,'ERROR');
            session()->flash('flash-msg' ,'اختلالی در دادههای ارسالی مشاهده شد.لطفا دوباره تلاش کنید.');

            return redirect('profile');
        }
        $user =Auth::user();
        if(!$user)
            return redirect('/');
        $ad = Ad::find($id);
        if(!$ad)
            return redirect('/');
        if($ad->user->name !==$user->name)
            return redirect('/');
        Ad::destroy($id);
        session()->flash('flash-status' ,'SUCCESS');
        session()->flash('flash-msg' ,'آگهی با موفقیت حذف شد.');
        return redirect('profile');
    }
    
    protected function deletesubmit($id){
        if(!$id)
            return view('ad.delete')->with('msg' ,'اطلاعات ارسال شده اشتباه است یا شما دسترسی لازم را ندارید.');
        $user =Auth::user();
        if(!$user)
            return redirect('/');
        $ad = Ad::find($id);
        if(!$ad)
            return view('ad.delete')->with('msg' ,'اطلاعات ارسال شده اشتباه است یا شما دسترسی لازم را ندارید.');
        if($ad->user_id!==$user->id)
            return view('ad.delete')->with('msg' ,'اطلاعات ارسال شده اشتباه است یا شما دسترسی لازم را ندارید.');


        return view('ad.delete')->with('ad' ,$ad);
    }
}
