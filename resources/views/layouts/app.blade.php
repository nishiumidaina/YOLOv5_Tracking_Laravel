<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GISproject') }}</title>

    <!-- Scripts -->
    <script src="{{ '/js/app.js' }}" defer></script>
    @yield('js')

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ '/css/app.css' }}" rel="stylesheet">
    <link href="{{ '/css/utility.css' }}" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css"
   integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
   crossorigin=""/>
 <!-- Make sure you put this AFTER Leaflet's CSS -->

    @yield('css')
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    YOLOv5_Tracking
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="main">
        @if(session('success'))
            <div class="alert alert-success" role="alert">
              {{ session('success') }}
            </div>
        @endif
          <div class="row" style='height: 92vh;'>
            <div class="col-md-6 p-0">
              <div class="card h-100">
              <div class="card-body py-2 px-4">
                <a class='d-block' href='/'>全て表示</a>
                <!-- GISの表示 -->
                <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>	
                <script src="https://cdn.geolonia.com/community-geocoder.js"></script>
                <link rel='stylesheet' href='https://unpkg.com/leaflet@1.3.0/dist/leaflet.css' />
                <script src='https://unpkg.com/leaflet@1.3.0/dist/leaflet.js'></script>
                <div id='mapcontainer' style='width:100%; height:300px; z-index:0;'></div>
                <button class="btn btn-primary btn-lg" type="submit" id="btnCurLocation" onClick="setCurLocation()">現在地を表示</button>
                </br>
                </br>
                <ul id=data2>
                </ul>
                <!--<script type="text/javascript" src="{{ asset('/js/gis.js') }}"></script>-->
                <script>
                //ピンの表示
                    var data = @json($spots);
                    var datas = data[0];
                    var user_id = 0;
                    var user_id = datas['user_id'];
                    console.log(user_id);
                    function init_map() {
                    var map = L.map('mapcontainer');
                    map.setView([35.7102, 139.8132], 10); //初期の中心位置とズームレベルを設定
                    //マーカーを表示
                    for (let i=0; i<data.length; i++){
                        var name = data[i].name                    
                        var long = data[i].longitude                    
                        var lat = data[i].latitude
                        var newline = "<br>"
                        var ex = data[i].address
                        var url = data[i].url
                        var url1 = url.substr( 32 );
                        var url2 = '<iframe width="300" height="200" src="https://www.youtube.com/embed/' + url1 + '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';                        
                        var long_int = Number(long)
                        var lat_int =Number(lat)
                        var marker = L.marker([lat_int,long_int]).addTo(map);
                        marker.bindPopup( name + newline + ex + newline + url2).openPopup();
                        console.log(url2);
                        //データ表示用の箱
                        var id_new = `result${i}`
                        $('#data2').append('<li>'+ name + '</br>' + `<div id=${id_new}>None</div>` + '</li>');
                    }
                    //マップタイル読み込み
                    L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 
                        {attribution: "<a href='http://osm.org/copyright'> ©OpenStreetMap </a>"}).addTo(map);
                    }
                    //初期化する
                    window.addEventListener('DOMContentLoaded', init_map());
                    function setCurLocation(){
                    // ユーザーの端末がGeoLocation APIに対応しているかの判定
                        if( navigator.geolocation ){
                            // 現在地を取得
                            navigator.geolocation.getCurrentPosition(
                                // [第1引数] 取得に成功した場合の関数
                                function( position ){
                                    // 取得したデータの整理
                                    var data_p = position.coords ;

                                    // データの整理
                                    var play_lat = data_p.latitude ;//プレイヤーの緯度
                                    var play_lng = data_p.longitude ;//プレイヤーの経度
                                        // アラート表示
                                    alert("あなたの現在位置は、\n[" + play_lat + "," + play_lng + "]\nです。" ) ;
                                            //マーカーに反映
                                    map.setView([play_lat, play_lng], 15);
                                    var marker2 = L.marker([play_lat, play_lng]).addTo(map);
                                },
                                function( error )
                                {
                                    // エラーコード(error.code)の番号
                                    // 0:UNKNOWN_ERROR				原因不明のエラー
                                    // 1:PERMISSION_DENIED			利用者が位置情報の取得を許可しなかった
                                    // 2:POSITION_UNAVAILABLE		電波状況などで位置情報が取得できなかった
                                    // 3:TIMEOUT					位置情報の取得に時間がかかり過ぎた…
                                    // エラー番号に対応したメッセージ
                                    var errorInfo = [
                                    "原因不明のエラーが発生しました…。" ,
                                    "位置情報の取得が許可されませんでした…。" ,
                                    "電波状況などで位置情報が取得できませんでした…。" ,
                                    "位置情報の取得に時間がかかり過ぎてタイムアウトしました…。"
                                    ] ;
                                    // エラー番号
                                    var errorNo = error.code ;
                                    // エラーメッセージ
                                    var errorMessage = "[エラー番号: " + errorNo + "]\n" + errorInfo[ errorNo ] ;
                                    // アラート表示
                                    alert( errorMessage ) ;
                                    // HTMLに書き出し
                                    document.getElementById("result").innerHTML = errorMessage;
                                },
                                // [第3引数] オプション
                                {
                                    "enableHighAccuracy": false,
                                    "timeout": 8000,
                                    "maximumAge": 2000,
                                }   
                            );                       
                        }

                    }
                    // setTimeout関数で2秒ごとに取得
                    var countup = function(){
                        $("#content").empty();
                        $(document).ready(function(){
                            // /**
                            // * Ajax通信メソッド
                            // * @param type
                            // * @param url
                            // * @param dataType
                            // ** /
                            // jQueryのajaxメソッドを使用しajax通信
                            // 入力されたID値を取得
                            $.ajax({
                                url:'dbconnect.php', //送信先
                                type:'POST', //送信方法
                                datatype: 'json', //受け取りデータの種類
                                data:{
                                    'id' : user_id
                                }
                                })
                                // Ajax通信が成功した時
                                .done( function(data) {
                                console.log('通信成功');
                                console.log(data[0]['count']);
                                for (let i=0; i<data.length; i++){
                                    n=0
                                    var id = `result${i}`
                                    $(`#${id}`).html(data[i]['count'] + '人');
                                };
                                
                                })
                                // Ajax通信が失敗した時
                                .fail( function(data) {
                                //$('#result').html(data);
                                console.log('通信失敗');
                                console.log(data);
                                })
                        });
                        setTimeout(countup, 2000);
                    }
                countup();
                </script>
                </div>
              </div>
                </div>
            <div class="col-md-2 p-0">
              <div class="card h-100">
                <div class="card-header d-flex">登録地点一覧 <a class='ml-auto' href='/create'><i class="fas fa-plus-circle"></i></a></div>
                <div class="card-body p-2">
                @foreach($spots as $spot)
                  <a href="/edit/{{ $spot['id'] }}" class='d-block'>{{ $spot['name'] }}</a>
                @endforeach
                </div>
              </div>    
            </div> <!-- col-md-3 -->
            <div class="col-md-4 p-0">
              @yield('content')
            </div>
          </div> <!-- row justify-content-center -->
        </main>
    </div>
    @yield('footer')
</body>
</html>
