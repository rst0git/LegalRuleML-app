@extends('layouts.app')

@section('content')
  <div class="row">
    @if ($data['num_doc'] > 0)
      <div class="row">
        <div class="col-md-12">
          <div class="col-md-offset-9 col-md-3">
            Uploaded Documents: {{$data['num_doc']}}
          </div>
        </div>
      </div>
      <div class="container">
        @foreach($data['docs'] as $doc)
        <div class="row">
          <div class="well">
            <a href="/doc/show/{{$doc->title}}">{{$doc->title}}</a>
          </div>
        </div>
        @endforeach
      </div>
      {{$data['docs']->links()}}
    @else
      @if (!Auth::guest())
        <a class="btn btn-primary" href="/doc/upload">Add new document</a>
      @endif
      <div class="container text-center">
        No documents have been uploaded.
      </div>
    @endif
  </div>
@endsection
