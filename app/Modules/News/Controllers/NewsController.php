<?php

namespace App\Modules\News\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\News\Models\News;
use App\Modules\News\Models\NewsValidation;
use Auth;
use App\Helper\ResponseHelper;

class NewsController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
    }
    
  public function getNews(Request $request)
{
    // if (Auth::user()->role != "sadm" && Auth::user()->role != "adm") {
    //     return ResponseHelper::error('Action not Authorised', '', 403);
    // }
    
    $news = News::all();

    if ($news->isNotEmpty()) {
        return ResponseHelper::success('News fetched', $news, 200);
    } else {
        return ResponseHelper::error('No Data Found', [], 200);
    }
}

    public function createNews(Request $request)
    {
       	$validator = NewsValidation::createNews($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed',$validator->errors(), 422);
        }
        if(Auth::user()->role!='sadm'&&Auth::user()->role != "adm"){
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $news=new News;
        $news->description=$request->description;
       
        
        if($news->save()){
            return ResponseHelper::success('News Created Successfully', $news, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
    public function deleteNews(Request $request)
{
    if(Auth::user()->role != 'sadm' && Auth::user()->role != 'adm'){
        return ResponseHelper::error('Action not Authorised', '', 403);
    }

        $news = News::find($request->id);

    if(!$news){
        return ResponseHelper::error('News not found', [], 404);
    }

    if($news->delete()){
        return ResponseHelper::success('News deleted successfully', [], 200);
    } else {
        return ResponseHelper::error('Something went wrong', '', 500);
    }
}

}
