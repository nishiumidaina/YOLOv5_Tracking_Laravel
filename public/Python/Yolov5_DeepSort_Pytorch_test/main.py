# limit the number of cpus used by high performance libraries
import os
from subprocess import IDLE_PRIORITY_CLASS
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"
import math
import sys
sys.path.insert(0, './yolov5')
import datetime

import argparse
import os
#import platform
import shutil
import time
from pathlib import Path
import numpy as np
import cv2
import torch
import torch.backends.cudnn as cudnn
import time
import itertools
import sqlite3
#import datetime
from matplotlib import path

from decimal import Decimal
from yolov5.models.experimental import attempt_load
from yolov5.utils.downloads import attempt_download
from yolov5.models.common import DetectMultiBackend
from yolov5.utils.datasets import LoadImages, LoadStreams, VID_FORMATS
from yolov5.utils.general import (LOGGER, check_img_size, non_max_suppression, scale_coords,
                                  check_imshow, xyxy2xywh, increment_path, strip_optimizer, colorstr)
from yolov5.utils.torch_utils import select_device, time_sync
from yolov5.utils.plots import Annotator, colors, save_one_box
from deep_sort.utils.parser import get_config
from deep_sort.deep_sort import DeepSort

FILE = Path(__file__).resolve()
ROOT = FILE.parents[0]  # yolov5 deepsort root directory
if str(ROOT) not in sys.path:
    sys.path.append(str(ROOT))  # add ROOT to PATH
ROOT = Path(os.path.relpath(ROOT, Path.cwd()))  # relative
import mysql.connector
import shutil


def detect(opt):
    parser = argparse.ArgumentParser()  
    parser.add_argument('--source', type=str, default='0', help='source')
    parser.add_argument('--classes', nargs='+', type=int, help='filter by class: --class 0, or --class 16 17')
    parser.add_argument('--spot_id', type=str, default=0)
    parser.add_argument('--save-txt', action='store_true', help='save MOT compliant results to *.txt')
    parser.add_argument('--yolo_model', nargs='+', type=str, default='yolov5m.pt', help='model.pt path(s)')

    args = parser.parse_args()

    spot_id = args.spot_id
    print(spot_id)
    spot_id = int(spot_id)
#????????????????????????
    conn = mysql.connector.connect(
        host='127.0.0.1',
        port='3306',
        user='0000',
        password='0000',
        database='projectd'
    )
    cur = conn.cursor(buffered=True)
    cur.execute("SELECT spots_id, spots_name, spots_status FROM spots WHERE spots_id = %s" % spot_id)
    db_lis = cur.fetchall()

    if 'Run' in db_lis[0][2]:
        spot_id = db_lis[0][0]
        cur = conn.cursor(buffered=True)
        sql = ("UPDATE spots SET spots_status = %s WHERE spots_id = %s")
        param = ('Run_process',db_lis[0][0])
        cur.execute(sql,param)
        shutil.rmtree('Python/Yolov5_DeepSort_Pytorch_test/runs/')        
    conn.commit()
    cur.close()
    #print(spot_id)

    #?????????????????????
    id_count = []
    id_collect = []
    id_violation = []
    bicycle_lis = []
    id_list = []

    out, source, yolo_model, deep_sort_model, show_vid, save_vid, save_txt, imgsz, evaluate, half, \
        project, exist_ok, update, save_crop = \
        opt.output, opt.source, opt.yolo_model, opt.deep_sort_model, opt.show_vid, opt.save_vid, \
        opt.save_txt, opt.imgsz, opt.evaluate, opt.half, opt.project, opt.exist_ok, opt.update, opt.save_crop
    webcam = source == '0' or source.startswith(
        'rtsp') or source.startswith('http') or source.endswith('.txt')

    # Initialize
    device = select_device(opt.device)
    half &= device.type != 'cpu'  # half precision only supported on CUDA

    # The MOT16 evaluation runs multiple inference streams in parallel, each one writing to
    # its own .txt file. Hence, in that case, the output folder is not restored
    if not evaluate:
        if os.path.exists(out):
            pass
            shutil.rmtree(out)  # delete output folder
        os.makedirs(out)  # make new output folder

    # Directories
    if type(yolo_model) is str:  # single yolo model
        exp_name = yolo_model.split(".")[0]
    elif type(yolo_model) is list and len(yolo_model) == 1:  # single models after --yolo_model
        exp_name = yolo_model[0].split(".")[0]
    else:  # multiple models after --yolo_model
        exp_name = "ensemble"
    exp_name = exp_name + "_" + deep_sort_model.split('/')[-1].split('.')[0]
    save_dir = increment_path(Path(project) / exp_name, exist_ok=exist_ok)  # increment run if project name exists
    (save_dir / 'tracks' if save_txt else save_dir).mkdir(parents=True, exist_ok=True)  # make dir

    # Load model
    model = DetectMultiBackend(yolo_model, device=device, dnn=opt.dnn)
    stride, names, pt = model.stride, model.names, model.pt
    imgsz = check_img_size(imgsz, s=stride)  # check image size

    # Half
    half &= pt and device.type != 'cpu'  # half precision only supported by PyTorch on CUDA
    if pt:
        model.model.half() if half else model.model.float()

    # Set Dataloader
    vid_path, vid_writer = None, None
    # Check if environment supports image displays
    if show_vid:
        show_vid = check_imshow()

    # Dataloader
    if webcam:
        show_vid = check_imshow()
        cudnn.benchmark = True  # set True to speed up constant image size inference
        dataset = LoadStreams(source, img_size=imgsz, stride=stride, auto=pt)
        nr_sources = len(dataset)
    else:
        dataset = LoadImages(source, img_size=imgsz, stride=stride, auto=pt)
        nr_sources = 1
    vid_path, vid_writer, txt_path = [None] * nr_sources, [None] * nr_sources, [None] * nr_sources

    # initialize deepsort
    cfg = get_config()
    cfg.merge_from_file(opt.config_deepsort)

    # Create as many trackers as there are video sources
    deepsort_list = []
    for i in range(nr_sources):
        deepsort_list.append(
            DeepSort(
                deep_sort_model,
                device,
                max_dist=cfg.DEEPSORT.MAX_DIST,
                max_iou_distance=cfg.DEEPSORT.MAX_IOU_DISTANCE,
                max_age=cfg.DEEPSORT.MAX_AGE, n_init=cfg.DEEPSORT.N_INIT, nn_budget=cfg.DEEPSORT.NN_BUDGET,
            )
        )
    outputs = [None] * nr_sources

    # Get names and colors
    names = model.module.names if hasattr(model, 'module') else model.names

    # Run tracking
    model.warmup(imgsz=(1 if pt else nr_sources, 3, *imgsz))  # warmup
    dt, seen = [0.0, 0.0, 0.0, 0.0], 0
    for frame_idx, (path1, im, im0s, vid_cap, s) in enumerate(dataset):
        t1 = time_sync()
        im = torch.from_numpy(im).to(device)
        im = im.half() if half else im.float()  # uint8 to fp16/32
        im /= 255.0  # 0 - 255 to 0.0 - 1.0
        if len(im.shape) == 3:
            im = im[None]  # expand for batch dim
        t2 = time_sync()
        dt[0] += t2 - t1

        # Inference
        visualize = increment_path(save_dir / Path(path1[0]).stem, mkdir=True) if opt.visualize else False
        pred = model(im, augment=opt.augment, visualize=visualize)
        t3 = time_sync()
        dt[1] += t3 - t2

        # Apply NMS
        pred = non_max_suppression(pred, opt.conf_thres, opt.iou_thres, opt.classes, opt.agnostic_nms, max_det=opt.max_det)
        dt[2] += time_sync() - t3

        # Process detections
        for i, det in enumerate(pred):  # detections per image
            #??????????????????????????????
            cur = conn.cursor(buffered=True)
            #print(spot_id)
            cur.execute("SELECT spots_id, spots_name, spots_status FROM spots WHERE spots_id = '%s'" % spot_id)
            db_lis_last = cur.fetchall()
            conn.commit()
            cur.close()
            if 'Stop' in db_lis_last[0][2]:
                cur = conn.cursor(buffered=True)
                sql = ("UPDATE spots SET spots_status = %s WHERE spots_id = %s")
                param2 = ('None',spot_id)
                cur.execute(sql,param2)
                cur.execute('DELETE FROM bicycles WHERE spots_id = %s',(spot_id,))
                shutil.rmtree('Python/Yolov5_DeepSort_Pytorch_test/runs/track/')
                conn.commit()
                cur.close()
                exit()


            seen += 1
            if webcam:  # nr_sources >= 1
                p, im0, _ = path1[i], im0s[i].copy(), dataset.count
                p = Path(p)  # to Path
                s += f'{i}: '
                txt_file_name = p.name
                save_path = str(save_dir / p.name)  # im.jpg, vid.mp4, ...
            else:
                p, im0, _ = path1, im0s.copy(), getattr(dataset, 'frame', 0)
                p = Path(p)  # to Path
                # video file
                if source.endswith(VID_FORMATS):
                    txt_file_name = p.stem
                    save_path = str(save_dir / p.name)  # im.jpg, vid.mp4, ...
                # folder with imgs
                else:
                    txt_file_name = p.parent.name  # get folder name containing current img
                    save_path = str(save_dir / p.parent.name)  # im.jpg, vid.mp4, ...

            txt_path = str(save_dir / 'tracks' / txt_file_name)  # im.txt
            s += '%gx%g ' % im.shape[2:]  # print string
            imc = im0.copy() if save_crop else im0  # for save_crop

            annotator = Annotator(im0, line_width=2, pil=not ascii)

            if det is not None and len(det):
                # Rescale boxes from img_size to im0 size
                det[:, :4] = scale_coords(im.shape[2:], det[:, :4], im0.shape).round()
                
                # Print results
                for c in det[:, -1].unique():
                    n = (det[:, -1] == c).sum()  # detections per class
                    s += f"{n} {names[int(c)]}{'s' * (n > 1)}, "  # add to string
                    # Print counter
                    n_1 = (det[:, -1] == 0).sum() #?????????A????????????????????????
                    a = f"{n_1} "#{'A'}{'s' * (n_1 > 1)}, "
                    #cv2.putText(im0, "BOX1_count" , (20, 50), 0, 1.0, (71, 99, 255), 3)
                    cv2.putText(im0, "Bicycle : " + str(a), (20, 50), 0, 0, (71, 99, 255), 3)

                    #??????????????????????????????
                    cur = conn.cursor(buffered=True)
                    #print(spot_id)
                    cur.execute("SELECT spots_id, spots_name, spots_status FROM spots WHERE spots_id = '%s'" % spot_id)
                    db_lis_last = cur.fetchall()

                    if 'Stop' in db_lis_last[0][2]:
                        cur = conn.cursor(buffered=True)
                        sql = ("UPDATE spots SET spots_status = %s WHERE spots_id = %s")
                        param2 = ('None',spot_id)
                        cur.execute(sql,param2)
                        cur.execute('DELETE FROM bicycles WHERE spots_id = %s',(spot_id,))
                        shutil.rmtree('Python/Yolov5_DeepSort_Pytorch_test/runs/track/')
                        conn.commit()
                        cur.close()
                        exit()
                    elif 'Run_process' in db_lis_last[0][2]:
                        cur = conn.cursor(buffered=True)
                        sql = ("UPDATE spots SET spots_count = %s WHERE spots_id = %s")
                        param2 = (a,spot_id)
                        cur.execute(sql,param2)
                        # DB????????????
                    conn.commit()
                    cur.close()                    
                xywhs = xyxy2xywh(det[:, 0:4])
                confs = det[:, 4]
                clss = det[:, 5]

                # pass detections to deepsort
                t4 = time_sync()
                outputs[i] = deepsort_list[i].update(xywhs.cpu(), confs.cpu(), clss.cpu(), im0)
                t5 = time_sync()
                dt[3] += t5 - t4

                # draw boxes for visualization
                if len(outputs[i]) > 0:
                    
                    for j, (output) in enumerate(outputs[i]):

                        bboxes = output[0:4]
                        id = output[4]
                        id2 = str(id)
                        cls = output[5]
                        conf = output[6]

                        #????????????????????????
                        #label = [["A","2,2","4,7","7,6","7,1"]]
                        label = [["A", 0,350, 0,600, 625,675, 700, 600]]
                        for il in range(len(label)):
                            label_mame = label[il][0]
                            P1X = label[il][1]
                            P1Y = label[il][2]
                            P2X = label[il][3]
                            P2Y = label[il][4]
                            P3X = label[il][5]
                            P3Y = label[il][6]
                            P4X = label[il][7]
                            P4Y = label[il][8]
                            polygon = path.Path(
                                [
                                    [P1X, P1Y],
                                    [P2X, P2Y],
                                    [P3X, P3Y],
                                    [P4X, P4Y],
                                ]
                            )
                            #XY??????????????????Y?????????????????????????????????720~Y
                            id_out = int(math.floor(id))
                            X_out= int(math.floor(output[0]))
                            Y_out= 720 - int(math.floor(output[1]))
                            #???????????????
                            XY_out = polygon.contains_point([X_out, Y_out])
                            if XY_out:
                                
                                #????????????????????????
                                cur = conn.cursor(buffered=True)
                                #print(spot_id)
                                cur.execute("SELECT get_id FROM bicycles WHERE spots_id = '%s'" % spot_id)
                                bicycle_lis = cur.fetchall()
                                #print(bicycle_lis)
                                if not id in bicycle_lis:
                                    cur.execute("INSERT INTO bicycles (spots_id,get_id,bicycles_x_coordinate,bicycles_y_coordinate) VALUES (%s, %s, %s, %s)", (spot_id,id_out,X_out,Y_out))
                                elif id in bicycle_lis:
                                    cur.execute("UPDATE bicycles SET bicycles_x_coordinate = %s,bicycles_y_coordinate = %s WHERE get_id = %s AND spots_id = %s",(X_out, Y_out,id_out, spot_id))

                                cur.execute("SELECT updated_at, created_at FROM bicycles WHERE spots_id = %s AND get_id = %s",(spot_id, id_out))
                                time_lis = cur.fetchall()
                                #print(time_lis)

                                #??????????????????
                                out_time = 60 #?????????????????????(??????)
                                time_dif = time_lis[0][0] - time_lis[0][1]
                                time_total = time_dif.total_seconds() 
                                if time_total >= out_time:
                                    #time_lis[3] = '????????????'
                                    cur.execute("UPDATE bicycles SET bicycles_status = %s WHERE get_id = %s AND spots_id = %s",('??????', id_out, spot_id))
                                    if not id2 in id_violation:
                                        id_violation.append(id2)
                                id_collect.append(int(math.floor(float(id2))))                           
                                conn.commit()
                                cur.close()
                                print(id,X_out,Y_out)                         
                        #??????
                        if save_txt:
                            # to MOT format
                            bbox_left = output[0]#X??????
                            bbox_top = output[1]#Y??????
                            bbox_w = output[2] - output[0]#???
                            bbox_h = output[3] - output[1]#??????
                            # Write MOT compliant results to file
                            with open(txt_path + '.txt', 'a') as f:
                                f.write(('%g ' * 10 + '\n') % (frame_idx + 1, id, bbox_left,  # MOT format
                                                               bbox_top, bbox_w, bbox_h, -1, -1, -1, i))

                        if save_vid or save_crop or show_vid:  # Add bbox to image
                            c = int(cls)  # integer class
                            label = f'{id:0.0f} {names[c]} {conf:.2f}'
                            annotator.box_label(bboxes, label, color=colors(c, True))
                            if save_crop:
                                txt_file_name = txt_file_name if (isinstance(path, list) and len(path) > 1) else ''
                                save_one_box(bboxes, imc, file=save_dir / 'crops' / txt_file_name / names[c] / f'{id}' / f'{p.stem}.jpg', BGR=True)
                #?????????
                #???????????????ID???????????????
                for i3 in range(len(id_count)):
                    if not id_count[i3][0] in id_collect:
                        id_count[i3] = "None"
                target = "None"  #None???????????????
                id_count = [item for item in id_count if item != target]
                for i_b in range(len(bicycle_lis)):
                    if not bicycle_lis[i_b][0] in id_collect:
                        cur = conn.cursor(buffered=True)
                        cur.execute("UPDATE bicycles SET bicycles_status = %s WHERE get_id = %s AND spots_id = %s",('None', bicycle_lis[i_b][0], spot_id)) 
                print(id_collect)#???????????????????????????
                #print(id_count)
                #print(id_violation)#????????????
                bicycle_lis.clear()
                id_collect.clear()
                LOGGER.info(f'{s}Done. YOLO:({t3 - t2:.3f}s), DeepSort:({t5 - t4:.3f}s)')

            else:
                deepsort_list[i].increment_ages()
                LOGGER.info('No detections')

            # Stream results
            im0 = annotator.result()
            if show_vid:
                cv2.imshow(str(p), im0)
                cv2.waitKey(1)  # 1 millisecond

            # Save results (image with detections)
            if save_vid:
                if vid_path[i] != save_path:  # new video
                    vid_path[i] = save_path
                    if isinstance(vid_writer[i], cv2.VideoWriter):
                        vid_writer[i].release()  # release previous video writer
                    if vid_cap:  # video
                        fps = vid_cap.get(cv2.CAP_PROP_FPS)
                        w = int(vid_cap.get(cv2.CAP_PROP_FRAME_WIDTH))
                        h = int(vid_cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
                    else:  # stream
                        fps, w, h = 30, im0.shape[1], im0.shape[0]
                    save_path = str(Path(save_path).with_suffix('.mp4'))  # force *.mp4 suffix on results videos
                    vid_writer[i] = cv2.VideoWriter(save_path, cv2.VideoWriter_fourcc(*'mp4v'), fps, (w, h))
                vid_writer[i].write(im0)

    # Print results
    t = tuple(x / seen * 1E3 for x in dt)  # speeds per image
    LOGGER.info(f'Speed: %.1fms pre-process, %.1fms inference, %.1fms NMS, %.1fms deep sort update \
        per image at shape {(1, 3, *imgsz)}' % t)
    if save_txt or save_vid:
        s = f"\n{len(list(save_dir.glob('tracks/*.txt')))} tracks saved to {save_dir / 'tracks'}" if save_txt else ''
        LOGGER.info(f"Results saved to {colorstr('bold', save_dir)}{s}")
    if update:
        strip_optimizer(yolo_model)  # update model (to fix SourceChangeWarning)


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--spot_id', type=str, default=0)
    parser.add_argument('--yolo_model', nargs='+', type=str, default='yolov5m.pt', help='model.pt path(s)')
    parser.add_argument('--deep_sort_model', type=str, default='osnet_x0_25')
    parser.add_argument('--source', type=str, default='0', help='source')  # file/folder, 0 for webcam
    parser.add_argument('--output', type=str, default='inference/output', help='output folder')  # output folder
    parser.add_argument('--imgsz', '--img', '--img-size', nargs='+', type=int, default=[640], help='inference size h,w')
    parser.add_argument('--conf-thres', type=float, default=0.5, help='object confidence threshold')
    parser.add_argument('--iou-thres', type=float, default=0.5, help='IOU threshold for NMS')
    parser.add_argument('--fourcc', type=str, default='mp4v', help='output video codec (verify ffmpeg support)')
    parser.add_argument('--device', default='', help='cuda device, i.e. 0 or 0,1,2,3 or cpu')
    parser.add_argument('--show-vid', action='store_true', help='display tracking video results')
    parser.add_argument('--save-vid', action='store_true', help='save video tracking results')
    parser.add_argument('--save-txt', action='store_true', help='save MOT compliant results to *.txt')
    # class 0 is person, 1 is bycicle, 2 is car... 79 is oven
    parser.add_argument('--classes', nargs='+', type=int, help='filter by class: --class 0, or --class 16 17')
    parser.add_argument('--agnostic-nms', action='store_true', help='class-agnostic NMS')
    parser.add_argument('--augment', action='store_true', help='augmented inference')
    parser.add_argument('--update', action='store_true', help='update all models')
    parser.add_argument('--evaluate', action='store_true', help='augmented inference')
    parser.add_argument("--config_deepsort", type=str, default="deep_sort/configs/deep_sort.yaml")
    parser.add_argument("--half", action="store_true", help="use FP16 half-precision inference")
    parser.add_argument('--visualize', action='store_true', help='visualize features')
    parser.add_argument('--max-det', type=int, default=1000, help='maximum detection per image')
    parser.add_argument('--save-crop', action='store_true', help='save cropped prediction boxes')
    parser.add_argument('--dnn', action='store_true', help='use OpenCV DNN for ONNX inference')
    parser.add_argument('--project', default=ROOT / 'runs/track', help='save results to project/name')
    parser.add_argument('--name', default='exp', help='save results to project/name')
    parser.add_argument('--exist-ok', action='store_true', help='existing project/name ok, do not increment')
    #parser.add_argument('--spot_id', type=int, default=0)
    opt = parser.parse_args()
    opt.imgsz *= 2 if len(opt.imgsz) == 1 else 1  # expand

    with torch.no_grad():
        detect(opt)

