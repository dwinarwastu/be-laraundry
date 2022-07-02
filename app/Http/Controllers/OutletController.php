<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Outlet;

class OutletController extends Controller
{
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name_outlet' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        }

        $outlet              = new Outlet();
        $outlet->name_outlet = $request->name_outlet;
        $outlet->save();

        $data = Outlet::where('id_outlet', '=', $outlet->id_outlet)->first();
        return response()->json([
            'success' => true,
            'message' => 'Data outlet berhasil ditamahkan!',
            'data'    => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
			'name_outlet' => 'required|string|max:255'
		]);

		if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
		}

		$outlet              = Outlet::where('id_outlet', $id)->first();
		$outlet->name_outlet = $request->name_outlet;
		$outlet->save();

        return response()->json([
            'success' => true,
            'message' => 'Data outlet berhasil diubah!.'
        ]);
    }

    public function delete($id)
    {
        $delete = Outlet::where('id_outlet', $id)->delete();

        if($delete){
            return response()->json([
                'success' => true,
                'message' => 'Data outlet berhasil dihapus!',
            ]);   
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Data outlet gagal dihapus!',
            ]);
        }
    }

    public function getAll()
    {
        $data["count"]  = Outlet::count();
        $data["outlet"] = Outlet::get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);  
    }

    public function getById($id)
    {
        $data["outlet"] = Outlet::where('id_outlet', $id)->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);  
    }
}
