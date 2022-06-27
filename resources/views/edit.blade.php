@extends('layouts.app')

@section('content')

<div class="row justify-content-center ml-0 mr-0 h-100">
    <div class="card w-100">

    <div class="card-header d-flex justify-content-between">
    {{ $spot['spots_name'] }}
        </div>
        <div class="form-group">
            <form method='POST' action="/start/{{$spot['spots_id']}}" id='delete-form'>
                @csrf
                <button class="btn btn-primary btn-lg"> 実行<i id='start-button'></i></button>
            </form>
        </div>
        <div class="form-group">
            <form method='POST' action="/stop/{{$spot['spots_id']}}" id='delete-form'>
                @csrf
                <button class="btn btn-primary btn-lg">停止<i id='start-button'></i></button>
            </form>
        </div>        
        <div class="form-group">   
            <form method='POST' action="/delete/{{$spot['spots_id']}}" id='delete-form'>
                @csrf
                <button class="btn btn-primary btn-lg"> 削除<i id='delete-button'></i></button>
            </form>
        </div>
    </div>
</div>
@endsection