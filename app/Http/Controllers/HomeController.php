<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Spot;
use App\Label;
use App\Bicycle;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = \Auth::user();
        $spots = Spot::where('users_id', $user['id'])->get();
        $spot = Spot::where('users_id', $user['id'])->get();
        return view('home', compact('user', 'spots','spot'));
    }
        public function create()
    {
        $user = \Auth::user();
        $spots = Spot::where('users_id', $user['id'])->get();
        $spot = Spot::where('users_id', $user['id'])->get();
        return view('create', compact('user','spots','spot'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $query = $data['spots_address'];
        $query = urlencode($query);
        $url = "http://www.geocoding.jp/api/";
        $url.= "?v=1.1&q=".$query;
        $line="";
        $fp = fopen($url, "r");
        while(!feof($fp)) {
            
            $line.= fgets($fp);
        }
        fclose($fp);
        $xml = simplexml_load_string($line);
        $insert_long = (string) $xml->coordinate->lng;
        $insert_lat= (string) $xml->coordinate->lat;
        $spot_id = Spot::insertGetId([
            'spots_name' => $data['spots_name'],
            'users_id' => $data['users_id'], 
             'spots_longitude' => $insert_long, 
             'spots_latitude' => $insert_lat,
             'spots_url' => $data['spots_url'],
             'spots_address' => $data['spots_address'],
             'spots_status' => 'None',
             'spots_count' => 0,
             'spots_over_time' => 60,
        ]);
        return redirect()->route('home');
    }

    public function edit($id){
        $user = \Auth::user();
        $spot = Spot::where('spots_id', $id)->where('users_id', $user['id'])->first();
        $spots = Spot::where('users_id', $user['id'])->get();
        //   dd($memo);
        return view('edit',compact('user','spot','spots'));
    }

    public function delete(Request $request, $id)
    {
        $inputs = $request->all();
        // dd($inputs);
         Spot::where('spots_id', $id)->delete();
        return redirect()->route('home')->with('success', '削除が完了しました！');
    }

    public function start(Request $request, $id)
    {
        $inputs = $request->all();
        $spots = Spot::where('spots_id', $id)->get();
        $spot_lis =  json_decode($spots , true); 
        //判定
        if ($spots[0]["spots_status"]=="Run" or $spots[0]["status"]=="Run_process"){
            return redirect()->route('home')->with('success', '処理中です');
        }else if ($spots[0]["spots_status"]=="None"){
           Spot::where('spots_id', $id)->update(['spots_status'=>'Start']);
           $command = 'python Python/Yolov5_DeepSort_Pytorch_test/start.py';
           popen('start "" ' . $command, 'r');
           return redirect()->route('home')->with('success', '処理を開始します');
        }
    }
    public function stop(Request $request, $id)
    {
        $inputs = $request->all();
        $spots = Spot::where('spots_id', $id)->get();
        $spot_lis =  json_decode($spots , true); 
        //判定
        if ($spots[0]["spots_status"]=="Run_process"){
           Spot::where('spots_id', $id)->update(['spots_status'=>'Stop']); 
           return redirect()->route('home')->with('spots_success', '処理を停止します');
        }else if ($spots[0]["spots_status"]=="Start" or $spots[0]["spots_status"]=="Stop"){
            Spot::where('spots_id', $id)->update(['spots_status'=>'None']);
            return redirect()->route('home')->with('success', '無効な処理です');
        }
        else{
            return redirect()->route('home')->with('success', '処理が開始されていません');
        }
    }

}
