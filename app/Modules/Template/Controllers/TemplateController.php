<?php

namespace App\Modules\Template\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Template\Models\Template;
use App\Modules\Template\Models\TemplateValidation;
use Auth;
use App\Helper\ResponseHelper;

class TemplateController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    public function getTemplates()
    {
              if(Auth::user()->role != "sadm"&&Auth::user()->role != "adm"){
             return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $templates = Template::all();
        if($templates){
            return ResponseHelper::success('Templates fetched',$templates, 200);
        }else{
            return ResponseHelper::success('No Data Found', [], 200);

        }
    }
    
    public function createTemplate(Request $request)
    {
       	$validator = TemplateValidation::createTemplate($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Error', $validator->errors(), 422);
        }
          if(Auth::user()->role != "sadm"&&Auth::user()->role != "adm"){
             return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $template=new Template;
        $template->vendor=$request->vendor;
        $template->name=$request->name;
        $template->header=$request->header;
        $template->message=$request->message;
        $template->temp_id=$request->temp_id;
        $template->status = $request->status ?? 1;

        if($template->save()){
            return ResponseHelper::success('Template Created Successfully',$template, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
    
    public function updateTemplate(Request $request)
    {
       	$validator = TemplateValidation::updateTemplate($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'', 422);
        }
          if(Auth::user()->role != "sadm"&&Auth::user()->role != "adm"){
             return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $template=Template::find($request->id);
        if($template){
                 $template->vendor=$request->vendor??$template->vendor;
                 $template->name=$request->name??$template->name;
                 $template->message=$request->message??$template->message;
                 $template->header=$request->header??$template->header;
                 $template->temp_id=$request->temp_id;
                 $template->status=$request->status??$template->status;
            if($template->save()){
                return ResponseHelper::success('Template updated Successfully', $template, 200);
            }
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);

        }
    }
    
       public function deleteTemplate(Request $request)
    {
        $validator = TemplateValidation::deleteTemplate($request->all());
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $template = Template::find($request->id);
        if (!$template) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Template not found'
            ], 404);
        }

        $template->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Template deleted successfully'
        ], 200);
    }

}
