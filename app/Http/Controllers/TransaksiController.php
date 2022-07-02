<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use DB;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransaksiController extends Controller
{
    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'id_member'         => 'required|numeric',
            'tgl'               => 'required|date',
            'lama_pengerjaan'   => 'required|numeric',
            'id_user'           => 'required|numeric',

		]);

		if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
		}

        //Menghitung Data Batas Waktu
        $tgl_transaksi = date_create($request->tgl);
        date_add($tgl_transaksi, date_interval_create_from_date_string($request->lama_pengerjaan."days"));
        $batas_waktu   = date_format($tgl_transaksi, 'Y-m-d');


		$transaksi              = new Transaksi();
		$transaksi->id_member   = $request->id_member;
        $transaksi->tgl         = $request->tgl;
        $transaksi->batas_waktu = $batas_waktu;
        $transaksi->id_user     = $request->id_user;
		$transaksi->save();

        //Insert Detail Transaksi
        for($i = 0; $i < count($request->detail); $i++){
            $detail_transaksi               = new DetailTransaksi();
            $detail_transaksi->id_transaksi = $transaksi->id_transaksi;
            $detail_transaksi->id_paket     = $request->detail[$i]['id_paket'];
            $detail_transaksi->weight       = $request->detail[$i]['weight'];
            $detail_transaksi->save();
        }

        $data = Transaksi::where('id_transaksi','=', $transaksi->id_transaksi)->first();

        return response()->json([
            'success' => true,
            'message' => 'Data transaksi berhasil ditambahkan!.',
            'data'    =>  $data
        ]);
    }

    public function update_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'id_transaksi' => 'required|numeric',
            'status'       => 'required|string',    
		]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
		}

        $transaksi         = Transaksi::where('id_transaksi', $request->id_transaksi)->first();
		$transaksi->status = $request->status;
		$transaksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Data transaksi berhasil diubah menjadi '.$request->status,
        ]);
    }

    public function update_bayar(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'id_transaksi'  => 'required|numeric',
            'status'       => 'required|string',
		]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
		}

        $transaksi          = Transaksi::where('id_transaksi', $request->id_transaksi)->first();
		$transaksi->dibayar = $request->status;

        if($request->status == 'dibayar'){
            $transaksi->tgl_bayar = date('Y-m-d H:i:s');
        } else {
            $transaksi->tgl_bayar = NULL;
        }
        
		$transaksi->save();

        return response()->json([
            'success' => true,
            'message' => 'Data pembayaran berhasil diubah menjadi '.$request->status,
        ]);

    }

    public function report(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $query = DB::table('transaksi')
                    ->select('transaksi.id_transaksi', 'transaksi.tgl', 'transaksi.status', 'transaksi.dibayar', 'transaksi.tgl_bayar', 'users.name as nama_user', 'member.name as nama_member')
                    ->join('users' , 'users.id'        , '=', 'transaksi.id_user')
                    ->join('outlet', 'outlet.id_outlet', '=', 'users.id_outlet')
                    ->join('member', 'member.id_member', '=', 'transaksi.id_member')
                    ->where('users.id_outlet', '=', $user['id_outlet']);

        if($request->tahun == ""){
            $query->whereYear('transaksi.tgl', '=', date('Y'));
        } else {
            $query->whereYear('transaksi.tgl', '=', $request->tahun);
        }

        if($request->bulan != ""){
            $query->WhereMonth('transaksi.tgl', '=', $request->bulan);
        }
        if($request->tgl != ""){
            $query->WhereDay('transaksi.tgl', '=', $request->tgl);
        }
        
        if(count($query->get()) > 0){
            $data['success'] = true;
            $i = 0;
            foreach($query->get() as $list){
                //Get Detail Transaksi
                $get_detail_transaksi = DB::table('detail_transaksi')
                                        ->select('detail_transaksi.id_detail_transaksi', 
                                                 'detail_transaksi.id_paket', 
                                                 'paket.jenis', 
                                                 'detail_transaksi.weight' ,
                                                 DB::raw('paket.harga * detail_transaksi.weight as sub_total')
                                                 )
                                        ->join('paket', 'paket.id_paket', "=", "detail_transaksi.id_paket")
                                        ->where('detail_transaksi.id_transaksi', '=', $list->id_transaksi)
                                        ->get();
                //Menghitung Total
                $total = 0;
                foreach($get_detail_transaksi as $sub_total){
                    $total += $sub_total->sub_total;
                }

                $data['data'][$i]['id_transaksi']     = $list->id_transaksi;
                $data['data'][$i]['tgl']              = $list->tgl;
                $data['data'][$i]['status']           = $list->status;
                $data['data'][$i]['dibayar']          = $list->dibayar;
                $data['data'][$i]['tgl_bayar']        = $list->tgl_bayar;
                $data['data'][$i]['kasir']            = $list->nama_user;
                $data['data'][$i]['nama_member']      = $list->nama_member;
                $data['data'][$i]['total']            = $total;
                $data['data'][$i]['detail_transaksi'] = $get_detail_transaksi;

                $i++;
            }
        } else {
            $data['status'] = false;
            $data['data']   = NULL;
        }

        return response()->json($data);
    }



}