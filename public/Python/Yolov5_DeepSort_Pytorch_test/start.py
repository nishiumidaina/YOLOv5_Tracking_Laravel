import subprocess
import mysql.connector

# コネクション作成
conn = mysql.connector.connect(
    host='127.0.0.1',
    port='3306',
    user='0000',
    password='0000',
    database='projectd'
)
# 接続状況確認
print(conn.is_connected())
cur = conn.cursor(buffered=True)
cur.execute("SELECT spots_id, spots_name, spots_status, spots_url FROM spots")
db_lis = cur.fetchall()
print(db_lis[0])
# DB操作終了
cur.close()
for i in range(len(db_lis)):
    url = db_lis[i][3]
    if db_lis[i][2] == 'Start':
        #BDの値を変更
        cur = conn.cursor(buffered=True)
        sql = ("UPDATE spots SET spots_status = %s WHERE spots_id = %s")
        param = ('Run',db_lis[i][0])
        cur.execute(sql,param)
        conn.commit()
        cur.close()
        spot_id = db_lis[i][0]        
        N = subprocess.Popen('python Python/Yolov5_DeepSort_Pytorch_test/main.py --source "%s" --spot_id %s --yolo_model ./Python/Yolov5_DeepSort_Pytorch_test/model_weight/best.pt' % (url,int(spot_id)),shell=True)

