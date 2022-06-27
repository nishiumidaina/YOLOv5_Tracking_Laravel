@extends('layouts.app')

@section('content')
<div class="row justify-content-center ml-0 mr-0 h-100">
    <div class="card w-100">
        <div class="card-header">登録</div>
        <div class="card-body">
            <form method='POST' action="/store">
                @csrf
                <input type='hidden' name='users_id' value="{{ $user['id'] }}">
                <div class="form-group">
                    <label for="spots_name">地点名</label>
                    <input name='spots_name' type="text" class="form-control" id="spots_name" placeholder="地点名を入力">
                    <label for="spots_url">URL(YouTube)</label>
                    <input name='spots_url' type="text" class="form-control" id="spots_url" placeholder="URLを入力">
                    <label for="spots_address">住所</label>
                    <input name='spots_address' type="text" class="form-control" id="spots_address" placeholder="住所を入力">
                </div>
                <button type='submit' class="btn btn-primary btn-lg">保存</button>
            </form>
        </div>
    </div>
</div>
@endsection