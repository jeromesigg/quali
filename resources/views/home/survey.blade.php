@extends('layouts.layout')

@section('survey_content')
<button onclick="topFunction()" id="myBtn" title="Go to top"><i class="fas fa-arrow-up"></i></button>
    @foreach ($surveys as $survey)
        
  
        <h1>{{$survey->surveyName()}}</h1>

        <p class="lead">
            von {{$survey->user['username']}}
        </p>
        <p>
            Die <span class = 'core_competence'>blau hinterlegten Kompetenzen</span>  sind die Kernkompetenzen für deine Ausbildungsstufe.
        </p>
        {!! Form::model($survey, ['method' => 'Patch', 'action'=>['SurveysController@update',$survey->id]]) !!}
            @foreach ($survey->chapters as $chapter)
                <div id="recent-activities-wrapper-{{$chapter->chapter['number']}}" class="card updates activities">
                    <a data-toggle="collapse" data-parent="#recent-activities-wrapper-{{$chapter->chapter['number']}}" href="#activities-box-{{$chapter->chapter['number']}}" aria-expanded="true" aria-controls="activities-box">
                        <div id="activites-header" class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h5 display">
                                {{$chapter->chapter['number']}}. {{$chapter->chapter['name']}}
                            </h2>
                            <i class="fa fa-angle-down"></i>
                        </div> 
                    </a>

                    <div id="activities-box-{{$chapter->chapter['number']}}" role="tabpanel" class="collapse">

                        @foreach ($chapter->questions as $question)  
                            <table class="table">
                                <tbody>
                                <tr class="{{($question->question['competence_js1'] && $camp['camp_type_id']===config('status.camptype_JS1')) ||
                                    ($question->question['competence_js2'] && $camp['camp_type_id']===config('status.camptype_JS2'))? 'core_competence':''}}">
                                    <td width="50px">{{$question->question['number']}}</td>
                                    <td width="150px">{{$question->question['competence']}}</td>
                                    <td width="300px">{{$question->question['name']}}</td>
                                </tr>
                                </tbody>
            
                            </table>
                            <div class="form-group row" style="padding-left:10px; padding-right:10px"> 
                                <div class="col-sm-12">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                @foreach ($answers as $answer)  
                                                @if ($aktUser->isLeader())
                                                
                                                    <td width="50px">     
                                                        {{ Form::radio('answers['.$question['id'].']', $answer['id'], ($question['answer_leader_id']===NULL) && ($answer['name']==='0') ? true : (($question['answer_leader_id']===$answer['id']) ? true : false), ["id" => $question->question['number'].$answer['id']])}}
                                                        {!! Form::label($question->question['number'].$answer['id'], $answer['name'] ? $answer['name'] : " 0 ") !!}
                                                    </td>
                                                @else
                                                    
                                                    @if ($survey->survey_status_id === config('config.status_2Offen'))
                                                        <td width="50px">
                                                            {{Form::radio('answers['.$question['id'].']', $answer['id'],  ($question['answer_second_id']===NULL) && ($answer['name']==='0') ? true : (($question['answer_second_id']===$answer['id']) ? true : false), ["id" => $question->question['number'].$answer['id']]) }}
                                                            {!! Form::label($question->question['number'].$answer['id'], $answer['name'] ? $answer['name'] : " 0 ") !!}
                                                        </td>
                                                    @else
                                                        <td width="50px">
                                                            {{Form::radio('answers['.$question['id'].']', $answer['id'],  ($question['answer_first_id']===NULL) && ($answer['name']==='0') ? true : (($question['answer_first_id']===$answer['id']) ? true : false), ["id" => $question->question['number'].$answer['id']]) }}
                                                            {!! Form::label($question->question['number'].$answer['id'], $answer['name'] ? $answer['name'] : " 0 ") !!}
                                                        </td>
                                                    @endif
                                                @endif
                                                @endforeach  
                                            </tr>
                                        </tbody>
                    
                                    </table>
                                </div>
                                <div class="col-sm-2"> 
                                    @if($aktUser->isLeader())
                                        {!! Form::label('comment_leader', 'Kommentar:') !!}
                                    @else
                                        @if ($survey->survey_status_id === config('config.status_2Offen'))
                                            {!! Form::label('comment_second', 'Kommentar:') !!}
                                        @else
                                            {!! Form::label('comment_first', 'Kommentar:') !!}
                                        @endif
                                    @endif
                                </div>
                                <div class="col-sm-10">
                                    @if($aktUser->isLeader())
                                        {!! Form::textarea('comments['.$question['id'].']', $question['comment_leader'], ['class' => 'form-control', 'rows'=> 2]) !!}
                                    @else
                                    @if ($survey->survey_status_id === config('config.status_2Offen'))
                                        {!! Form::textarea('comments['.$question['id'].']', $question['comment_second'], ['class' => 'form-control', 'rows'=> 2]) !!} 
                                    @else
                                        {!! Form::textarea('comments['.$question['id'].']', $question['comment_first'], ['class' => 'form-control', 'rows'=> 2]) !!}
                                    @endif
                                            @endif
                                        </div>
                            </div>
                        @endforeach  
                            
                    </div>
                </div>      
            @endforeach
            <div class="card radar-chart-example">
                <div class="card-header d-flex align-items-center">
                    <h4>Kompetenzendarstellung</h4>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                    <canvas id="radarChart-1"></canvas>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    Die Grafik wird erst nach dem Speichern aktualisiert.
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-4">
                    <button type="submit" name="action" value="save" class = "btn btn-primary">{{$survey->surveyName()}} speichern</button>
                </div>
                @if ($aktUser->isTeilnehmer())
                    <div class="col-sm-4">
                        <button type="submit" name="action" value="close" class = "btn btn-secondary">{{$survey->surveyName()}} speichern und abschliessen</button>
                    </div>  
                @endif
            </div>
        {!! Form::close()!!} 
    @endforeach

   
@endsection

@section('scripts')
    @include('home.radar')
@endsection