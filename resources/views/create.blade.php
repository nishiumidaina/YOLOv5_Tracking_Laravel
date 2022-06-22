@extends('layouts.app')

@section('content')
<div class="row justify-content-center ml-0 mr-0 h-100">
    <div class="card w-100">
        <div class="card-header">登録</div>
        <div class="card-body">
            <form method='POST' action="/store">
                @csrf
                <input type='hidden' name='user_id' value="{{ $user['id'] }}">
                <div class="form-group">
                    <label for="name">地点名</label>
                    <input name='name' type="text" class="form-control" id="name" placeholder="地点名を入力">
                    <label for="url">URL(YouTube)</label>
                    <input name='url' type="text" class="form-control" id="url" placeholder="URLを入力">
                    <label for="address">住所</label>
                    <input name='address' type="text" class="form-control" id="address" placeholder="住所を入力">
                </div>
                <button type='submit' class="btn btn-primary btn-lg">保存</button>
            </form>
        </div>
    </div>
</div>
@endsection