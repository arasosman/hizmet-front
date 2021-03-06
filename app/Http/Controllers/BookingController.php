<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function showCreate()
    {
        return view('pages.booking.create_booking');
    }

    public function create(Request $request)
    {

        $client_id = Auth::user()->id;
        if ($request->input("submit_category") == "ev") {
            $service_id = 1;
            $room = $request->input("et_room_number");
            $m2 = $request->input("et_metrekare");
            $tarih = $request->input("et_tarih");
            $start = $request->input("et_start_time");
            $finish = $request->input("et_finish_time");
            $detail = $request->input("et_detail");
        } else {
            $service_id = 2;
            $room = $request->input("ot_room_number");
            $m2 = $request->input("ot_metrekare");
            $tarih = $request->input("ot_tarih");
            $start = $request->input("ot_start_time");
            $finish = $request->input("ot_finish_time");
            $detail = $request->input("ot_detail");
        }
        $images = array();

        if($files=$request->file('files')){
            foreach($files as $file){
                $images[]=$file->move('img/ilan', md5(uniqid()) . $file->getClientOriginalName());
            }
        }


        $booking_id = DB::table('booking')->insertGetId(
            [
                'booking_title' => $request->input("booking_title"),
                'province' => $request->input("city"),
                'district' => $request->input("ilce"),
                'location' => $request->input("adres"),
                'service_id' => $service_id,
                'room_number' => $room,
                'm2' => $m2,
                'booking_date' => $tarih,
                'service_start' => $start,
                'service_finish' => $finish,
                'detail' => $detail,
                'client_id' => $client_id,

            ]
        );
        foreach ($images as $image){

            DB::table('booking_images')->insert(
                [
                    'booking_id' => $booking_id,
                    'image_adress' => $image,
                ]
            );
        }


        Helper::fire_event("create",Auth::user(),"booking",$booking_id);

        Helper::fire_alert("booking", "create ", $booking_id);




        return redirect()->to('/ilanlarim');
    }

    public function showEdit(Request $request, $id = 0)
    {
        $booking_data = DB::table('booking')
            ->where('id', $id)
            ->where('status', '<>', 0)
            ->first();

        return view('pages.booking.edit_booking', ['booking_data' => $booking_data]);
    }

    public function edit(Request $request, $id = 0)
    {
        $images = array();

        if($files=$request->file('files')){
            foreach($files as $file){
                $images[]=$file->move('img/ilan', md5(uniqid()) . $file->getClientOriginalName());
            }
        }


        DB::table('booking')
            ->where('status', '<>', 0)
            ->where('id', $id)
            ->update(
                [
                    'booking_title' => $request->input("booking_title"),
                    'province' => $request->input("city"),
                    'district' => $request->input("ilce"),
                    'location' => $request->input("adres"),
                    'room_number' => $request->input("room_number"),
                    'm2' => $request->input("metrekare"),
                    'booking_date' => date('Y-m-d', strtotime(str_replace('/', '-', $request->input("tarih")))),
                    'service_start' => $request->input("start_time"),
                    'service_finish' => $request->input("finish_time"),
                    'detail' => $request->input("detail"),
                ]
            );
        DB::table('booking_images')
            ->where('booking_id',$id)
            ->delete();
        foreach ($images as $image){

            DB::table('booking_images')->insert(
                [
                    'booking_id' => $id,
                    'image_adress' => $image,
                ]
            );
        }


        return redirect()->back();
    }

    public function hidden(Request $request, $id = 0, $op=0)
    {

        if ($op == 0) {
            DB::table('booking')
                ->where('id', $id)
                ->update(
                    [
                        'visibled' => 1,

                    ]
                );

        } else {
            DB::table('booking')
                ->where('id', $id)
                ->update(
                    [
                        'visibled' => 0,

                    ]
                );
        }


        return redirect()->back();
    }

    public function delete(Request $request, $id = 0)
    {

        DB::table('booking')
            ->where('id', $id)
            ->update(
                [
                    'status' =>0,

                ]
            );


        return redirect()->back();
    }

    public function showMyAds()
    {
        $ads_data = DB::table('booking')
            ->where('client_id', Auth::user()->id)
            ->where('status', '<>', 0)
            ->get();
        return view('pages.client.myads', ['ads_data' => $ads_data]);
    } public function tamamlanan()
    {
        $ads_data = DB::table('booking')
            ->Join('booking_offers','booking.id','booking_offers.booking_id')
            ->where('booking_offers.client_id', Auth::user()->id)
            ->where('booking_offers.status', 5)
            ->get();
        return view('pages.client.tamamlanan_ilanlar', ['ads_data' => $ads_data]);
    }
    public function islemde()
    {    $offer_data = DB::table('booking_offers')
        ->select('booking.*','clients.name as cname','services.s_name as sname','clients.province as bas_il','clients.district as bas_ilce','booking_offers.note as note','clients.name as bas_name', 'booking_offers.offer_date as offer_date','booking_offers.id as bid','booking_offers.status as status','booking_offers.prices as prices','clients.id as cid')
        ->Join('booking','booking.id','booking_offers.booking_id')
        ->Join('clients','clients.id','booking_offers.assigned_id')
        ->Join('services','services.id','booking.service_id')
        ->where('booking_offers.client_id', Auth::user()->id)
        ->where('booking_offers.status', 4)
        ->get();
        return view('pages.client.islemdeki_ilanlar', ['offer_data' => $offer_data]);
    }

    public function showDetail(Request $request, $id = 0)
    {
        $ads_data = DB::table('booking')
            ->select('booking.*','clients.name as cname', 'booking.id as bid', 'clients.phone as phone','clients.email as email','clients.logo as logo')
            ->Join('services','services.id','booking.service_id')
            ->Join('clients','clients.id','booking.client_id')
            ->where('booking.id', $id)
            ->where('booking.status', '<>', 0)
            ->first();

        $images= DB::table('booking')
            ->Join('booking_images','booking_images.booking_id','booking.id')
            ->where('booking.id', $id)
            ->get();

        $client_id = DB::table('booking')
            ->where('booking.id', $id)
            ->first();

        $rate= DB::table('comment')
            ->where('c_id', $client_id->client_id)
            ->avg('point');

        return view('pages.booking.ads_detail', ['ads_data' => $ads_data, 'rate' => $rate, 'images'=> $images]);
    }
    public function offer(Request $request){
        $assigned_id = Auth::user()->id;


        $getid=DB::table('booking_offers')->insertGetId(
            [
                'client_id' => $request->input("client_id"),
                'booking_id' => $request->input("booking_id"),
                'note' => $request->input("message"),
                'prices' => $request->input("price"),
                'assigned_id' => $assigned_id,
            ]
        );
        Helper::fire_event("create",Auth::user(),"offers",$getid);

        Helper::fire_alert("offers", "create ", $getid);

        return redirect()->back();
    }

}
