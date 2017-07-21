@extends('layouts.app')

@section('title', 'Index')

@section('content')
    <div class="row">
        @if ($data['num_doc'] > 0)
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-offset-9 col-md-3">
                        Uploaded documents: {{$data['num_doc']}} ({{$data['num_doc_shown']}} shown)
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
                <a class="btn btn-primary" href={{route('doc_upload')}}>Add new document</a>
            @endif
            <div class="container text-center">
                No documents have been uploaded.
            </div>
        @endif
    </div>
@endsection
