<?php
$id = $_POST['id'];

// データベース接続

$host = 'localhost';
$dbname = 'gisproject';
$dbuser = '0000';
$dbpass = '0000';

try {
$dbh = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $dbuser,$dbpass, array(PDO::ATTR_EMULATE_PREPARES => false));
} catch (PDOException $e) {
 var_dump($e->getMessage());
 exit;
}
// データ取得
$sql = "SELECT id, name, count, status FROM spots WHERE user_id = ?";
$stmt = ($dbh->prepare($sql));
$stmt->execute(array($id));

//あらかじめ配列を生成しておき、while文で回します。
$spot_list = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
 $spot_list[]=array(
  'id' =>$row['id'],
  'name'=>$row['name'],
  'count'=>$row['count'],
  'status'=>$row['status']
 );
}

//jsonとして出力
header('Content-type: application/json');
echo json_encode($spot_list,JSON_UNESCAPED_UNICODE);