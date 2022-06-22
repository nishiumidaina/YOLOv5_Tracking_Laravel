function init_map() {

    var map = L.map('mapcontainer');
    map.setView([35.7102, 139.8132], 10); //初期の中心位置とズームレベルを設定

    //マップタイルを読み込み、引用元を記載する
    L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 
        {attribution: "<a href='http://osm.org/copyright'> ©OpenStreetMap </a>"}).addTo(map);
}
    //ダウンロード時に初期化する
    window.addEventListener('DOMContentLoaded', init_map());
