<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\PageServices;
use Illuminate\Http\Request;


class PageController extends Controller
{
    protected $page;

    public function __construct(PageServices $page)
    {
        $this->page =  $page;
    }

    /**
     * Display a listing of the resource.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Page::orderBy('id','desc');

          if ($request->ajax()) {
            $data = Page::orderBy('id','desc');

            return DataTables::of($data)
            ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('admin.page.edit',[$row->id]).'" class="btn btn-primary btn-sm">Edit</a>';
                    $btn .= '&nbsp<a href="'.route('admin.page.destroy',[$row->id]).'" class="btn btn-danger btn-delete-post btn-sm">Delete</a>';
                    return $btn;
                })
                ->rawColumns(['action','content'])
                ->make(true);
        }

        return view('admin.page.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try{
            return view('admin.page.create');
        }catch(Exception $e)
        {
            Log::error($e->getMessage());
            return back()->with(['message' => __('global.server_error')]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $page = $this->page->store($request->validated());

            return redirect()->route('admin.page.index')->with(['message'=> "Successfully Store"]);
        }catch(Exception $e)
        {
            Log::error($e->getMessage());
            return back()->with(['message' => __('global.server_error')]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $page = Page::whereId($id)->first();
        return view('admin.page.create',['page'=>$page]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $page = $this->page->update($request->validated(),$id);

            return redirect()->route('admin.page.index')->with(['message'=>"Page Updated Successfully"]);
        }catch(Exception $e)
        {
            Log::error($e->getMessage());
            return back()->with(['message' => __('global.server_error')]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
         Page::whereId($id)->delete();
         return response()->json(['status'=>true]);
    }
}
