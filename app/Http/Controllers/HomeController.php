<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Spot;

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
        $spots = Spot::where('user_id', $user['id'])->get();
        $spot = Spot::where('user_id', $user['id'])->get();
        return view('home', compact('user', 'spots','spot'));
    }
        public function create()
    {
        $user = \Auth::user();
        $spots = Spot::where('user_id', $user['id'])->get();
        $spot = Spot::where('user_id', $user['id'])->get();
        return view('create', compact('user','spots','spot'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $query = $data['address'];
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
            'name' => $data['name'],
            'user_id' => $data['user_id'], 
             'longitude' => $insert_long, 
             'latitude' => $insert_lat,
             'url' => $data['url'],
             'address' => $data['address'],
             'status' => 'None',
             'count' => 0
        ]);
        return redirect()->route('home');
    }

    public function edit($id){
        $user = \Auth::user();
        $spot = Spot::where('id', $id)->where('user_id', $user['id'])->first();
        $spots = Spot::where('user_id', $user['id'])->get();
        //   dd($memo);
        return view('edit',compact('user','spot','spots'));
    }

    public function delete(Request $request, $id)
    {
        $inputs = $request->all();
        // dd($inputs);
         Spot::where('id', $id)->delete();
        return redirect()->route('home')->with('success', '削除が完了しました！');
    }

    public function start(Request $request, $id)
    {
        $inputs = $request->all();
        $spots = Spot::where('id', $id)->get();
        $spot_lis =  json_decode($spots , true); 
        //判定
        if ($spots[0]["status"]=="Run" or $spots[0]["status"]=="Run_process"){
            return redirect()->route('home')->with('success', '処理中です');
        }else if ($spots[0]["status"]=="None"){
           Spot::where('id', $id)->update(['status'=>'Start']);
           $command = 'python Python/Yolov5_DeepSort_Pytorch_test/start.py';
           popen('start "" ' . $command, 'r');
           return redirect()->route('home')->with('success', '処理を開始します');
        }
    }
    public function stop(Request $request, $id)
    {
        $inputs = $request->all();
        $spots = Spot::where('id', $id)->get();
        $spot_lis =  json_decode($spots , true); 
        //判定
        if ($spots[0]["status"]=="Run_process"){
           Spot::where('id', $id)->update(['status'=>'Stop']); 
           return redirect()->route('home')->with('success', '処理を停止します');
        }else if ($spots[0]["status"]=="Start" or $spots[0]["status"]=="Stop"){
            Spot::where('id', $id)->update(['status'=>'None']);
            return redirect()->route('home')->with('success', '無効な処理です');
        }
        else{
            return redirect()->route('home')->with('success', '処理が開始されていません');
        }
    }

}
