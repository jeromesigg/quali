@extends('layouts.admin')

@section('content')
    <div class="breadcrumb-holder">
        <div class="container-fluid">
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin/">Dashboard</a></li>
            <li class="breadcrumb-item active">Kompetenzen</li>
          </ul>
        </div>
    </div>
    <section>
        <div class="container-fluid">
            <!-- Page Header-->
            <header> 
                <h1 class="h3 display">Kompetenzen</h1>
            </header>
            @if (Auth::user()->isAdmin())
                {!! Form::open(['method' => 'POST', 'action'=>'AdminQuestionsController@uploadFile', 'enctype' => 'multipart/form-data']) !!}
                <input type='file' name='file' >
                <input type='submit' name='submit' value='Import'>
                {!! Form::close()!!}
            @endif
            <div class="row">
                @if ($questions)
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Kapitel-Nr.</th>
                                <th scope="col">Kapitel</th>
                                <th scope="col">Nummer</th>
                                <th scope="col">Kompetenz</th>
                                <th scope="col">Beschreibung</th>
                                <th scope="col">Created Date</th>
                                <th scope="col">Updated Date</th>
                            </tr>
                        </thead>
                    @foreach ($questions as $question)
                        <tbody>
                            <tr>
                                <td>{{$question->chapter['number']}}</td>
                                <td>{{$question->chapter['name']}}</td>
                                <td>{{$question->number}}</td>
                                <td><a href="{{route('questions.edit',$question->id)}}">{{$question->competence}}</a></td>
                                <td>{{$question->name}}</td>
                                <td>{{$question->created_at ? $question->created_at->diffForHumans() : 'no date'}}</td>
                                <td>{{$question->updated_at ? $question->updated_at->diffForHumans() : 'no date'}}</td>
                            </tr>
                        </tbody>
                    @endforeach
                    </table>
                
                @endif

            </div>
        </div>
    </section>
@endsection